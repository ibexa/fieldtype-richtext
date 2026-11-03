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
            'square bracket in userinfo' => [
                'https://user:pa[ss@example.com/x',
                'https://user:pa%5Bss@example.com/x',
            ],
            'percent sign in userinfo' => [
                'https://user:pa%ss@example.com/x',
                'https://user:pa%25ss@example.com/x',
            ],
            'square brackets in userinfo before IP literal' => [
                'https://u[s]er@[::1]/x',
                'https://u%5Bs%5Der@[::1]/x',
            ],
            'invalid characters in host are untouched' => [
                'https://exa[mple.com:80%/x[y]',
                'https://exa[mple.com:80%/x%5By%5D',
            ],
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

    /**
     * @return array<string, array{string}>
     */
    public static function providerConvertKeepsHrefUntouched(): array
    {
        return [
            'IP literal in authority' => ['http://[::1]:8080/foo'],
            'IP literal after userinfo' => ['https://user@[::1]:8080/foo'],
            'valid percent-encoding' => ['https://example.com/foo%5Bbar%5d%20baz'],
            'characters accepted by anyURI' => ['https://example.com/a b{c}|d^e`f\\g&amp;h&quot;i&lt;j'],
            'internal link' => ['#foo'],
            'ezurl link' => ['ezurl://95#foo'],
            'ezlocation link' => ['ezlocation://42'],
            'mailto link' => ['mailto:foo@example.com'],
        ];
    }

    /**
     * @dataProvider providerConvertKeepsHrefUntouched
     */
    public function testConvertKeepsHrefUntouched(string $href): void
    {
        self::assertEquals(
            $this->createDocument($href),
            (new LinkHref())->convert($this->createDocument($href))
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
