<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use DOMXPath;
use Ibexa\FieldTypeRichText\RichText\Schema\DocbookSchemaPruner;
use Ibexa\FieldTypeRichText\RichText\Validator\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * Compiling the full DocBook RELAX NG grammar takes ~80ms per validated document, so the docbook validator
 * uses a schema with the definitions unreachable from ezpublish.rng removed.
 */
final class PrunedDocbookSchemaTest extends TestCase
{
    private const SCHEMA_DIR = __DIR__ . '/../../../../src/bundle/Resources/richtext/schemas/docbook';
    private const DOCBOOK_NAMESPACE = 'http://docbook.org/ns/docbook';

    public function testPrunedSchemaIsUpToDate(): void
    {
        $schemas = (new DocbookSchemaPruner())->prune(
            self::SCHEMA_DIR . '/' . DocbookSchemaPruner::DOCBOOK_FILE,
            self::SCHEMA_DIR . '/' . DocbookSchemaPruner::EZPUBLISH_FILE
        );

        foreach ($schemas as $file => $document) {
            self::assertStringEqualsFile(
                self::SCHEMA_DIR . '/pruned/' . $file,
                (string)$document->saveXML(),
                "pruned/$file is outdated, regenerate it with prune-rng.php"
            );
        }
    }

    public function testPrunedSchemaDropsUnreachableDefinitions(): void
    {
        $fullCount = $this->countDefinitions(self::SCHEMA_DIR . '/docbook.rng');
        $prunedCount = $this->countDefinitions(self::SCHEMA_DIR . '/pruned/docbook.rng');

        self::assertLessThan($fullCount / 2, $prunedCount);
    }

    /**
     * Compiles the full schema for every case, skip with --exclude-group slow for quick local runs.
     *
     * @group slow
     *
     * @dataProvider provideDocuments
     */
    public function testPrunedSchemaValidatesLikeFullSchema(string $xml): void
    {
        $fullValidator = new Validator([self::SCHEMA_DIR . '/ezpublish.rng']);
        $prunedValidator = new Validator([self::SCHEMA_DIR . '/pruned/ezpublish.rng']);

        self::assertSame(
            $fullValidator->validateDocument($this->loadDocument($xml)),
            $prunedValidator->validateDocument($this->loadDocument($xml))
        );
    }

    /**
     * libxml keeps the IDs and IDREFs found during validation on the document, so each validation needs a fresh one.
     */
    private function loadDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml));

        return $document;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDocuments(): iterable
    {
        $documentTemplate = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">%s</section>';

        yield 'empty section' => [sprintf($documentTemplate, '')];
        yield 'DocBook element disabled by ezpublish.rng' => [sprintf($documentTemplate, '<abstract><para>Foo</para></abstract>')];
        yield 'DocBook element replaced by notAllowed in ezpublish.rng' => [sprintf($documentTemplate, '<para><footnote><para>Foo</para></footnote></para>')];
        yield 'DocBook element removed from the pruned schema' => [sprintf($documentTemplate, '<para><indexterm><primary>Foo</primary></indexterm></para>')];
        yield 'link to a missing anchor' => [sprintf($documentTemplate, '<para><link linkend="missing-anchor">Foo</link></para>')];
        yield 'unknown element' => [sprintf($documentTemplate, '<para><foo>bar</foo></para>')];
        yield 'invalid attribute value' => [sprintf($documentTemplate, '<orderedlist numeration="foo"><listitem><para>Foo</para></listitem></orderedlist>')];
        yield 'article root element' => ['<?xml version="1.0" encoding="UTF-8"?><article xmlns="http://docbook.org/ns/docbook" version="5.0"><title>Foo</title></article>'];

        $fixtures = (new Finder())
            ->files()
            ->in(__DIR__ . '/../../../')
            ->name('*.xml')
            ->contains('xmlns="' . self::DOCBOOK_NAMESPACE . '"')
            ->sortByName();

        foreach ($fixtures as $fixture) {
            yield $fixture->getRelativePathname() => [$fixture->getContents()];
        }
    }

    private function countDefinitions(string $schemaPath): int
    {
        $schema = new DOMDocument();
        $schema->load($schemaPath);

        $xpath = new DOMXPath($schema);
        $xpath->registerNamespace('rng', 'https://relaxng.org/ns/structure/1.0');

        $definitions = $xpath->query('//rng:define');
        self::assertNotFalse($definitions);

        return $definitions->length;
    }
}
