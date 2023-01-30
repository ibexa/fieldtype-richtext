<?php

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\FieldTypeRichText\Persistence\Legacy\ContentModelGateway as Gateway;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


final class MigrateNamespacesCommand extends MultiprocessComand
{
    private Gateway $gateway;
    private ?int $cursorStart;
    private ?int $cursorStop;

    public function __construct(
        PermissionResolver $permissionResolver,
        UserService $userService,
        Gateway $gateway,
    )
    {
        parent::__construct("ibexa:migrate:richtext-namespaces", $permissionResolver, $userService);
        $this->gateway = $gateway;
    }

    public function configure(): void
    {
        parent::configure();

        $this->addOption(
            'cursor-start',
            null,
            InputOption::VALUE_REQUIRED,
            'Internal option - only used for subprocesses',
            )
            ->addOption(
                'cursor-stop',
                null,
                InputOption::VALUE_REQUIRED,
                'Internal option - only used for subprocesses',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cursorStart = $input->getOption('cursor-start') !== null ? (int) $input->getOption('cursor-start') : null;
        $this->cursorStop = $input->getOption('cursor-stop') !== null ? (int) $input->getOption('cursor-stop') : null;

        // Check that both --cursor-start and cursor-start are set, or neither
        if ( ($this->cursorStart === null) xor ($this->cursorStop === null) ) {
            throw new RuntimeException('The options --cursor-start and -cursor-stop are only for internal use !');
        }


        parent::execute($input, $output);

        return self::SUCCESS;
    }

    protected function getObjectCount(): int
    {
        return $this->gateway->countRichtextAttributes();
    }

    protected function iterate(): void
    {
        $limit = $this->getIterationCount();
        $cursor =  [
            'start' => -1,
            'stop' => null
        ];

        $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
        $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        while ($cursor['stop'] !== null) {
            $this->createChildProcess($cursor, count($contentAttributeIDs));
            //$this->updateNamespacesInColumns($cursor['start'], $cursor['stop']);

            $cursor['start'] = $cursor['stop'];
            //$this->advanceProgressBar(count($contentAttributeIDs));
            $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
            $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        }
    }

    protected function completed(): void
    {
        $this->output->writeln(PHP_EOL . "Completed");
    }

    protected function getNextCursor(array $contentAttributeIDs): ?int
    {
        $lastId = count($contentAttributeIDs) > 0 ? end($contentAttributeIDs)['id'] : null;

        return $lastId;
    }

    protected function processData($cursor)
    {
        $this->updateNamespacesInColumns($cursor['start'], $cursor['stop']);
    }

    protected function constructCursorFromInputOptions(): mixed
    {
        return [
            'start' => $this->cursorStart,
            'stop' => $this->cursorStop,
        ];
    }

    protected function addChildProcessArguments($cursor): array
    {
        return [
            '--cursor-start=' . $cursor['start'],
            '--cursor-stop=' . $cursor['stop'],
        ];
    }

    protected function isChildProcess(): bool
    {
        return $this->cursorStart !== null || $this->cursorStop !== null;
    }

    protected function updateNamespacesInColumns(int $contentAttributeIdStart, int $contentAttributeIdStop): void
    {
        $contentAttributes = $this->gateway->getContentObjectAttributes($contentAttributeIdStart,$contentAttributeIdStop);

        foreach ($contentAttributes as $contentAttribute) {
            $contentAttribute['data_text'] = str_replace('xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml"', 'xmlns:ezxhtml="http://FOOBAR.co/xmlns/dxp/docbook/xhtml"', $contentAttribute['data_text']);
            $contentAttribute['data_text'] = str_replace( 'xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"', 'xmlns:ezcustom="http://FOOBAR.co/xmlns/dxp/docbook/custom"', $contentAttribute['data_text']);

            if (!$this->isDryRun()) {
                $this->gateway->updateContentObjectAttribute($contentAttribute['data_text'], $contentAttribute['contentobject_id'], $contentAttribute['id'], $contentAttribute['version'], $contentAttribute['language_code']);
            }
        }
    }
}