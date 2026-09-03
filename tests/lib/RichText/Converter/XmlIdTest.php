<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Converter\XmlId;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentLoader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\Converter\XmlId
 */
final class XmlIdTest extends TestCase
{
    private const SECTION_OPEN_TAG = '<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">';

    /**
     * @return array<string, array<int, string>>
     */
    public static function providerConvert(): array
    {
        // Paragraph-only cases, expressed as input xml:id list => expected xml:id list (null = no attribute)
        $idCases = [
            'numeric id gets prefixed' => [['227'], ['_227']],
            'space in id replaced' => [['foo bar'], ['foo_bar']],
            'colon in id replaced' => [['foo:bar'], ['foo_bar']],
            'sanitized ids colliding with each other get deduplicated' => [
                ['foo bar', 'foo:bar'],
                ['foo_bar', 'foo_bar_1'],
            ],
            'sanitized id colliding with existing valid id gets deduplicated' => [
                ['foo_bar', 'foo bar'],
                ['foo_bar', 'foo_bar_1'],
            ],
            'sanitized id colliding with valid id appearing later gets deduplicated' => [
                ['foo bar', 'foo_bar'],
                ['foo_bar_1', 'foo_bar'],
            ],
            'leading digit gets prefixed' => [['1lipsum'], ['_1lipsum']],
            'valid ids are untouched' => [['lipsum_id1', 'good.id-1'], ['lipsum_id1', 'good.id-1']],
            'valid UTF-8 id is untouched' => [['zażółć'], ['zażółć']],
            'invalid symbol characters replaced' => [['a×b!'], ['a_b_']],
            'empty id gets removed' => [[''], [null]],
            'document without ids is untouched' => [[null], [null]],
        ];

        $cases = [];
        foreach ($idCases as $name => [$inputIds, $expectedIds]) {
            $cases[$name] = [self::buildParagraphs($inputIds), self::buildParagraphs($expectedIds)];
        }

        $externalLinks = '<para><link xlink:href="http://example.com/#227">Lorem</link></para>'
            . '<para><link xlink:href="ezurl://95#227">ipsum</link></para>';
        $danglingFragment = '<para><link xlink:href="#42">Lorem ipsum</link></para>';

        return $cases + [
            'internal link fragment follows the sanitized anchor id' => [
                self::buildAnchorWithInternalLink('227'),
                self::buildAnchorWithInternalLink('_227'),
            ],
            'external link fragments are untouched' => [
                self::buildParagraphs(['227']) . $externalLinks,
                self::buildParagraphs(['_227']) . $externalLinks,
            ],
            'dangling internal fragment is untouched' => [$danglingFragment, $danglingFragment],
        ];
    }

    /**
     * @param array<int, string|null> $ids
     */
    private static function buildParagraphs(array $ids): string
    {
        $xml = '';
        foreach ($ids as $index => $id) {
            $attribute = $id === null ? '' : sprintf(' xml:id="%s"', $id);
            $xml .= sprintf('<para%s>Lorem ipsum %d</para>', $attribute, $index);
        }

        return $xml;
    }

    private static function buildAnchorWithInternalLink(string $id): string
    {
        return sprintf(
            '<para><anchor xml:id="%s"/>Lorem</para><para><link xlink:href="#%s">ipsum</link></para>',
            $id,
            $id
        );
    }

    /**
     * @dataProvider providerConvert
     */
    public function testConvert(string $input, string $output): void
    {
        $inputDocument = $this->createDocument(self::SECTION_OPEN_TAG . $input . '</section>');

        $converter = new XmlId();

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = $this->createDocument(self::SECTION_OPEN_TAG . $output . '</section>');

        self::assertEquals($expectedOutputDocument, $outputDocument);
        self::assertLoadsWithoutLibXmlErrors((string)$outputDocument->saveXML());

        self::assertEquals(
            $expectedOutputDocument,
            $converter->convert($this->createDocument(self::SECTION_OPEN_TAG . $output . '</section>'))
        );
    }

    private function createDocument(string $xml): DOMDocument
    {
        return (new DOMDocumentLoader())->loadXML($xml);
    }

    private static function assertLoadsWithoutLibXmlErrors(string $xml): void
    {
        $document = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $document->loadXML($xml);
            self::assertSame([], libxml_get_errors(), 'Sanitized document should load without libxml errors');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
    }
}
