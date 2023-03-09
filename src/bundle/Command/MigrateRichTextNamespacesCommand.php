<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Command;

use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Handler;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrateRichTextNamespacesCommand extends Command
{
    protected static $defaultName = 'ibexa:migrate:richtext-namespaces';

    private Handler $handler;

    private TagAwareAdapterInterface $cache;

    /** @var array<string, string> */
    private array $xmlNamespacesMap;

    /**
     * @param array<string, string> $xmlNamespacesMap
     */
    public function __construct(
        Handler $handler,
        array $xmlNamespacesMap,
        TagAwareAdapterInterface $cache
    ) {
        parent::__construct();

        $this->handler = $handler;
        $this->xmlNamespacesMap = $xmlNamespacesMap;
        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Starting namespaces migration process...');

        $replacedNamespaces = $this->handler->migrateXMLNamespaces($this->xmlNamespacesMap);

        if ($replacedNamespaces > 0) {
            $io->success("Updated $replacedNamespaces field attributes(s)");

            $this->cache->clear();

            return self::SUCCESS;
        }

        $io->error('No namespaces to convert');

        return self::FAILURE;
    }
}
