<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\FieldTypeRichText\RichText\RelationProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @group fieldType
 * @group ezrichtext
 */
class RelationProcessorTest extends TestCase
{
    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\RelationProcessor::getRelations
     *
     * @dataProvider dateProviderForGetRelations
     *
     * @param \DOMDocument $document
     * @param array $expectedRelations
     */
    public function testGetRelations(DOMDocument $document, array $expectedRelations): void
    {
        $actualProcessor = (new RelationProcessor())->getRelations($document);

        $this->assertSame($expectedRelations, $actualProcessor);
    }

    public function dateProviderForGetRelations(): array
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para><link xlink:href="ezlocation://72">link1</link></para>
    <para><link xlink:href="ezlocation://61">link2</link></para>
    <para><link xlink:href="ezlocation://61">link3</link></para>
    <para><link xlink:href="ezcontent://70">link4</link></para>
    <para><link xlink:href="ezcontent://75">link5</link></para>
    <para><link xlink:href="ezcontent://75">link6</link></para>
</section>
EOT;

        return [
            [
                $this->createDOMDocument($xml),
                [
                    Relation::LINK => [
                        'locationIds' => [72, 61],
                        'contentIds' => [70, 75],
                    ],
                    Relation::EMBED => [
                        'locationIds' => [],
                        'contentIds' => [],
                    ],
                ],
            ],
        ];
    }

    private function createDOMDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document;
    }
}

class_alias(RelationProcessorTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\RelationProcessorTest');
