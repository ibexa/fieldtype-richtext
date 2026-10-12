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
    private const string VALIDATE_RICHTEXT_XML = 'validate_richtext_xml';

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

    public function testValidateRichTextXmlSchemas(): void
    {
        $this->assertToolInputSchemaEqualsJsonFile(
            self::VALIDATE_RICHTEXT_XML,
            __DIR__ . '/Fixture/validate_richtext_xml/input_schema.json',
        );
        $this->assertToolOutputSchemaEqualsJsonFile(
            self::VALIDATE_RICHTEXT_XML,
            __DIR__ . '/Fixture/validate_richtext_xml/output_schema.json',
        );
    }

    /**
     * @dataProvider provideForValidateRichTextXml
     *
     * @param list<string> $expectedErrors
     */
    public function testValidateRichTextXml(string $xml, array $expectedErrors): void
    {
        $result = $this->callTool(self::VALIDATE_RICHTEXT_XML, ['xml' => $xml], validateOutputSchema: true);

        self::assertSame(
            ['valid' => $expectedErrors === [], 'errors' => $expectedErrors],
            (array)$result->structuredContent,
        );
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideForValidateRichTextXml(): iterable
    {
        yield 'valid DocBook' => [self::DOCBOOK_SECTION_START . '<para>Hello world</para></section>', []];

        yield 'valid XHTML5 edit' => [
            '<?xml version="1.0" encoding="UTF-8"?>'
                . '<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit"><p>a&nbsp;b</p></section>',
            [],
        ];

        yield 'link to an existing location' => [
            self::DOCBOOK_SECTION_START . '<para><link xlink:href="ezlocation://2">Home</link></para></section>',
            [],
        ];

        yield 'links to missing targets' => [
            self::DOCBOOK_SECTION_START . '<para>'
                . '<link xlink:href="ezcontent://999999">c</link>'
                . '<link xlink:href="ezlocation://999999">l</link>'
                . '<link xlink:href="ezremote://missing-remote-id">r</link>'
                . '</para></section>',
            [
                'Invalid link "ezcontent://999999": cannot find target content',
                'Invalid link "ezlocation://999999": cannot find target Location',
                'Invalid link "ezremote://missing-remote-id": cannot find target content',
            ],
        ];

        yield 'element outside the RichText schema' => [
            self::DOCBOOK_SECTION_START . '<not-a-docbook-element>Nope</not-a-docbook-element></section>',
            ['Error in 1:0: Element section has extra content: not-a-docbook-element'],
        ];

        yield 'malformed XML' => ['<section><para>unclosed', ['Premature end of data in tag para']];

        yield 'root element without namespace' => [
            '<section><para>x</para></section>',
            ['The root element has no namespace, ' . self::SUPPORTED_NAMESPACES],
        ];

        yield 'unsupported root element namespace' => [
            '<html xmlns="http://www.w3.org/1999/xhtml"><p>x</p></html>',
            ['Unsupported root element namespace "http://www.w3.org/1999/xhtml", ' . self::SUPPORTED_NAMESPACES],
        ];

        yield 'blank document' => ["  \n ", ['The XML document is empty.']];
    }

    public function testConvertedXmlIsValid(): void
    {
        $result = $this->callTool(
            self::CONVERT_TO_RICHTEXT_XML,
            ['html' => '<p>Round trip</p>'],
            validateOutputSchema: true,
        );
        $xml = $result->structuredContent->xml;

        $result = $this->callTool(self::VALIDATE_RICHTEXT_XML, ['xml' => $xml], validateOutputSchema: true);

        self::assertSame(['valid' => true, 'errors' => []], (array)$result->structuredContent);
    }

    public function testValidateRichTextXmlRequiresXml(): void
    {
        $error = $this->callToolJsonRpcError(self::VALIDATE_RICHTEXT_XML);

        self::assertSame(Error::INVALID_PARAMS, $error->code);
    }
}
