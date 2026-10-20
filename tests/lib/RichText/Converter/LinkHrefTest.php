<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Converter\LinkHref;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\Converter\LinkHref
 */
final class LinkHrefTest extends TestCase
{
    private const SECTION_OPEN_TAG = '<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">';

    /**
     * @return array<string, array{string, string}>
     */
    public static function providerConvert(): array
    {
        return [
            'square brackets in path' => [
                'https://example.com/foo[bar]',
                'https://example.com/foo%5Bbar%5D',
            ],
            'square brackets in query' => [
                'https://example.com/?filter[tags][]=foo',
                'https://example.com/?filter%5Btags%5D%5B%5D=foo',
            ],
            'square brackets in fragment' => [
                'https://example.com/#foo[bar]',
                'https://example.com/#foo%5Bbar%5D',
            ],
            'percent sign not followed by hex digits' => [
                'https://example.com/100%/off%2',
                'https://example.com/100%25/off%252',
            ],
            'number sign in fragment' => [
                'https://example.com/#foo#bar',
                'https://example.com/#foo%23bar',
            ],
            'relative URL' => [
                '/foo[bar]',
                '/foo%5Bbar%5D',
            ],
            'IP literal in authority is untouched' => [
                'http://[::1]:8080/foo',
                'http://[::1]:8080/foo',
            ],
            'valid percent-encoding is untouched' => [
                'https://example.com/foo%5Bbar%5d%20baz',
                'https://example.com/foo%5Bbar%5d%20baz',
            ],
            'characters accepted by anyURI are untouched' => [
                'https://example.com/a b{c}|d^e`f\\g&amp;h&quot;i&lt;j',
                'https://example.com/a b{c}|d^e`f\\g&amp;h&quot;i&lt;j',
            ],
            'internal link is untouched' => ['#foo', '#foo'],
            'ezurl link is untouched' => ['ezurl://95#foo', 'ezurl://95#foo'],
            'ezlocation link is untouched' => ['ezlocation://42', 'ezlocation://42'],
            'mailto link is untouched' => ['mailto:foo@example.com', 'mailto:foo@example.com'],
        ];
    }

    /**
     * @dataProvider providerConvert
     */
    public function testConvert(
        string $inputHref,
        string $expectedHref
    ): void {
        $converter = new LinkHref();

        $outputDocument = $converter->convert($this->createDocument($inputHref));

        $expectedOutputDocument = $this->createDocument($expectedHref);
        self::assertEquals($expectedOutputDocument, $outputDocument);

        self::assertEquals(
            $expectedOutputDocument,
            $converter->convert($this->createDocument($expectedHref)),
            'Conversion should be idempotent'
        );
    }

    public function testConvertEzLink(): void
    {
        $xml = self::SECTION_OPEN_TAG
            . '<para><ezembed><ezlink xlink:href="https://example.com/foo[bar]"/></ezembed></para>'
            . '</section>';

        $outputDocument = (new LinkHref())->convert($this->loadXml($xml));

        self::assertEquals(
            $this->loadXml(str_replace('foo[bar]', 'foo%5Bbar%5D', $xml)),
            $outputDocument
        );
    }

    private function createDocument(string $href): DOMDocument
    {
        return $this->loadXml(
            self::SECTION_OPEN_TAG
            . sprintf('<para><link xlink:href="%s">Lorem ipsum</link></para>', $href)
            . '</section>'
        );
    }

    private function loadXml(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document;
    }
}
