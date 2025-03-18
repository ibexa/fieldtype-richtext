<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Persistence\Legacy\Tests\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\FieldTypeRichText\Persistence\Legacy\RichTextFieldValueConverter;
use PHPUnit\Framework\TestCase;

/**
 * Test case for RichText converter in Legacy storage.
 *
 * @group fieldType
 * @group ezrichtext
 */
class RichTextFieldValueConverterTest extends TestCase
{
    /**
     * @var \Ibexa\FieldTypeRichText\Persistence\Legacy\RichTextFieldValueConverter
     */
    protected $converter;

    /**
     * @var string
     */
    private string $docbookString;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new RichTextFieldValueConverter();
        $this->docbookString = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
  <title>This is a heading.</title>
  <para>This is a paragraph.</para>
</section>

EOT;
    }

    protected function tearDown(): void
    {
        unset($this->docbookString);
        parent::tearDown();
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Persistence\Legacy\RichTextFieldValueConverter::toStorageValue
     */
    public function testToStorageValue(): void
    {
        $value = new FieldValue();
        $value->data = $this->docbookString;
        $storageFieldValue = new StorageFieldValue();

        $this->converter->toStorageValue($value, $storageFieldValue);
        self::assertSame($this->docbookString, $storageFieldValue->dataText);
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Persistence\Legacy\RichTextFieldValueConverter::toFieldValue
     */
    public function testToFieldValue(): void
    {
        $storageFieldValue = new StorageFieldValue();
        $storageFieldValue->dataText = $this->docbookString;
        $fieldValue = new FieldValue();

        $this->converter->toFieldValue($storageFieldValue, $fieldValue);
        self::assertSame($this->docbookString, $fieldValue->data);
    }
}
