<?php

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use eZ\Publish\API\Repository\Repository;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\FieldTypeRichText\Persistence\Legacy\ContentModelGateway as Gateway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


// https://symfony.com/doc/current/components/process.html

final class MigrateNamespacesCommand extends Command
{
    /** @var \eZ\Publish\API\Repository\Repository */
    //private $repository;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    private $userService;

    private Gateway $gateway;

    /**
     * @var bool
     */
    protected $hasProgressBar;

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar;

    private OutputInterface $output;
    private bool $dryRun;

    public function __construct(
        PermissionResolver $permissionResolver,
        UserService $userService,
        Gateway $gateway,
        /*Repository $repository*/
    )
    {
        //$this->repository = $repository;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->dryRun = false;

        parent::__construct("ibexa:migrate:richtext-namespaces");
        $this->gateway = $gateway;
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
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getOption('user');
        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUserByLogin($login)
        );

        if ($input->getOption('dry-run')) {
            $this->dryRun = true;
        }
        if ($this->dryRun) {
            $output->writeln('Running in dry-run mode. No changes will actually be written to database');
        }


        $this->hasProgressBar = !$input->getOption('no-progress');

        $this->output = $output;

        //$io = new SymfonyStyle($input, $output);

        $this->migrateNamespaces();
        return self::SUCCESS;
    }

    protected function progressBarStart($count)
    {
        if ($this->hasProgressBar) {
            $this->progressBar = new ProgressBar($this->output, $count);
            $this->progressBar->setFormat('very_verbose');
            $this->progressBar->start();
        }
    }

    protected function progressBarAdvance($step)
    {
        if ($this->hasProgressBar) {
            $this->progressBar->advance($step);
        }
    }

    protected function progressBarFinish()
    {
        if ($this->hasProgressBar) {
            $this->progressBar->finish();
        }
    }

    protected function migrateNamespaces(): void
    {
        $count = $this->gateway->countRichtextAttributes();
        $limit = 10;
        $cursor =  [
            'start' => -1,
            'stop' => null
        ];


        $this->progressBarStart($count);

        $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
        $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        while ($cursor['stop'] !== null) {
            $this->updateNamespacesInColumns($cursor['start'], $cursor['stop']);

            $cursor['start'] = $cursor['stop'];
            $this->progressBarAdvance(count($contentAttributeIDs)); //fixme -- offset ?
            $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
            $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        }
    }

    protected function getNextCursor(array $contentAttributeIDs): ?int
    {
        $lastId = count($contentAttributeIDs) > 0 ? end($contentAttributeIDs)['id'] : null;

        return $lastId;
    }

    protected function updateNamespacesInColumns(int $contentAttributeIdStart, int $contentAttributeIdStop): void
    {
        $contentAttributes = $this->gateway->getContentObjectAttributes($contentAttributeIdStart,$contentAttributeIdStop);

        foreach ($contentAttributes as $contentAttribute) {
            //var_dump("contentAttribute", $contentAttribute);
//            $contentAttribute['data_text'] = str_replace('xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"', 'xmlns:ezxhtml="http://FOOBAR.co/xmlns/dxp/docbook/xhtml"', $contentAttribute['data_text']);
//            $contentAttribute['data_text'] = str_replace( 'xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"', 'xmlns:ezcustom="http://FOOBAR.co/xmlns/dxp/docbook/custom"', $xml);
            $contentAttribute['data_text'] = str_replace('xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml', 'xmlns:ezxhtml="http://FOOBAR.co/xmlns/dxp/docbook/xhtml"', $contentAttribute['data_text']);
            $contentAttribute['data_text'] = str_replace( 'xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"', 'xmlns:ezcustom="http://FOOBAR.co/xmlns/dxp/docbook/custom"', $contentAttribute['data_text']);
            //var_dump("converted xml", $contentAttribute['data_text']);

            if (!$this->dryRun) {
                $this->gateway->updateContentObjectAttribute($contentAttribute['data_text'], $contentAttribute['contentobject_id'], $contentAttribute['id'], $contentAttribute['version'], $contentAttribute['language_code']);
            }
        }
    }
}