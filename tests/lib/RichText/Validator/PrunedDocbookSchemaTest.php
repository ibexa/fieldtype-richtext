<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use DOMXPath;
use Ibexa\FieldTypeRichText\RichText\Validator\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Compiling the full DocBook RELAX NG grammar takes ~80ms per validated document, so the docbook validator
 * uses a schema with the definitions unreachable from ezpublish.rng removed.
 */
final class PrunedDocbookSchemaTest extends TestCase
{
    private const SCHEMA_DIR = __DIR__ . '/../../../../src/bundle/Resources/richtext/schemas/docbook';
    private const DOCBOOK_NAMESPACE = 'http://docbook.org/ns/docbook';

    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/' . uniqid('ibexa_richtext_pruned_rng_', true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->outputDir . '/*') ?: []);
        if (is_dir($this->outputDir)) {
            rmdir($this->outputDir);
        }
    }

    public function testDefaultDocbookValidatorUsesPrunedSchema(): void
    {
        $config = Yaml::parseFile(
            __DIR__ . '/../../../../src/bundle/Resources/config/fieldtype_services.yaml',
            Yaml::PARSE_CUSTOM_TAGS
        );

        self::assertContains(
            '%ibexa.field_type.richtext.resources%/schemas/docbook/ezpublish.pruned.rng',
            $config['parameters']['ibexa.field_type.richtext.validator.docbook.resources']
        );
    }

    public function testPrunedSchemaIsUpToDate(): void
    {
        exec(
            sprintf(
                '%s %s %s 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(self::SCHEMA_DIR . '/prune-rng.php'),
                escapeshellarg($this->outputDir)
            ),
            $output,
            $exitCode
        );

        self::assertSame(0, $exitCode, implode("\n", $output));
        foreach (['docbook.pruned.rng', 'ezpublish.pruned.rng'] as $file) {
            self::assertFileEquals(
                $this->outputDir . '/' . $file,
                self::SCHEMA_DIR . '/' . $file,
                "$file is outdated, regenerate it with prune-rng.php"
            );
        }
    }

    public function testPrunedSchemaDropsUnreachableDefinitions(): void
    {
        $fullCount = $this->countDefinitions(self::SCHEMA_DIR . '/docbook.rng');
        $prunedCount = $this->countDefinitions(self::SCHEMA_DIR . '/docbook.pruned.rng');

        self::assertLessThan($fullCount / 2, $prunedCount);
    }

    /**
     * @dataProvider provideDocuments
     */
    public function testPrunedSchemaValidatesLikeFullSchema(string $xml): void
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $fullValidator = new Validator([self::SCHEMA_DIR . '/ezpublish.rng']);
        $prunedValidator = new Validator([self::SCHEMA_DIR . '/ezpublish.pruned.rng']);

        self::assertSame(
            $fullValidator->validateDocument($document),
            $prunedValidator->validateDocument($document)
        );
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
        yield 'DocBook element never referenced by ezpublish.rng' => [sprintf($documentTemplate, '<para><footnote><para>Foo</para></footnote></para>')];
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
        $xpath->registerNamespace('rng', 'http://relaxng.org/ns/structure/1.0');

        $definitions = $xpath->query('//rng:define');
        self::assertNotFalse($definitions);

        return $definitions->length;
    }
}
