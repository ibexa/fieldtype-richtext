<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType\RichText;

use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Value
 */
final class ValueTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para>Lorem ipsum</para></section>';

        $value = new Value($xml);

        self::assertNotNull($value->xml->documentElement);
        self::assertSame('section', $value->xml->documentElement->localName);
    }

    public function testCreateEmptyValue(): void
    {
        $value = new Value();

        self::assertSame(Value::EMPTY_VALUE, trim((string)$value));
    }

    /**
     * Loading an already stored document with an invalid xml:id must not emit a libxml
     * warning, which Symfony's error handler would turn into an exception during rendering.
     */
    public function testCreateFromStringWithInvalidXmlIdDoesNotEmitWarning(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para xml:id="227">Lorem ipsum</para></section>';

        $value = new Value($xml);

        self::assertNotNull($value->xml->documentElement);
        self::assertSame('section', $value->xml->documentElement->localName);
        self::assertStringContainsString('xml:id="227"', (string)$value);
    }
}
