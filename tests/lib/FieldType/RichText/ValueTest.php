<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\Value
 */
final class ValueTest extends TestCase
{
    private const XML = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para>Lorem ipsum</para></section>';

    public function testCreateFromDOMDocument(): void
    {
        $document = new DOMDocument();
        $document->loadXML(self::XML);

        $value = new Value($document);

        self::assertSame($document, $value->xml);
    }

    public function testCreateEmptyValue(): void
    {
        $value = new Value();

        self::assertSame(Value::EMPTY_VALUE, trim((string)$value));
    }
}
