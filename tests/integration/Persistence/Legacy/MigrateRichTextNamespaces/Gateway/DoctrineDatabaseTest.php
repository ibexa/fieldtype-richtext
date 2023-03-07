<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Tests\Integration\FieldTypeRichText\BaseRichTextIntegrationTestCase;

/**
 * @covers \Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\DoctrineDatabase
 */
final class DoctrineDatabaseTest extends BaseRichTextIntegrationTestCase
{
    private GatewayInterface $gateway;

    /**
     * @var array<string, string>
     */
    private array $xmlNamespacesMigrationMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = self::getServiceByClassName(GatewayInterface::class);
        $this->xmlNamespacesMigrationMap = $this->getXmlNamespacesMigrationMapParameter();
        self::setAdministratorUser();
    }

    /**
     * @dataProvider provideDataForTestReplaceDataTextAttributeValues
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testReplaceDataTextAttributeValues(string $expected, string $contents): void
    {
        $folder = $this->createRichTextContent($contents);

        // sanity check
        self::assertRichTextFieldValue($contents, $folder);

        self::assertGreaterThan(
            0,
            $this->gateway->migrate($this->xmlNamespacesMigrationMap)
        );

        $this->invalidateContentItemPersistenceCache(
            $folder->id,
            $folder->getVersionInfo()->versionNo
        );
        $folder = $this->contentService->loadContent($folder->id);

        self::assertRichTextFieldValue($expected, $folder);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public function provideDataForTestReplaceDataTextAttributeValues(): iterable
    {
        yield [
            <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ibexa.co/xmlns/annotation" xmlns:m="http://ibexa.co/xmlns/module" xmlns:ez="http://ibexa.co/xmlns/dxp/docbook" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>RichText namespace migration test</para>
</section>
XML,
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ez.no/xmlns/annotation" xmlns:m="http://ez.no/xmlns/module" xmlns:ez="http://ez.no/xmlns/ezpublish/docbook" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>RichText namespace migration test</para>
</section>
XML,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getXmlNamespacesMigrationMapParameter(): array
    {
        /** @var array<string, string> $xmlNamespacesMigrationMap */
        $xmlNamespacesMigrationMap = self::getContainer()
            ->getParameter('ibexa.field_type.rich_text.namespaces_migration_map');

        return $xmlNamespacesMigrationMap;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createRichTextContent(string $description): Content
    {
        $folderCreateStruct = $this->contentService->newContentCreateStruct(
            $this->createRichTextContentType(),
            'eng-US'
        );
        $folderCreateStruct->setField('contents', $description);

        $contentDraft = $this->contentService->createContent($folderCreateStruct);

        return $this->contentService->publishVersion($contentDraft->getVersionInfo());
    }

    private function invalidateContentItemPersistenceCache(int $contentId, int $versionNo): void
    {
        /** @var \Symfony\Component\Cache\Adapter\TagAwareAdapter $cache */
        $cache = self::getContainer()->get('ibexa.cache_pool');
        $cacheIdentifierGenerator = self::getServiceByClassName(CacheIdentifierGeneratorInterface::class);

        $cache->invalidateTags([
            $cacheIdentifierGenerator->generateTag(
                'content_version',
                [$contentId, $versionNo]
            ),
        ]);
    }
}
