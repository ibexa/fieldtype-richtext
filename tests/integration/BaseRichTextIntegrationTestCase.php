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

abstract class BaseRichTextIntegrationTestCase extends IbexaKernelTestCase
{
    private const FIELD_DEFINITION_IDENTIFIER = 'contents';
    private const DEFAULT_LANGUAGE_CODE = 'eng-US';

    private const FIELD_TYPE_IDENTIFIER = 'ezrichtext';

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
        /** @var \Ibexa\FieldTypeRichText\FieldType\RichText\Value $actualValue */
        $actualValue = $folder->getField(self::FIELD_DEFINITION_IDENTIFIER)->getValue();
        self::assertSame($contents, trim($actualValue->xml->saveXML()));
    }
}
