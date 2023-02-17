<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

abstract class AbstractMultiProcessComand extends Command
{
    protected PermissionResolver $permissionResolver;

    protected UserService $userService;

    protected bool $hasProgressBar;

    protected ProgressBar $progressBar;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var bool
     */
    private bool $dryRun;

    /**
     * @var int
     */
    private int $maxProcesses;

    /**
     * @var \Symfony\Component\Process\Process[]
     */
    private $processes;

    /**
     * @var string
     */
    private mixed $user;

    /**
     * @var int
     */
    private int $iterationCount;

    /**
     * @var string
     */
    private string $environment;

    public function __construct(
        string $name = null,
        PermissionResolver $permissionResolver,
        UserService $userService
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->dryRun = false;
        $this->processes = [];

        parent::__construct($name);
    }

    public function configure(): void
    {
        $this
            ->addOption(
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
            )->addOption(
                'processes',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of child processes to run in parallel for iterations, if set to "auto" it will set to number of CPU cores -1, set to "1" or "0" to disable [default: "auto"]',
                1
            )->addOption(
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

        $this->environment = (string) $input->getOption('env');

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
            $this->output->writeln('Processing ' . $this->getObjectCount() . ' items.');
            $this->output->writeln('Using ' . $this->getMaxProcesses() . ' concurrent processes and processing ' . $this->getIterationCount() . ' items per iteration');

            $this->startProgressBar();

            $this->iterate();
            $this->waitForAllChildren();
            $this->completed();
        }

        return self::SUCCESS;
    }

    /**
     * This method should return the total number of items to process.
     *
     * @return int
     */
    abstract protected function getObjectCount(): int;

    /**
     * This method should process the subset of data, specified by the cursor.
     *
     * @param mixed $cursor
     *
     * @return mixed
     */
    abstract protected function processData(mixed $cursor);

    /**
     * This method is called once in every child process. It should return a cursor based on the input parameters
     * to the subprocess command.
     *
     * @return mixed
     */
    abstract protected function constructCursorFromInputOptions(): mixed;

    /**
     * This method should return the command arguments that should be added when launching a new child process. It will
     * typically be the arguments needed in order to construct the Cursor for the child process.
     *
     * @param mixed $cursor
     *
     * @return array
     */
    abstract protected function addChildProcessArguments(mixed $cursor): array;

    /**
     * The method should return true if the current process is a child process. This is typically detected using the
     * custom command arguments used when launching a child proccess.
     *
     * @return bool
     */
    abstract protected function isChildProcess(): bool;

    /**
     * This is the method that is responsible for iterating over the dataset that is being processed and split it into
     * chunks that can be processed by a child processes. In order to do that it will maintain a cursor and call
     * createChildProcess() for each chunk.
     */
    abstract protected function iterate(): void;

    /**
     * This method is called when all data has been completed successfully.
     */
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

            $arguments = [
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
