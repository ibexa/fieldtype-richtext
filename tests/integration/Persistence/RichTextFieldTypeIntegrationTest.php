<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Persistence;

use Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage as UrlGateway;
use Ibexa\Core\Tests\FieldType\BaseIntegrationTest;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway\DoctrineStorage;
use Ibexa\FieldTypeRichText\FieldType\RichText\Type;
use Ibexa\FieldTypeRichText\Persistence\Legacy\RichTextFieldValueConverter;
use Ibexa\FieldTypeRichText\RichText;

/**
 * Integration test for legacy storage field types.
 *
 * This abstract base test case is supposed to be the base for field type
 * integration tests. It basically calls all involved methods in the field type
 * ``Converter`` and ``Storage`` implementations. Fo get it working implement
 * the abstract methods in a sensible way.
 *
 * The following actions are performed by this test using the custom field
 * type:
 *
 * - Create a new content type with the given field type
 * - Load create content type
 * - Create content object of new content type
 * - Load created content
 * - Copy created content
 * - Remove copied content
 *
 * @group integration
 */
class RichTextFieldTypeIntegrationTest extends BaseIntegrationTest
{
    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName()
    {
        return 'ezrichtext';
    }

    /**
     * Get handler with required custom field types registered.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Handler
     */
    public function getCustomHandler()
    {
        $inputHandler = new RichText\InputHandler(
            new RichText\DOMDocumentFactory(),
            new RichText\ConverterDispatcher([]),
            new RichText\Normalizer\Aggregate(),
            new RichText\Validator\ValidatorDispatcher([
                'http://docbook.org/ns/docbook' => null,
            ]),
            new RichText\Validator\ValidatorAggregate([
                new RichText\Validator\Validator([
                    $this->getAbsolutePath('src/bundle/Resources/richtext/schemas/docbook/ezpublish.rng'),
                    $this->getAbsolutePath('src/bundle/Resources/richtext/schemas/docbook/docbook.iso.sch.xsl'),
                ]),
            ]),
            new RichText\RelationProcessor()
        );

        $fieldType = new Type($inputHandler);
        $fieldType->setTransformationProcessor($this->getTransformationProcessor());

        $urlGateway = new UrlGateway($this->getDatabaseConnection());

        return $this->getHandler(
            'ezrichtext',
            $fieldType,
            new RichTextFieldValueConverter(),
            new RichTextStorage(
                new DoctrineStorage(
                    $urlGateway,
                    $this->getDatabaseConnection()
                )
            )
        );
    }

    /**
     * Returns the FieldTypeConstraints to be used to create a field definition
     * of the FieldType under test.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldTypeConstraints
     */
    public function getTypeConstraints()
    {
        return new FieldTypeConstraints();
    }

    /**
     * Get field definition data values.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function getFieldDefinitionData()
    {
        return [
            // The ezrichtext field type does not have any special field definition
            // properties
            ['fieldType', 'ezrichtext'],
            [
                'fieldTypeConstraints',
                new FieldTypeConstraints(),
            ],
        ];
    }

    /**
     * Get initial field value.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     */
    public function getInitialValue()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <title>This is a heading.</title>
  <para>This is a paragraph.</para>
</section>
';

        return new FieldValue(
            [
                'data' => $xml,
                'externalData' => null,
                'sortKey' => null,
            ]
        );
    }

    /**
     * Get update field value.
     *
     * Use to update the field
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     */
    public function getUpdatedValue()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <title>This is an updated heading.</title>
  <para>This is an updated paragraph.</para>
</section>
';

        return new FieldValue(
            [
                'data' => $xml,
                'externalData' => null,
                'sortKey' => null,
            ]
        );
    }

    /**
     * @param string $relativePath
     *
     * @return string
     */
    protected function getAbsolutePath($relativePath)
    {
        return self::getInstallationDir() . '/' . $relativePath;
    }

    /**
     * @return string
     */
    protected static function getInstallationDir()
    {
        static $installDir = null;
        if ($installDir === null) {
            $config = require 'config.php';
            $installDir = $config['install_dir'];
        }

        return $installDir;
    }
}
