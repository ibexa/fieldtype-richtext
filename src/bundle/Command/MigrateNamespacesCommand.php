<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\FieldTypeRichText\Persistence\Legacy\ContentModelGateway as Gateway;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateNamespacesCommand extends AbstractMultiProcessComand
{
    protected static $defaultName = 'ibexa:migrate:richtext-namespaces';

    private Gateway $gateway;

    private ?int $cursorStart;

    private ?int $cursorStop;

    private ?int $objectCount;

    private int $convertDone;

    private int $convertSkipped;

    public function __construct(
        PermissionResolver $permissionResolver,
        UserService $userService,
        Gateway $gateway
    ) {
        $this->objectCount = null;
        $this->convertDone = 0;
        $this->convertSkipped = 0;
        parent::__construct(null, $permissionResolver, $userService);
        $this->gateway = $gateway;
    }

    public function configure(): void
    {
        parent::configure();

        $this
            ->setName(self::$defaultName)
            ->addOption(
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
        if (($this->cursorStart === null) xor ($this->cursorStop === null)) {
            throw new RuntimeException('The options --cursor-start and -cursor-stop are only for internal use !');
        }

        parent::execute($input, $output);

        return self::SUCCESS;
    }

    protected function getObjectCount(): int
    {
        if ($this->objectCount === null) {
            $this->output->writeln('Fetching number of objects to process. This might take several minutes if you have many records in ezcontentobject_attribute table.');
            $this->objectCount = $this->gateway->countRichtextAttributes();
        }

        return $this->objectCount;
    }

    protected function iterate(): void
    {
        $limit = $this->getIterationCount();
        $cursor = [
            'start' => -1,
            'stop' => null,
        ];

        $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
        $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        while ($cursor['stop'] !== null) {
            $this->createChildProcess($cursor, count($contentAttributeIDs));

            $cursor['start'] = $cursor['stop'];
            $contentAttributeIDs = $this->gateway->getContentObjectAttributeIds($cursor['start'], $limit);
            $cursor['stop'] = $this->getNextCursor($contentAttributeIDs);
        }
    }

    protected function completed(): void
    {
        $this->output->writeln(PHP_EOL . 'Completed');
        $this->output->writeln("Converted $this->convertDone field attributes(s)");
        $this->output->writeln("Skipped $this->convertSkipped field attributes(s) which already had correct namespaces");
    }

    protected function getNextCursor(array $contentAttributeIDs): ?int
    {
        $lastId = count($contentAttributeIDs) > 0 ? end($contentAttributeIDs)['id'] : null;

        return $lastId;
    }

    protected function processData($cursor)
    {
        $this->updateNamespacesInColumns($cursor['start'], $cursor['stop']);
        if ($this->isChildProcess()) {
            $this->output->writeln("Converted:$this->convertDone");
            $this->output->writeln("Skipped:$this->convertSkipped");
        }
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

    protected function processIncrementalOutput(string $output): void
    {
        if ($output !== '') {
            $lines = explode(PHP_EOL, $output);
            foreach ($lines as $line) {
                if (strpos($line, 'Converted:') === 0) {
                    $this->convertDone += (int) substr($line, strpos($line, ':') + 1);
                } elseif (strpos($line, 'Skipped:') === 0) {
                    $this->convertSkipped += (int) substr($line, strpos($line, ':') + 1);
                } elseif ($line !== '') {
                    $this->output->writeln($line);
                }
            }
        }
    }

    protected function processIncrementalErrorOutput(string $output): void
    {
        $this->output->write($output);
    }

    public static function migrateNamespaces(string $xmlText)
    {
        $xmlText = str_replace('xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml"', 'xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"', $xmlText);
        $xmlText = str_replace('xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"', 'xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"', $xmlText);
        $xmlText = str_replace('ezxhtml:class="ez-embed-type-image"', 'ezxhtml:class="ibexa-embed-type-image"', $xmlText);
        $xmlText = str_replace('xmlns:ez="http://ez.no/xmlns/ezpublish/docbook"', 'xmlns:ez="http://ibexa.co/xmlns/ezpublish/docbook"', $xmlText);
        $xmlText = str_replace('xmlns:a="http://ez.no/xmlns/annotation"', 'xmlns:a="http://ibexa.co/xmlns/annotation"', $xmlText);
        $xmlText = str_replace('xmlns:m="http://ez.no/xmlns/module"', 'xmlns:m="http://ibexa.co/xmlns/module"', $xmlText);

        return $xmlText;
    }

    protected function updateNamespacesInColumns(int $contentAttributeIdStart, int $contentAttributeIdStop): void
    {
        $contentAttributes = $this->gateway->getContentObjectAttributes($contentAttributeIdStart, $contentAttributeIdStop);

        foreach ($contentAttributes as $contentAttribute) {
            //$orgString = $contentAttribute['data_text'];
            $newXml = self::migrateNamespaces($contentAttribute['data_text']);

            if ($newXml !== $contentAttribute['data_text']) {
                ++$this->convertDone;
                if (!$this->isDryRun()) {
                    $this->gateway->updateContentObjectAttribute($newXml, $contentAttribute['contentobject_id'], $contentAttribute['id'], $contentAttribute['version'], $contentAttribute['language_code']);
                }
            } else {
                ++$this->convertSkipped;
            }
        }
    }
}
