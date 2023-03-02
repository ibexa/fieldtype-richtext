<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;

final class DoctrineDatabaseTest extends IbexaKernelTestCase
{
    private GatewayInterface $gateway;

    /**
     * @var array<string, string>
     */
    private array $xmlNamespacesMigrationMap;

    private ContentService $contentService;

    private ContentTypeService $contentTypeService;

    protected function setUp(): void
    {
        $this->gateway = self::getServiceByClassName(GatewayInterface::class);
        $this->xmlNamespacesMigrationMap = self::getContainer()
            ->getParameter('ibexa.field_type.rich_text.namespaces_migration_map');
        $this->contentService = self::getContentService();
        $this->contentTypeService = self::getContentTypeService();
        self::setAdministratorUser();
    }

    public function testReplaceDataTextAttributeValues(): void
    {
        $contents = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
            <para><emphasis role="strong">You are now ready to start your project.</emphasis></para>
        </section>
        XML;

        $folder = $this->createRichTextContent($contents);
        self::assertSame($contents, $folder->getField('contents')->getValue());

        $this->gateway->replaceDataTextAttributeValues($this->xmlNamespacesMigrationMap);
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

    protected function createRichTextContentType(): ContentType
    {
        $createStruct = $this->contentTypeService->newContentTypeCreateStruct('richtext_type');
        $createStruct->mainLanguageCode = 'eng-US';
        $createStruct->names = ['eng-US' => 'RichText type'];

        $createStruct->addFieldDefinition(
            $this->createFieldDefinition(
                'contents',
                'ezrichtext',
                'Contents',
                1)
        );

        $contentGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $this->contentTypeService->createContentType($createStruct, [$contentGroup]);

        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $this->contentTypeService->loadContentType($contentTypeDraft->id);
    }

    private function createFieldDefinition(string $fieldIdentifier, string $fieldType, string $fieldName, int $position): FieldDefinitionCreateStruct
    {
        $fieldCreate = $this->contentTypeService->newFieldDefinitionCreateStruct(
            $fieldIdentifier,
            $fieldType
        );
        $fieldCreate->names = ['eng-US' => $fieldName];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = $position;
        $fieldCreate->isTranslatable = true;

        return $fieldCreate;
    }
}
