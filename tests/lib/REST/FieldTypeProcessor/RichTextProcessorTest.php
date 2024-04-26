<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\REST\FieldTypeProcessor;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\FieldTypeRichText\REST\FieldTypeProcessor\RichTextProcessor;
use PHPUnit\Framework\TestCase;

class RichTextProcessorTest extends TestCase
{
    public function testPostProcessValueHash()
    {
        $processor = $this->getProcessor();

        $outputValue = [
            'xml' => <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para>Foobar</para>
</section>
EOT
        ];
        $processedOutputValue = $outputValue;
        $processedOutputValue['xhtml5edit'] = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
    <h1>Some text</h1>
    <p>Foobar</p>
</section>

EOT;

        $convertedDocument = new DOMDocument();
        $convertedDocument->loadXML($processedOutputValue['xhtml5edit']);

        $this->converter
            ->expects(self::once())
            ->method('convert')
            ->with(self::isInstanceOf('DOMDocument'))
            ->willReturn($convertedDocument);

        self::assertEquals(
            $processedOutputValue,
            $processor->postProcessValueHash($outputValue)
        );
    }

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\Converter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $converter;

    /**
     * @return \Ibexa\FieldTypeRichText\REST\FieldTypeProcessor\RichTextProcessor
     */
    protected function getProcessor()
    {
        $this->converter = $this->createMock(Converter::class);

        return new RichTextProcessor($this->converter);
    }
}

class_alias(RichTextProcessorTest::class, 'EzSystems\Tests\EzPlatformRichText\REST\FieldTypeProcessor\RichTextProcessorTest');
