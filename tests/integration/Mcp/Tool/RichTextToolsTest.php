<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Mcp\Tool;

use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Mcp\Schema\JsonRpc\Error;

/**
 * @covers \Ibexa\FieldTypeRichText\Mcp\Tool\RichTextTools
 */
final class RichTextToolsTest extends AbstractRichTextToolsTestCase
{
    private const string CONVERT_TO_RICHTEXT_XML = 'convert_to_richtext_xml';

    private const string DOCBOOK_SECTION_START = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" '
        . 'version="5.0-variant ezpublish-1.0">';

    private const string CONVERTED_SECTION_START = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" '
        . 'xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" '
        . 'version="5.0-variant ezpublish-1.0">';

    private const string SUPPORTED_NAMESPACES = 'expected DocBook (http://docbook.org/ns/docbook) '
        . 'or XHTML5 edit (http://ibexa.co/namespaces/ezpublish5/xhtml5/edit).';

    public function testConvertToRichTextXmlSchemas(): void
    {
        $this->assertToolInputSchemaEqualsJsonFile(
            self::CONVERT_TO_RICHTEXT_XML,
            __DIR__ . '/Fixture/convert_to_richtext_xml/input_schema.json',
        );
        $this->assertToolOutputSchemaEqualsJsonFile(
            self::CONVERT_TO_RICHTEXT_XML,
            __DIR__ . '/Fixture/convert_to_richtext_xml/output_schema.json',
        );
    }

    /**
     * @dataProvider provideForConvertToRichTextXml
     *
     * @param array<string, string> $arguments
     */
    public function testConvertToRichTextXml(array $arguments, string $expectedXml): void
    {
        $result = $this->callTool(self::CONVERT_TO_RICHTEXT_XML, $arguments, validateOutputSchema: true);

        self::assertSame(['xml' => $expectedXml], (array)$result->structuredContent);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function provideForConvertToRichTextXml(): iterable
    {
        yield 'omitted input' => [[], Value::EMPTY_VALUE];

        yield 'blank input' => [['html' => "  \n "], Value::EMPTY_VALUE];

        yield 'plain text' => [
            ['html' => 'Hello & world'],
            self::CONVERTED_SECTION_START . '<para>Hello &amp; world</para></section>' . "\n",
        ];

        yield 'HTML fragment with an HTML named entity' => [
            ['html' => '<h2>Title</h2><p>First</p><p>a&nbsp;b</p>'],
            self::CONVERTED_SECTION_START
                . '<title ezxhtml:level="2">Title</title><para>First</para><para>a' . "\u{a0}" . 'b</para></section>' . "\n",
        ];

        yield 'complete XHTML5 edit document' => [
            [
                'html' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit"><p>Edit <strong>bold</strong></p></section>',
            ],
            self::CONVERTED_SECTION_START . '<para>Edit <emphasis role="strong">bold</emphasis></para></section>' . "\n",
        ];

        yield 'complete DocBook document' => [
            ['html' => self::DOCBOOK_SECTION_START . '<para>Hello world</para></section>'],
            str_replace('?><section', "?>\n<section", self::DOCBOOK_SECTION_START)
                . '<para>Hello world</para></section>' . "\n",
        ];
    }

    /**
     * @dataProvider provideForConvertToRichTextXmlError
     */
    public function testConvertToRichTextXmlError(string $html, string $expectedMessage): void
    {
        self::assertSame(
            $expectedMessage,
            $this->callToolErrorMessage(self::CONVERT_TO_RICHTEXT_XML, ['html' => $html]),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideForConvertToRichTextXmlError(): iterable
    {
        yield 'unclosed tag' => [
            '<p>unclosed',
            'Cannot convert the given input to RichText XML, it is not well-formed XML: '
                . 'Opening and ending tag mismatch: p and section; Premature end of data in tag section',
        ];

        yield 'HTML void element' => [
            '<p>a<br>b</p>',
            'Cannot convert the given input to RichText XML, it is not well-formed XML: '
                . 'Opening and ending tag mismatch: br and p; Opening and ending tag mismatch: p and section; '
                . 'Premature end of data in tag section',
        ];

        yield 'DocBook namespace not on the root element' => [
            '<div xmlns:d="http://docbook.org/ns/docbook"><p>x</p></div>',
            'Cannot convert the given input to RichText XML: The root element has no namespace, '
                . self::SUPPORTED_NAMESPACES,
        ];
    }

    public function testConvertToRichTextXmlRejectsNonStringInput(): void
    {
        $error = $this->callToolJsonRpcError(self::CONVERT_TO_RICHTEXT_XML, ['html' => 123]);

        self::assertSame(Error::INVALID_PARAMS, $error->code);
    }
}
