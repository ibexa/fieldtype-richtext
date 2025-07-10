<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter\Xslt;

use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\FieldTypeRichText\RichText\Converter\Aggregate;
use Ibexa\FieldTypeRichText\RichText\Converter\LiteralLayoutNestedList;
use Ibexa\FieldTypeRichText\RichText\Converter\ProgramListing;
use Ibexa\FieldTypeRichText\RichText\Converter\Xslt;

/**
 * Tests conversion from xhtml5 edit format to docbook.
 */
class Xhtml5ToDocbookTest extends BaseTest
{
    /**
     * Returns subdirectories for input and output fixtures.
     *
     * The test will try to match each XML file in input directory with
     * the file of the same name in the output directory.
     *
     * It is possible to test lossy conversion as well (say legacy ezxml).
     * To use this file name of the fixture that is converted with data loss
     * needs to end with `.lossy.xml`. As input test with this fixture will
     * be skipped, but as output fixture it will be matched to the input
     * fixture file of the same name but without `.lossy` part.
     *
     * Comments in fixtures are removed before conversion, so be free to use
     * comments inside fixtures for documentation as needed.
     *
     * @return array{input: string, output: string}
     */
    public function getFixtureSubdirectories(): array
    {
        return [
            'input' => 'xhtml5/edit',
            'output' => 'docbook',
        ];
    }

    /**
     * Return the absolute path to conversion transformation stylesheet.
     */
    protected function getConversionTransformationStylesheet(): string
    {
        return __DIR__ . '/../../../../../src/bundle/Resources/richtext/stylesheets/xhtml5/edit/docbook.xsl';
    }

    /**
     * Return custom XSLT stylesheets configuration.
     *
     * Stylesheet paths must be absolute.
     *
     * Code example:
     *
     * <code>
     *  array(
     *      array(
     *          "path" => __DIR__ . "/core.xsl",
     *          "priority" => 100
     *      ),
     *      array(
     *          "path" => __DIR__ . "/custom.xsl",
     *          "priority" => 99
     *      ),
     *  )
     * </code>
     *
     * @return array<array{path: string, priority: int}>
     */
    protected function getCustomConversionTransformationStylesheets(): array
    {
        return [
            [
                'path' => __DIR__ . '/_fixtures/xhtml5/edit/custom_stylesheets/youtube_docbook.xsl',
                'priority' => 99,
            ],
        ];
    }

    /**
     * Return an array of absolute paths to conversion result validation schemas.
     *
     * @return string[]
     */
    protected function getConversionValidationSchema(): array
    {
        return [
            __DIR__ . '/_fixtures/docbook/custom_schemas/youtube.rng',
            __DIR__ . '/../../../../../src/bundle/Resources/richtext/schemas/docbook/docbook.iso.sch.xsl',
        ];
    }

    protected function getConverter(): Converter
    {
        if ($this->converter === null) {
            $this->converter = new Aggregate(
                [
                    new ProgramListing(),
                    new Xslt(
                        $this->getConversionTransformationStylesheet(),
                        $this->getCustomConversionTransformationStylesheets()
                    ),
                    new LiteralLayoutNestedList(),
                ]
            );
        }

        return $this->converter;
    }
}
