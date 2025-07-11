<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Repository;

use DirectoryIterator;
use Doctrine\DBAL\ParameterType;
use DOMDocument;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\URL\Gateway\DoctrineDatabase;
use Ibexa\Core\Repository\Values\Content\Relation;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value as RichTextValue;
use Ibexa\Tests\Integration\Core\Repository\FieldType\RelationSearchBaseIntegrationTestTrait;
use Ibexa\Tests\Integration\Core\Repository\FieldType\SearchBaseIntegrationTestCase;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class RichTextFieldTypeIntegrationTest extends SearchBaseIntegrationTestCase
{
    use RelationSearchBaseIntegrationTestTrait;

    private DOMDocument $createdDOMValue;

    private DOMDocument $updatedDOMValue;

    /**
     * @param array<mixed> $data
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->createdDOMValue = new DOMDocument();
        $this->createdDOMValue->loadXML(
            <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para><link xlink:href="ezlocation://58" xlink:show="none">link1</link></para>
    <para><link xlink:href="ezcontent://54" xlink:show="none">link2</link> <ezembedinline xlink:href="ezlocation://60" view="embed" xml:id="embed-id-1" ezxhtml:class="embed-class" ezxhtml:align="left"></ezembedinline></para>
</section>
EOT
        );

        $this->updatedDOMValue = new DOMDocument();
        $this->updatedDOMValue->loadXML(
            <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para><link xlink:href="ezlocation://60" xlink:show="none">link1</link></para>
    <para><link xlink:href="ezcontent://56" xlink:show="none">link2</link></para>
    <ezembed xlink:href="ezcontent://54" view="embed" xml:id="embed-id-1" ezxhtml:class="embed-class" ezxhtml:align="left">
      <ezconfig>
        <ezvalue key="size">medium</ezvalue>
        <ezvalue key="offset">10</ezvalue>
        <ezvalue key="limit">5</ezvalue>
      </ezconfig>
    </ezembed>
</section>
EOT
        );

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @return \Ibexa\Core\Repository\Values\Content\Relation[]
     */
    public function getCreateExpectedRelations(Content $content): array
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'type' => Relation::LINK,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(56),
                ]
            ),
            new Relation(
                [
                    'type' => Relation::LINK,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(54),
                ]
            ),
            new Relation(
                [
                    'type' => Relation::EMBED,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(58),
                ]
            ),
        ];
    }

    /**
     * @return \Ibexa\Core\Repository\Values\Content\Relation[]
     */
    public function getUpdateExpectedRelations(Content $content): array
    {
        $contentService = $this->getRepository()->getContentService();

        return [
            new Relation(
                [
                    'type' => Relation::LINK,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(58),
                ]
            ),
            new Relation(
                [
                    'type' => Relation::LINK,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(56),
                ]
            ),
            new Relation(
                [
                    // @todo Won't be possible to add before we break how we store relations with legacy kernel.
                    //'sourceFieldDefinitionIdentifier' => 'data',
                    'type' => Relation::EMBED,
                    'sourceContentInfo' => $content->contentInfo,
                    'destinationContentInfo' => $contentService->loadContentInfo(54),
                ]
            ),
        ];
    }

    /**
     * Get name of tested field type.
     */
    public function getTypeName(): string
    {
        return 'ibexa_richtext';
    }

    /**
     * Get expected settings schema.
     *
     * @return array<mixed>
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @return array<mixed>
     */
    public function getValidFieldSettings(): array
    {
        return [];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @return array<string, int>
     */
    public function getInvalidFieldSettings(): array
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * Get expected validator schema.
     *
     * @return array<mixed>
     */
    public function getValidatorSchema(): array
    {
        return [];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @return array<mixed>
     */
    public function getValidValidatorConfiguration(): array
    {
        return [];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @return array<string, array<string, int>>
     */
    public function getInvalidValidatorConfiguration(): array
    {
        return [
            'unknown' => ['value' => 23],
        ];
    }

    /**
     * Get initial field data for valid object creation.
     */
    public function getValidCreationFieldData(): RichTextValue
    {
        return new RichTextValue($this->createdDOMValue);
    }

    /**
     * Get name generated by the given field type (either via Nameable or fieldType->getName()).
     */
    public function getFieldName(): string
    {
        return 'link1 link2';
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     */
    public function assertFieldDataLoadedCorrect(Field $field): void
    {
        self::assertInstanceOf(
            RichTextValue::class,
            $field->value
        );

        $this->assertPropertiesCorrect(
            [
                'xml' => $this->createdDOMValue,
            ],
            $field->value
        );
    }

    /**
     * Get field data which will result in errors during creation.
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'Ibexa\\Contracts\\Core\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array<array<mixed>>
     */
    public function provideInvalidCreationFieldData(): array
    {
        return [
            [
                new \stdClass(),
                'Ibexa\\Core\\Base\\Exceptions\\InvalidArgumentType',
            ],
        ];
    }

    /**
     * Get update field externals data.
     */
    public function getValidUpdateFieldData(): RichTextValue
    {
        return new RichTextValue($this->updatedDOMValue);
    }

    /**
     * Get externals updated field data values.
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field): void
    {
        self::assertInstanceOf(
            RichTextValue::class,
            $field->value
        );

        $this->assertPropertiesCorrect(
            [
                'xml' => $this->updatedDOMValue,
            ],
            $field->value
        );
    }

    /**
     * Get field data which will result in errors during update.
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'Ibexa\\Contracts\\Core\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array<array<mixed>>
     */
    public function provideInvalidUpdateFieldData(): array
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field): void
    {
        self::assertInstanceOf(
            RichTextValue::class,
            $field->value
        );

        $this->assertPropertiesCorrect(
            [
                'xml' => $this->createdDOMValue,
            ],
            $field->value
        );
    }

    /**
     * Get data to test to hash method.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return list<array{RichTextValue, array{xml: string|false}}>
     */
    public function provideToHashData(): array
    {
        $xml = new DOMDocument();
        $xml->loadXML(
            <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para>Foobar</para>
</section>
EOT
        );

        return [
            [
                new RichTextValue($xml),
                ['xml' => $xml->saveXML()],
            ],
        ];
    }

    /**
     * Get expectations for the fromHash call on our field value.
     *
     * @return array<array<array<string, mixed>>>
     */
    public function provideFromHashData(): array
    {
        return [
            [
                [
                    'xml' => <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para>Foobar</para>
</section>

EOT
                    ,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideFromHashData
     *
     * @todo: Requires correct registered FieldTypeService, needs to be
     *        maintained!
     */
    public function testFromHash(mixed $hash, mixed $expectedValue = null): void
    {
        $richTextValue = $this
            ->getRepository()
            ->getFieldTypeService()
            ->getFieldType($this->getTypeName())
            ->fromHash($hash);
        self::assertInstanceOf(
            RichTextValue::class,
            $richTextValue
        );

        self::assertEquals($hash['xml'], (string)$richTextValue);
    }

    /**
     * @return array<array<mixed>>
     */
    public function providerForTestIsEmptyValue(): array
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0"/>
EOT;

        return [
            [new RichTextValue()],
            [new RichTextValue($xml)],
        ];
    }

    /**
     * @return array<array<mixed>>
     */
    public function providerForTestIsNotEmptyValue(): array
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0"> </section>
EOT;
        $xml2 = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para/>
</section>
EOT;

        return [
            [
                $this->getValidCreationFieldData(),
            ],
            [new RichTextValue($xml)],
            [new RichTextValue($xml2)],
        ];
    }

    /**
     * Get data to test remote id conversion.
     *
     * This is a PHP Unit data provider
     *
     * @see testConvertReomoteObjectIdToObjectId()
     *
     * @phpstan-return list<array{string, string}>
     */
    public function providerForTestConvertRemoteObjectIdToObjectId(): array
    {
        $remoteId = '[RemoteId]';
        $objectId = '[ObjectId]';

        return [
            [
                // test link
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezremote://' . $remoteId . '#fragment">link</link>
    </para>
</section>',
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezcontent://' . $objectId . '#fragment">link</link>
    </para>
</section>
',
            ], /*, @TODO adapt and enable when embeds are implemented with remote id support
            array(
                // test embed
            '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <embed view="embed" size="medium" object_remote_id="' . $remoteId . '"/>
    </para>
</section>'
            ,
            '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <embed view="embed" size="medium" object_id="' . $objectId . '"/>
    </para>
</section>'
            ),
            array(
                // test embed-inline
            '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <embed-inline size="medium" object_remote_id="' . $remoteId . '"/>
    </para>
</section>',
                '<?xml version="1.0" encoding="utf-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <embed-inline size="medium" object_id="' . $objectId . '"/>
    </para>
</section>'
            ),
*/
        ];
    }

    /**
     * This tests the conversion from remote_object_id to object_id.
     *
     * @dataProvider providerForTestConvertRemoteObjectIdToObjectId
     */
    public function testConvertRemoteObjectIdToObjectId(string $test, string $expected): void
    {
        $repository = $this->getRepository();

        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $permissionResolver = $repository->getPermissionResolver();

        // Create Type containing RichText Field definition
        $createStruct = $contentTypeService->newContentTypeCreateStruct('test-RichText');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = 'test-RichText-abcdefghjklm9876543210';
        $createStruct->names = ['eng-GB' => 'Test'];
        $createStruct->creatorId = $permissionResolver->getCurrentUserReference()->getUserId();
        $createStruct->creationDate = $this->createDateTime();

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            'description',
            'ibexa_richtext'
        );
        $fieldCreate->names = ['eng-GB' => 'Title'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = true;
        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $testContentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        // Create a folder for tests
        $createStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );

        $createStruct->setField('name', 'Folder Link');
        $draft = $contentService->createContent(
            $createStruct,
            [$locationService->newLocationCreateStruct(2)]
        );

        $folder = $contentService->publishVersion(
            $draft->versionInfo
        );

        $objectId = $folder->versionInfo->contentInfo->id;
        $locationId = $folder->versionInfo->contentInfo->mainLocationId;
        $remoteId = $folder->versionInfo->contentInfo->remoteId;

        // Create value to be tested
        $testStruct = $contentService->newContentCreateStruct($testContentType, 'eng-GB');
        $testStruct->setField('description', str_replace('[RemoteId]', $remoteId, $test));
        $test = $contentService->createContent(
            $testStruct,
            [$locationService->newLocationCreateStruct($locationId)]
        );

        self::assertEquals(
            str_replace('[ObjectId]', (string)$objectId, $expected),
            $test->getField('description')->value->xml->saveXML()
        );
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testExternalLinkStoringAfterUpdate(): void
    {
        $testLink = 'https://support.ibexa.co/';
        $xmlDocument = $this->createXmlDocumentWithExternalLink(['https://ibexa.co/', $testLink]);
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $locationService = $repository->getLocationService();

        $testContentType = $this->createContentTypeForTestExternalLinkStoringAfterUpdate();

        $testContentCreateStruct = $contentService->newContentCreateStruct(
            $testContentType,
            'eng-GB'
        );
        $testContentCreateStruct->setField('description', $xmlDocument, 'eng-GB');
        $content = $contentService->createContent(
            $testContentCreateStruct,
            [$locationService->newLocationCreateStruct(2)]
        );
        $content = $contentService->publishVersion(
            $content->versionInfo
        );

        $xmlDocument = $this->createXmlDocumentWithExternalLink([$testLink]);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('description', $xmlDocument, 'eng-GB');
        $contentDraft = $contentService->updateContent(
            $contentService->createContentDraft($content->contentInfo)->versionInfo,
            $contentUpdateStruct
        );
        $content = $contentService->publishVersion($contentDraft->versionInfo);
        $urlIdsAfterUpdate = $this->getUrlIdsForContentObjectAttributeIdAndVersionNo(
            $content->getField('description')->id,
            $content->contentInfo->currentVersionNo
        );

        $urlId = $this->getUrlIdForLink($testLink);

        self::assertContains($urlId, $urlIdsAfterUpdate);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getUrlIdForLink(string $link): int
    {
        $connection = $this->getRawDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select(
                $connection->quoteIdentifier('id')
            )
            ->from(DoctrineDatabase::URL_TABLE)
            ->where('url = :url')
            ->setParameter('url', $link, ParameterType::STRING)
        ;

        $id = $query->executeQuery()->fetchOne();

        if ($id === false) {
            throw new NotFoundException('ezurl', $link);
        }

        return (int)$id;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function createContentTypeForTestExternalLinkStoringAfterUpdate(): ContentType
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('test-RichText');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->names = ['eng-GB' => 'Test'];

        $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
            'description',
            'ibexa_richtext'
        );
        $fieldDefinitionCreateStruct->names = ['eng-GB' => 'Title'];
        $fieldDefinitionCreateStruct->fieldGroup = 'main';
        $fieldDefinitionCreateStruct->position = 1;
        $fieldDefinitionCreateStruct->isTranslatable = true;
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType(
            $contentTypeCreateStruct,
            [$contentGroup]
        );

        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentType($contentTypeDraft->id);
    }

    /**
     * @return int[]
     *
     * @throws \ErrorException
     */
    private function getUrlIdsForContentObjectAttributeIdAndVersionNo(
        int $contentObjectAttributeId,
        int $versionNo
    ): array {
        $connection = $this->getRawDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->select(
                $connection->quoteIdentifier('url_id')
            )
            ->from(DoctrineDatabase::URL_LINK_TABLE)
            ->where('contentobject_attribute_id = :contentobject_attribute_id')
            ->andWhere('contentobject_attribute_version = :contentobject_attribute_version')
            ->setParameter('contentobject_attribute_version', $versionNo, ParameterType::INTEGER)
            ->setParameter(
                'contentobject_attribute_id',
                $contentObjectAttributeId,
                ParameterType::INTEGER
            );

        $statement = $query->executeQuery();

        return array_map(
            'intval',
            array_column($statement->fetchAllAssociative(), 'url_id')
        );
    }

    /**
     * @param string $xmlDocumentPath
     *
     * @dataProvider providerForTestCreateContentWithValidCustomTag
     */
    public function testCreateContentWithValidCustomTag($xmlDocumentPath): void
    {
        $validXmlDocument = $this->createDocument($xmlDocumentPath);
        $this->createContent(new RichTextValue($validXmlDocument));
    }

    /**
     * Data provider for testCreateContentWithValidCustomTag.
     *
     * @return list<array{(string|false)}>
     */
    public function providerForTestCreateContentWithValidCustomTag(): array
    {
        $data = [];
        $iterator = new DirectoryIterator(__DIR__ . '/_fixtures/ibexa_richtext/custom_tags/valid');
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'xml') {
                $data[] = [$fileInfo->getRealPath()];
            }
        }

        return $data;
    }

    /**
     * @param string $xmlDocumentPath
     *
     * @dataProvider providerForTestCreateContentWithInvalidCustomTag
     */
    public function testCreateContentWithInvalidCustomTag(
        $xmlDocumentPath,
        string $expectedValidationMessage
    ): void {
        try {
            $invalidXmlDocument = $this->createDocument($xmlDocumentPath);
            $this->createContent(new RichTextValue($invalidXmlDocument));
        } catch (ContentFieldValidationException $e) {
            $this->assertValidationErrorOccurs($e, $expectedValidationMessage);

            return;
        }

        self::fail("Expected ValidationError '{$expectedValidationMessage}' did not occur.");
    }

    /**
     * Data provider for testCreateContentWithInvalidCustomTag.
     *
     * @return array<list<string>>
     */
    public function providerForTestCreateContentWithInvalidCustomTag(): array
    {
        return [
            [
                __DIR__ . '/_fixtures/ibexa_richtext/custom_tags/invalid/equation.xml',
                "Validation of XML content failed:\nThe attribute 'processor' of RichText Custom Tag 'equation' cannot be empty",
            ],
            [
                __DIR__ . '/_fixtures/ibexa_richtext/custom_tags/invalid/video.xml',
                "Validation of XML content failed:\nUnknown attribute 'unknown_attribute' of RichText Custom Tag 'video'",
            ],
            [
                __DIR__ . '/_fixtures/ibexa_richtext/custom_tags/invalid/nested.xml',
                "Validation of XML content failed:\nUnknown attribute 'typo' of RichText Custom Tag 'equation'",
            ],
        ];
    }

    protected function createDocument(string $filename): DOMDocument
    {
        $document = new DOMDocument();

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $document->loadXml(file_get_contents($filename), LIBXML_NOENT);

        return $document;
    }

    /**
     * Prepare Content structure with link to deleted Location.
     *
     * @return array{\Ibexa\Contracts\Core\Repository\Values\Content\Location, \Ibexa\Contracts\Core\Repository\Values\Content\Content}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function prepareInternalLinkValidatorBrokenLinksTestCase(Repository $repository): array
    {
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // Create first content with single Language
        $primaryContent = $contentService->publishVersion(
            $this->createMultilingualContent(
                ['eng-US' => 'ContentA'],
                ['eng-US' => $this->getValidCreationFieldData()],
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );
        // Create secondary Location (to be deleted) for the first Content
        $deletedLocation = $locationService->createLocation(
            $primaryContent->contentInfo,
            $locationService->newLocationCreateStruct(60)
        );

        // Create second Content with two Languages, one of them linking to secondary Location
        $brokenContent = $contentService->publishVersion(
            $this->createMultilingualContent(
                [
                    'eng-US' => 'ContentB',
                    'eng-GB' => 'ContentB',
                ],
                [
                    'eng-US' => $this->getValidCreationFieldData(),
                    'eng-GB' => $this->getDocumentWithLocationLink($deletedLocation),
                ],
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // delete Location making second Content broken
        $locationService->deleteLocation($deletedLocation);

        return [$deletedLocation, $brokenContent];
    }

    /**
     * Test updating Content which contains links to deleted Location doesn't fail when updating not broken field only.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     */
    public function testInternalLinkValidatorIgnoresMissingRelationOnNotUpdatedField(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        [, $contentB] = $this->prepareInternalLinkValidatorBrokenLinksTestCase($repository);

        // update field w/o erroneous link to trigger validation
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('data', $this->getValidUpdateFieldData(), 'eng-US');

        $contentDraftB = $contentService->updateContent(
            $contentService->createContentDraft($contentB->contentInfo)->versionInfo,
            $contentUpdateStruct
        );

        $contentService->publishVersion($contentDraftB->versionInfo, ['eng-US']);
    }

    /**
     * Test updating Content which contains links to deleted Location fails when updating broken field.
     *
     * @throws \DOMException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testInternalLinkValidatorReturnsErrorOnMissingRelationInUpdatedField(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        [$deletedLocation, $brokenContent] = $this->prepareInternalLinkValidatorBrokenLinksTestCase(
            $repository
        );

        // update field containing erroneous link to trigger validation
        /** @var \DOMDocument $document */
        $document = $brokenContent->getField('data', 'eng-GB')?->getValue()->xml;
        $newParagraph = $document->createElement('para', 'Updated content');
        $document
            ->getElementsByTagName('section')->item(0)
            ->appendChild($newParagraph);

        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('data', new RichTextValue($document), 'eng-GB');

        $expectedValidationErrorMessage = sprintf(
            "Validation of XML content failed:\nInvalid link \"ezlocation://%s\": cannot find target Location",
            $deletedLocation->id
        );
        try {
            $contentDraftB = $contentService->updateContent(
                $contentService->createContentDraft($brokenContent->contentInfo)->versionInfo,
                $contentUpdateStruct
            );

            $contentService->publishVersion($contentDraftB->versionInfo);
        } catch (ContentFieldValidationException $e) {
            $this->assertValidationErrorOccurs($e, $expectedValidationErrorMessage);

            return;
        }

        self::fail("Expected ValidationError '{$expectedValidationErrorMessage}' didn't occur");
    }

    /**
     * @param list<string> $urls
     */
    private function createXmlDocumentWithExternalLink(array $urls): DOMDocument
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $links = '';
        foreach ($urls as $url) {
            $links .= sprintf(
                '<link xlink:href="%s" xlink:show="none" xlink:title="">%1$s</link>',
                $url
            );
        }
        $document->loadXML(
            (
                <<<XML
<?xml version="1.0" encoding="UTF-8"?>
    <section 
    xmlns="http://docbook.org/ns/docbook" 
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
    xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
    version="5.0-variant ezpublish-1.0">
        <para>
            $links
        </para>
    </section>

XML
            ),
            LIBXML_NOENT
        );

        return $document;
    }

    /**
     * Get XML Document in DocBook format, containing link to the given Location.
     */
    private function getDocumentWithLocationLink(Location $location): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML(
            <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para><link xlink:href="ezlocation://{$location->id}" xlink:show="none">link1</link></para>
</section>
XML
        );

        return $document;
    }

    protected function checkSearchEngineSupport(): void
    {
        if ($this->getSetupFactory() instanceof SetupFactory\LegacySetupFactory) {
            self::markTestSkipped(
                "'ibexa_richtext' field type is not searchable with Legacy Search Engine"
            );
        }
    }

    protected function getValidSearchValueOne(): string
    {
        return <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>caution is the path to mediocrity</para>
</section>
EOT;
    }

    protected function getSearchTargetValueOne(): string
    {
        // ensure case-insensitivity
        return strtoupper('caution is the path to mediocrity');
    }

    protected function getValidSearchValueTwo(): string
    {
        return <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>truth suffers from too much analysis</para>
</section>
EOT;
    }

    protected function getSearchTargetValueTwo(): string
    {
        // ensure case-insensitivity
        return strtoupper('truth suffers from too much analysis');
    }

    /**
     * @return array<list<string>>
     */
    protected function getFullTextIndexedFieldData(): array
    {
        return [
            ['mediocrity', 'analysis'],
        ];
    }
}
