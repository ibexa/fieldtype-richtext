<?php

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

abstract class MultiprocessComand extends Command
{
    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    protected $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    protected $userService;

    /**
     * @var bool
     */
    protected $hasProgressBar;

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar;

    protected OutputInterface $output;
    private bool $dryRun;
    private int $maxProcesses;

    /**
     * @var Process[]
     */
    private $processes;

    /**
     * @var string
     */
    private mixed $user;
    private int $iterationCount;
    private string $environment;

    public function __construct(
        string $name = null,
        PermissionResolver $permissionResolver,
        UserService $userService,
    )
    {
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->dryRun = false;
        $this->processes = [];

        parent::__construct($name);
    }

    public function configure(): void
    {
        $this->addOption(
            'user',
            'u',
            InputOption::VALUE_REQUIRED,
            'Ibexa DXP username',
            'admin'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run the converter without writing anything to the database'
            )
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Disable the progress bar.'
            )
            ->addOption(
                'processes',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of child processes to run in parallel for iterations, if set to "auto" it will set to number of CPU cores -1, set to "1" or "0" to disable [default: "auto"]',
                1
            )
                ->addOption(
                'iteration-count',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of objects to process in a single iteration. Set to avoid using too much memory [default: 10000]',
                10000
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->user = (string) $input->getOption('user');
        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUserByLogin($this->user)
        );

        $this->environment = (string) $input->getOption("env");

        if ($input->getOption('dry-run')) {
            $this->dryRun = true;
        }
        if ($this->isDryRun() && !$this->isChildProcess()) {
            $output->writeln('Running in dry-run mode. No changes will actually be written to database');
        }

        $this->maxProcesses = (int) $input->getOption('processes');
        if ($this->maxProcesses < 1) {
            throw new RuntimeException('Invalid value for "--processes" given');
        }

        $this->iterationCount = (int) $input->getOption('iteration-count');
        if ($this->iterationCount < 1) {
            throw new RuntimeException('Invalid value for "--processes" given');
        }

        $this->hasProgressBar = !$this->isChildProcess() && !$input->getOption('no-progress');

        $this->output = $output;

        if ($this->isChildProcess()) {
            $cursor = $this->constructCursorFromInputOptions();
            $this->processData($cursor);
        } else {
            $this->output->writeln("Processing " . $this->getObjectCount() . " items.");
            $this->output->writeln("Using " . $this->getMaxProcesses() . " concurrent processes and processing " . $this->getIterationCount() . " items per iteration");


            $this->startProgressBar();

            $this->iterate();
            $this->waitForAllChildren();
            $this->completed();
        }

        return self::SUCCESS;
    }

    abstract protected function getObjectCount(): int;
    abstract protected function processData(mixed $cursor);
    abstract protected function constructCursorFromInputOptions(): mixed;
    abstract protected function addChildProcessArguments(mixed $cursor): array;
    abstract protected function isChildProcess(): bool;
    abstract protected function iterate(): void;
    abstract protected function completed(): void;

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function getMaxProcesses(): int
    {
        return $this->maxProcesses;
    }

    public function getIterationCount(): int
    {
        return $this->iterationCount;
    }

    protected function doFork(): bool
    {
        return $this->maxProcesses > 1;
    }


    protected function waitForAvailableProcessSlot()
    {
        if (!$this->processSlotAvailable()) {
            $this->waitForChild();
        }
    }

    protected function processSlotAvailable(): bool
    {
        return \count($this->processes) < $this->getMaxProcesses();
    }

    private function waitForChild(): void
    {
        $childEnded = false;
        while (!$childEnded) {
            foreach ($this->processes as $pid => $p) {
                $process = $p['process'];
                $itemCount = $p['itemCount'];

                if (!$process->isRunning()) {
                    $this->output->write($process->getIncrementalOutput());
                    $this->output->write($process->getIncrementalErrorOutput());
                    $childEnded = true;
                    $exitStatus = $process->getExitCode();
                    if ($exitStatus !== 0) {
                        throw new RuntimeException(sprintf('Child process ended with status code %d. Terminating', $exitStatus));
                    }
                    unset($this->processes[$pid]);
                    $this->advanceProgressBar($itemCount);
                    break;
                }
                $this->output->write($process->getIncrementalOutput());
                $this->output->write($process->getIncrementalErrorOutput());
            }
            if (!$childEnded) {
                sleep(1);
            }
        }
    }

    protected function waitForAllChildren(): void
    {
        while (count($this->processes) > 0) {
            $this->waitForChild();
        }
        $this->finishProgressBar();
    }

    protected function createChildProcess(mixed $cursor, int $itemCount)
    {
        if ($this->doFork()) {
            $this->waitForAvailableProcessSlot();

            $phpBinaryFinder = new PhpExecutableFinder();
            $phpBinaryPath = $phpBinaryFinder->find();

            $arguments =[
                $phpBinaryPath,
                'bin/console',
                $this->getName(),
                "--user=$this->user",
            ];

            $arguments[] = '--env=' . $this->environment;

            if ($this->isDryRun()) {
                $arguments[] = '--dry-run';
            }
            if ($this->output->isVerbose()) {
                $arguments[] = '-v';
            } elseif ($this->output->isVeryVerbose()) {
                $arguments[] = '-vv';
            } elseif ($this->output->isDebug()) {
                $arguments[] = '-vvv';
            }

            $arguments = array_merge($arguments, $this->addChildProcessArguments($cursor));

            $process = new Process($arguments);
            $process->start();
            $this->processes[$process->getPid()] = [
                'process' => $process,
                'itemCount' => $itemCount,
            ];
        } else {
            $this->processData($cursor);
            $this->advanceProgressBar($itemCount);
        }
    }

    private function startProgressBar()
    {
        if ($this->hasProgressBar) {
            $this->progressBar = new ProgressBar($this->output, $this->getObjectCount());
            $this->progressBar->setFormat('very_verbose');
            $this->progressBar->start();
        }
    }

    protected function advanceProgressBar($step)
    {
        if ($this->hasProgressBar) {
            $this->progressBar->advance($step);
        }
    }

    protected function finishProgressBar()
    {
        if ($this->hasProgressBar) {
            $this->progressBar->finish();
        }
    }

}
