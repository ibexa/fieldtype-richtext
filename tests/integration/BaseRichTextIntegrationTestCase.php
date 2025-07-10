<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;

abstract class BaseRichTextIntegrationTestCase extends IbexaKernelTestCase
{
    private const string FIELD_DEFINITION_IDENTIFIER = 'contents';
    private const string DEFAULT_LANGUAGE_CODE = 'eng-US';

    private const string FIELD_TYPE_IDENTIFIER = 'ibexa_richtext';

    protected ContentService $contentService;

    protected ContentTypeService $contentTypeService;

    protected function setUp(): void
    {
        $this->contentService = self::getContentService();
        $this->contentTypeService = self::getContentTypeService();
        self::setAdministratorUser();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createRichTextContentType(): ContentType
    {
        $createStruct = $this->contentTypeService->newContentTypeCreateStruct('richtext_type');
        $createStruct->mainLanguageCode = self::DEFAULT_LANGUAGE_CODE;
        $createStruct->names = [$createStruct->mainLanguageCode => 'RichText type'];

        $createStruct->addFieldDefinition($this->createRichTextFieldDefinitionCreateStruct());

        $contentGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $this->contentTypeService->createContentType(
            $createStruct,
            [$contentGroup]
        );

        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $this->contentTypeService->loadContentType($contentTypeDraft->id);
    }

    private function createRichTextFieldDefinitionCreateStruct(): FieldDefinitionCreateStruct
    {
        $fieldCreate = $this->contentTypeService->newFieldDefinitionCreateStruct(
            self::FIELD_DEFINITION_IDENTIFIER,
            self::FIELD_TYPE_IDENTIFIER
        );
        $fieldCreate->names = [self::DEFAULT_LANGUAGE_CODE => 'Contents'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = true;

        return $fieldCreate;
    }

    protected static function assertRichTextFieldValue(string $contents, Content $folder): void
    {
        $field = $folder->getField(self::FIELD_DEFINITION_IDENTIFIER);
        self::assertNotNull($field, 'Missing field with identifier: ' . self::FIELD_DEFINITION_IDENTIFIER);

        $value = $field->getValue();
        self::assertInstanceOf(Value::class, $value);

        $xml = $value->xml->saveXML();
        self::assertIsString($xml);
        self::assertSame($contents, trim($xml));
    }
}
