<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Core\Base\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException as APIUnauthorizedException;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\LocationService;
use Ibexa\FieldTypeRichText\RichText\Converter\Link;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the Link converter
 * Class LinkTest.
 */
class LinkTest extends TestCase
{
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockContentService()
    {
        return $this->createMock(ContentService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockLocationService()
    {
        return $this->createMock(LocationService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockRouter()
    {
        return $this->createMock(RouterInterface::class);
    }

    /**
     * @return array
     */
    public function providerLinkXmlSample()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="/test">Link text</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="/test">Link text</link>
  </para>
</section>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="/test#anchor">Link text</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="/test#anchor">Link text</link>
  </para>
</section>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="/test#anchor"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="/test#anchor" href_resolved="/test#anchor"/>
  </ezembed>
</section>',
            ],
        ];
    }

    /**
     * Test conversion of ezurl://<id> links.
     *
     * @dataProvider providerLinkXmlSample
     */
    public function testLink($input, $output)
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $contentService = $this->getMockContentService();
        $locationService = $this->getMockLocationService();
        $router = $this->getMockRouter();

        $contentService->expects($this->never())
            ->method($this->anything());

        $locationService->expects($this->never())
            ->method($this->anything());

        $router->expects($this->never())
            ->method($this->anything());

        $converter = new Link($locationService, $contentService, $router);

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    /**
     * @return array
     */
    public function providerLocationLink()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezlocation://106">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="test">Content name</link>
  </para>
</section>',
                106,
                'test',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezlocation://106#anchor">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="test#anchor">Content name</link>
  </para>
</section>',
                106,
                'test',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106#anchor"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106#anchor" href_resolved="test#anchor"/>
  </ezembed>
</section>',
                106,
                'test',
            ],
        ];
    }

    /**
     * Test conversion of ezlocation://<id> links.
     *
     * @dataProvider providerLocationLink
     */
    public function testConvertLocationLink($input, $output, $locationId, $urlResolved)
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $contentService = $this->getMockContentService();
        $locationService = $this->getMockLocationService();
        $router = $this->getMockRouter();

        $location = $this->createMock(APILocation::class);

        $locationService->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo($locationId))
            ->willReturn($location);

        $router->expects($this->once())
            ->method('generate')
            ->with(UrlAliasRouter::URL_ALIAS_ROUTE_NAME, ['location' => $location])
            ->willReturn($urlResolved);

        $converter = new Link($locationService, $contentService, $router);

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    /**
     * @return array
     */
    public function providerBadLocationLink()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezlocation://106">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="#">Content name</link>
  </para>
</section>',
                106,
                new APINotFoundException('Location', 106),
                'warning',
                'While generating links for richtext, could not locate Location with ID 106',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezlocation://106">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="#">Content name</link>
  </para>
</section>',
                106,
                new APIUnauthorizedException('Location', 106),
                'notice',
                'While generating links for richtext, unauthorized to load Location with ID 106',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezlocation://106" href_resolved="#"/>
  </ezembed>
</section>',
                106,
                new APIUnauthorizedException('Location', 106),
                'notice',
                'While generating links for richtext, unauthorized to load Location with ID 106',
            ],
        ];
    }

    /**
     * Test logging of bad location links.
     *
     * @dataProvider providerBadLocationLink
     */
    public function testConvertBadLocationLink($input, $output, $locationId, $exception, $logType, $logMessage)
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $contentService = $this->getMockContentService();
        $locationService = $this->getMockLocationService();
        $router = $this->getMockRouter();

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method($logType)
            ->with($this->equalTo($logMessage));

        $locationService->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo($locationId))
            ->will($this->throwException($exception));

        $converter = new Link($locationService, $contentService, $router, $logger);

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    /**
     * @return array
     */
    public function providerContentLink()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezcontent://104">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="test">Content name</link>
  </para>
</section>',
                104,
                'test',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezcontent://104#anchor">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="test#anchor">Content name</link>
  </para>
</section>',
                104,
                'test',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezcontent://104#anchor"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezcontent://104#anchor" href_resolved="test#anchor"/>
  </ezembed>
</section>',
                104,
                'test',
            ],
        ];
    }

    /**
     * Test conversion of ezcontent://<id> links.
     *
     * @dataProvider providerContentLink
     */
    public function testConvertContentLink($input, $output, $contentId, $urlResolved)
    {
        $locationId = 106;
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $contentService = $this->getMockContentService();
        $locationService = $this->getMockLocationService();
        $router = $this->getMockRouter();

        $contentInfo = $this->createMock(APIContentInfo::class);
        $location = $this->createMock(APILocation::class);

        $contentInfo->expects($this->once())
            ->method('__get')
            ->with($this->equalTo('mainLocationId'))
            ->willReturn($locationId);

        $contentService->expects($this->any())
            ->method('loadContentInfo')
            ->with($this->equalTo($contentId))
            ->willReturn($contentInfo);

        $locationService->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo($locationId))
            ->willReturn($location);

        $router->expects($this->once())
            ->method('generate')
            ->with(UrlAliasRouter::URL_ALIAS_ROUTE_NAME, ['location' => $location])
            ->willReturn($urlResolved);

        $converter = new Link($locationService, $contentService, $router);

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }

    /**
     * @return array
     */
    public function providerBadContentLink()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezcontent://205">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="#">Content name</link>
  </para>
</section>',
                205,
                new APINotFoundException('Content', 205),
                'warning',
                'While generating links for richtext, could not locate Content object with ID 205',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="ezcontent://205">Content name</link>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <para>
    <link xlink:href="#">Content name</link>
  </para>
</section>',
                205,
                new APIUnauthorizedException('Content', 205),
                'notice',
                'While generating links for richtext, unauthorized to load Content object with ID 205',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezcontent://205"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" version="5.0-variant ezpublish-1.0">
  <title>Link example</title>
  <ezembed>
    <ezlink xlink:href="ezcontent://205" href_resolved="#"/>
  </ezembed>
</section>',
                205,
                new APIUnauthorizedException('Content', 205),
                'notice',
                'While generating links for richtext, unauthorized to load Content object with ID 205',
            ],
        ];
    }

    /**
     * Test logging of bad content links.
     *
     * @dataProvider providerBadContentLink
     */
    public function testConvertBadContentLink($input, $output, $contentId, $exception, $logType, $logMessage)
    {
        $inputDocument = new DOMDocument();
        $inputDocument->loadXML($input);

        $contentService = $this->getMockContentService();
        $locationService = $this->getMockLocationService();
        $router = $this->getMockRouter();

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method($logType)
            ->with($this->equalTo($logMessage));

        $contentService->expects($this->once())
            ->method('loadContentInfo')
            ->with($this->equalTo($contentId))
            ->will($this->throwException($exception));

        $converter = new Link($locationService, $contentService, $router, $logger);

        $outputDocument = $converter->convert($inputDocument);

        $expectedOutputDocument = new DOMDocument();
        $expectedOutputDocument->loadXML($output);

        $this->assertEquals($expectedOutputDocument, $outputDocument);
    }
}

class_alias(LinkTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Converter\LinkTest');
