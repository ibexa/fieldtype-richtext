<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Schema;

use DOMDocument;
use DOMXPath;
use Ibexa\FieldTypeRichText\RichText\Schema\DocbookSchemaPruner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\Schema\DocbookSchemaPruner
 */
final class DocbookSchemaPrunerTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/_fixtures';

    private DocbookSchemaPruner $pruner;

    protected function setUp(): void
    {
        $this->pruner = new DocbookSchemaPruner();
    }

    public function testRemovesUnreachableAndOverriddenDefinitions(): void
    {
        $schemas = $this->pruneFixture('variant.rng');
        $base = $this->createXPath($schemas[DocbookSchemaPruner::DOCBOOK_FILE]);

        self::assertSame(['used', 'overridden'], $this->queryValues($base, '//rng:define/@name'));
        self::assertSame(['replacement'], $this->queryValues($base, '//rng:define[@name="overridden"]/rng:element/@name'));
        self::assertSame(0, $this->countNodes($base, '//rng:div'));
    }

    public function testMovesIncludeOverridesIntoPrunedGrammar(): void
    {
        $schemas = $this->pruneFixture('variant.rng');
        $base = $this->createXPath($schemas[DocbookSchemaPruner::DOCBOOK_FILE]);
        $variant = $this->createXPath($schemas[DocbookSchemaPruner::EZPUBLISH_FILE]);

        self::assertSame(['variantRoot'], $this->queryValues($base, '//rng:start/rng:ref/@name'));
        self::assertSame(0, $this->countNodes($variant, '//rng:include/*'));
        self::assertSame(['variantRoot'], $this->queryValues($variant, '/rng:grammar/rng:define/@name'));
    }

    public function testKeepsLicenseNoticeAndDropsAnnotations(): void
    {
        $schemas = $this->pruneFixture('variant.rng');
        $base = $this->createXPath($schemas[DocbookSchemaPruner::DOCBOOK_FILE]);

        self::assertSame(
            [' License notice '],
            $this->queryValues($base, '/rng:grammar/comment()')
        );
        self::assertSame(0, $this->countNodes($base, '//*[namespace-uri() != "http://relaxng.org/ns/structure/1.0"]'));
    }

    public function testAddsGeneratedNoticeToBothSchemas(): void
    {
        foreach ($this->pruneFixture('variant.rng') as $file => $document) {
            self::assertStringContainsString(
                'do not edit',
                (string)$this->createXPath($document)->evaluate('string(/comment())'),
                $file
            );
        }
    }

    /**
     * @dataProvider provideInvalidSchemas
     */
    public function testThrowsOnInvalidSchema(
        string $variantPath,
        string $expectedMessage
    ): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->pruner->prune(self::FIXTURES_DIR . '/base.rng', $variantPath);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidSchemas(): iterable
    {
        yield 'reference without a definition' => [
            self::FIXTURES_DIR . '/undefined-reference.rng',
            'References without a definition: missing',
        ];
        yield 'variant without include' => [
            self::FIXTURES_DIR . '/no-include.rng',
            'Expected a docbook.rng grammar and a single <include> in ezpublish.rng',
        ];
        yield 'missing file' => [
            self::FIXTURES_DIR . '/does-not-exist.rng',
            'Cannot load',
        ];
    }

    /**
     * @return array<string, DOMDocument>
     */
    private function pruneFixture(string $variantFile): array
    {
        return $this->pruner->prune(self::FIXTURES_DIR . '/base.rng', self::FIXTURES_DIR . '/' . $variantFile);
    }

    private function createXPath(DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rng', 'http://relaxng.org/ns/structure/1.0');

        return $xpath;
    }

    /**
     * @return string[]
     */
    private function queryValues(
        DOMXPath $xpath,
        string $expression
    ): array {
        $nodes = $xpath->query($expression);
        self::assertNotFalse($nodes);

        $values = [];
        foreach ($nodes as $node) {
            $values[] = (string)$node->nodeValue;
        }

        return $values;
    }

    private function countNodes(
        DOMXPath $xpath,
        string $expression
    ): int {
        $nodes = $xpath->query($expression);
        self::assertNotFalse($nodes);

        return $nodes->length;
    }
}
