<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException;
use PHPUnit\Framework\TestCase;

class DOMDocumentFactoryTest extends TestCase
{
    /**
     * @var \EzSystems\EzPlatformRichText\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->domDocumentFactory = new DOMDocumentFactory();
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\RichText\DOMDocumentFactory::loadXMLString
     */
    public function testLoadXMLString(): void
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
</section>
EOT;

        $doc = $this->domDocumentFactory->loadXMLString($xml);

        $this->assertInstanceOf(DOMDocument::class, $doc);
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\RichText\DOMDocumentFactory::loadXMLString
     */
    public function testLoadXMLStringThrowsInvalidXmlException(): void
    {
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Argument \'$xmlString\' is invalid: Start tag expected, \'<\' not found');

        $this->domDocumentFactory->loadXMLString('This is not XML');
    }
}

class_alias(DOMDocumentFactoryTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\DOMDocumentFactoryTest');
