<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\FieldTypeRichText\RichText\RendererInterface;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\FieldTypeRichText\RichText\Converter\Aggregate;
use Ibexa\FieldTypeRichText\RichText\Converter\Link;
use Ibexa\FieldTypeRichText\RichText\Converter\Render\Template;
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    /**
     * @dataProvider providerConvertWithLinkInCustomTag
     *
     * @see https://jira.ez.no/browse/EZP-30166
     */
    public function testConvertWithLinkInCustomTag(string $input, string $expectedOutput): void
    {
        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML($input);

        $locationService = $this->createMock(LocationService::class);
        $contentService = $this->createMock(ContentService::class);
        $urlAliasRouter = $this->createMock(UrlAliasRouter::class);
        $renderer = $this->createMock(RendererInterface::class);

        $linkConverter = new Link(
            $locationService,
            $contentService,
            $urlAliasRouter
        );

        $aggregate = new Aggregate([$linkConverter]);

        $templateConverter = new Template(
            $renderer,
            $aggregate
        );

        $aggregate = new Aggregate([$templateConverter, $linkConverter]);
        $output = $aggregate->convert($xmlDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($expectedOutput);
        $this->assertEquals($expectedOutputDocument, $output, 'Xml is not converted as expected');
    }

    public function providerConvertWithLinkInCustomTag(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml"
         xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"
         version="5.0-variant ezpublish-1.0">
    <para></para>
    <eztemplate name="bulbo" ezxhtml:class="ez-custom-tag ez-custom-tag--attributes-visible">
        <ezcontent>
            <para>Just a regular text</para>
            <para>
                <link xlink:href="ezlocation://2" xlink:show="none">ezlocation URL</link>
            </para>
            <para>
                <link xlink:href="ezurl://1" xlink:show="none" xlink:title="">ezurl URL </link>
            </para>
        </ezcontent>
        <ezconfig><ezvalue key="title">Bulbo</ezvalue></ezconfig>
    </eztemplate>
</section>',
                '<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para/>
    <eztemplate name="bulbo" ezxhtml:class="ez-custom-tag ez-custom-tag--attributes-visible">
        <ezcontent>
            <para>Just a regular text</para>
            <para>
                <link xlink:href="" xlink:show="none">ezlocation URL</link>
            </para>
            <para>
                <link xlink:href="ezurl://1" xlink:show="none" xlink:title="">ezurl URL </link>
            </para>
        </ezcontent>
        <ezconfig><ezvalue key="title">Bulbo</ezvalue></ezconfig>
    </eztemplate>
</section>',
            ],
        ];
    }
}

class_alias(AggregateTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Converter\AggregateTest');
