<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter\Render;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\RendererInterface;
use Ibexa\FieldTypeRichText\RichText\Converter\Render\Embed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EmbedTest extends TestCase
{
    protected RendererInterface&MockObject $rendererMock;

    protected LoggerInterface&MockObject $loggerMock;

    public function setUp(): void
    {
        $this->rendererMock = $this->getRendererMock();
        $this->loggerMock = $this->getLoggerMock();
        parent::setUp();
    }

    /**
     * Data provider for testConvert.
     *
     * @return array<array{
     *     0: string,
     *     1: string,
     *     2: array<string>,
     *     3: array{array<array<mixed>>, array<string>},
     *     4: array{array<array<mixed>>, array<string>}
     * }>
     *
     * @see testConvert
     *
     * Provided parameters:
     * <code>string $xmlString, string $expectedXmlString, array $errors, array $renderParams</code>
     */
    public function providerForTestConvert(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106" view="embed"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106" view="embed">
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed',
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembed xlink:href="ezcontent://106" view="embed" xml:id="embed-id-1" ezxhtml:class="embed-class" ezxhtml:align="left">
    <ezlink href_resolved="RESOLVED" xlink:href="ezurl://95#fragment1" xlink:show="new" xml:id="link-id-1" xlink:title="Link title" ezxhtml:class="link-class"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembed xlink:href="ezcontent://106" view="embed" xml:id="embed-id-1" ezxhtml:class="embed-class" ezxhtml:align="left">
    <ezlink href_resolved="RESOLVED" xlink:href="ezurl://95#fragment1" xlink:show="new" xml:id="link-id-1" xlink:title="Link title" ezxhtml:class="link-class"/>
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed',
                                    'link' => [
                                        'href' => 'RESOLVED',
                                        'resourceType' => 'URL',
                                        'resourceId' => null,
                                        'wrapped' => false,
                                        'target' => '_blank',
                                        'title' => 'Link title',
                                        'id' => 'link-id-1',
                                        'class' => 'link-class',
                                    ],
                                    'class' => 'embed-class',
                                    'align' => 'left',
                                    'anchor' => 'embed-id-1',
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezlocation://601" view="embed-inline">
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
      <ezvalue key="offset">10</ezvalue>
      <ezvalue key="limit">5</ezvalue>
      <ezvalue key="hey">
          <ezvalue key="look">
              <ezvalue key="at">
                  <ezvalue key="this">wohoo</ezvalue>
                  <ezvalue key="that">weeee</ezvalue>
              </ezvalue>
          </ezvalue>
          <ezvalue key="what">get to the chopper</ezvalue>
      </ezvalue>
    </ezconfig>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezlocation://601" view="embed-inline">
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
      <ezvalue key="offset">10</ezvalue>
      <ezvalue key="limit">5</ezvalue>
      <ezvalue key="hey">
          <ezvalue key="look">
              <ezvalue key="at">
                  <ezvalue key="this">wohoo</ezvalue>
                  <ezvalue key="that">weeee</ezvalue>
              </ezvalue>
          </ezvalue>
          <ezvalue key="what">get to the chopper</ezvalue>
      </ezvalue>
    </ezconfig>
    <ezpayload><![CDATA[601]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [],
                    [],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed-inline',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed-inline',
                                    'config' => [
                                        'size' => 'medium',
                                        'offset' => 10,
                                        'limit' => 5,
                                        'hey' => [
                                            'look' => [
                                                'at' => [
                                                    'this' => 'wohoo',
                                                    'that' => 'weeee',
                                                ],
                                            ],
                                            'what' => 'get to the chopper',
                                        ],
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['601'],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembed xlink:href="ezlocation://601" view="embed-inline">
    <ezlink href_resolved="RESOLVED" xlink:href="ezcontent://95#fragment1" xlink:show="replace"/>
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
      <ezvalue key="offset">10</ezvalue>
      <ezvalue key="limit">5</ezvalue>
      <ezvalue key="hey">
          <ezvalue key="look">
              <ezvalue key="at">
                  <ezvalue key="this">wohoo</ezvalue>
                  <ezvalue key="that">weeee</ezvalue>
              </ezvalue>
          </ezvalue>
          <ezvalue key="what">get to the chopper</ezvalue>
      </ezvalue>
    </ezconfig>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembed xlink:href="ezlocation://601" view="embed-inline">
    <ezlink href_resolved="RESOLVED" xlink:href="ezcontent://95#fragment1" xlink:show="replace"/>
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
      <ezvalue key="offset">10</ezvalue>
      <ezvalue key="limit">5</ezvalue>
      <ezvalue key="hey">
          <ezvalue key="look">
              <ezvalue key="at">
                  <ezvalue key="this">wohoo</ezvalue>
                  <ezvalue key="that">weeee</ezvalue>
              </ezvalue>
          </ezvalue>
          <ezvalue key="what">get to the chopper</ezvalue>
      </ezvalue>
    </ezconfig>
    <ezpayload><![CDATA[601]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [],
                    [],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed-inline',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed-inline',
                                    'config' => [
                                        'size' => 'medium',
                                        'offset' => 10,
                                        'limit' => 5,
                                        'hey' => [
                                            'look' => [
                                                'at' => [
                                                    'this' => 'wohoo',
                                                    'that' => 'weeee',
                                                ],
                                            ],
                                            'what' => 'get to the chopper',
                                        ],
                                    ],
                                    'link' => [
                                        'href' => 'RESOLVED',
                                        'resourceType' => 'CONTENT',
                                        'resourceId' => '95',
                                        'resourceFragmentIdentifier' => 'fragment1',
                                        'wrapped' => false,
                                        'target' => '_self',
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['601'],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezlocation://601" view="embed"/>
  <ezembedinline xlink:href="ezcontent://106" view="full"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezlocation://601" view="embed">
    <ezpayload><![CDATA[601]]></ezpayload>
  </ezembed>
  <ezembedinline xlink:href="ezcontent://106" view="full">
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembedinline>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'full',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'full',
                                ],
                            ],
                            'is_inline' => true,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed',
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['601'],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembedinline xlink:href="ezlocation://601" view="embed">
    <ezlink href_resolved="RESOLVED" xlink:href="ezcontent://95"/>
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
    </ezconfig>
  </ezembedinline>
  <paragraph>Here is one <link>linked <ezembedinline xlink:href="ezcontent://106" view="full">
    <ezlink href_resolved="RESOLVED2" xlink:href="ezlocation://59#fragment"/>
    <ezconfig>
      <ezvalue key="size">small</ezvalue>
    </ezconfig>
  </ezembedinline> inline</link> embed</paragraph>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <ezembedinline xlink:href="ezlocation://601" view="embed">
    <ezlink href_resolved="RESOLVED" xlink:href="ezcontent://95"/>
    <ezconfig>
      <ezvalue key="size">medium</ezvalue>
    </ezconfig>
    <ezpayload><![CDATA[601]]></ezpayload>
  </ezembedinline>
  <paragraph>Here is one <link>linked <ezembedinline xlink:href="ezcontent://106" view="full">
    <ezlink href_resolved="RESOLVED2" xlink:href="ezlocation://59#fragment"/>
    <ezconfig>
      <ezvalue key="size">small</ezvalue>
    </ezconfig>
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembedinline> inline</link> embed</paragraph>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'full',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'full',
                                    'config' => [
                                        'size' => 'small',
                                    ],
                                    'link' => [
                                        'href' => 'RESOLVED2',
                                        'resourceType' => 'LOCATION',
                                        'resourceId' => '59',
                                        'resourceFragmentIdentifier' => 'fragment',
                                        'wrapped' => true,
                                    ],
                                ],
                            ],
                            'is_inline' => true,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed',
                                    'config' => [
                                        'size' => 'medium',
                                    ],
                                    'link' => [
                                        'href' => 'RESOLVED',
                                        'resourceType' => 'CONTENT',
                                        'resourceId' => '95',
                                        'wrapped' => false,
                                    ],
                                ],
                            ],
                            'is_inline' => true,
                        ],
                    ],
                    ['601'],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed view="embed"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed view="embed"/>
</section>',
                [
                    "Could not embed resource: empty 'xlink:href' attribute",
                ],
                [
                    [],
                    [],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="eznodeassignment://106" view="embed"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="eznodeassignment://106" view="embed"/>
</section>',
                [
                    "Could not embed resource: unhandled resource reference 'eznodeassignment://106'",
                ],
                [
                    [],
                    [],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106" view="embed">
    <ezlink xlink:href="ezcontent://601"/>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106" view="embed">
    <ezlink xlink:href="ezcontent://601"/>
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembed>
</section>',
                [
                    'Could not create link parameters: resolved embed link is missing',
                ],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed',
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106">
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed',
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembedinline xlink:href="ezcontent://106"/>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembedinline xlink:href="ezcontent://106">
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembedinline>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed-inline',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed-inline',
                                ],
                            ],
                            'is_inline' => true,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <para>
    <ezattribute>
      <ezvalue key="not-related-attr">not related value</ezvalue>
    </ezattribute>
    <ezembedinline xlink:href="ezcontent://106" view="embed-inline">
      <ezattribute>
        <ezvalue key="inline-choice-attr">choice1</ezvalue>
        <ezvalue key="inline-choice-mul-attr">choice2,choice3</ezvalue>
      </ezattribute>
    </ezembedinline>
  </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <para>
    <ezattribute>
      <ezvalue key="not-related-attr">not related value</ezvalue>
    </ezattribute>
    <ezembedinline xlink:href="ezcontent://106" view="embed-inline">
      <ezattribute>
        <ezvalue key="inline-choice-attr">choice1</ezvalue>
        <ezvalue key="inline-choice-mul-attr">choice2,choice3</ezvalue>
      </ezattribute>
      <ezpayload>106</ezpayload>
    </ezembedinline>
  </para>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed-inline',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed-inline',
                                    'dataAttributes' => [
                                        'inline-choice-attr' => 'choice1',
                                        'inline-choice-mul-attr' => 'choice2,choice3',
                                    ],
                                ],
                            ],
                            'is_inline' => true,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106">
    <ezattribute>
       <ezvalue key="inline-choice-attr">choice1</ezvalue>
       <ezvalue key="inline-choice-mul-attr">choice2,choice3</ezvalue>
    </ezattribute>
  </ezembed>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink">
  <ezembed xlink:href="ezcontent://106">
    <ezattribute>
      <ezvalue key="inline-choice-attr">choice1</ezvalue>
      <ezvalue key="inline-choice-mul-attr">choice2,choice3</ezvalue>
    </ezattribute>
    <ezpayload><![CDATA[106]]></ezpayload>
  </ezembed>
</section>',
                [],
                [
                    [
                        [
                            'id' => '106',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '106',
                                    'viewType' => 'embed',
                                    'dataAttributes' => [
                                        'inline-choice-attr' => 'choice1',
                                        'inline-choice-mul-attr' => 'choice2,choice3',
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['106'],
                ],
                [
                    [],
                    [],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <paragraph>Here is paragraph with child embed
    <ezembed xlink:href="ezlocation://601">
      <ezembed xlink:href="ezlocation://602">
        <ezconfig>
          <ezvalue key="nested">value2</ezvalue>
        </ezconfig>
      </ezembed>
      <ezconfig>
        <ezvalue key="parent">value1</ezvalue>
      </ezconfig>
    </ezembed>
    <ezconfig>
      <ezvalue key="custom">big</ezvalue>
    </ezconfig>
  </paragraph>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml">
  <paragraph>Here is paragraph with child embed
    <ezembed xlink:href="ezlocation://601">
      <ezembed xlink:href="ezlocation://602">
        <ezconfig>
          <ezvalue key="nested">value2</ezvalue>
        </ezconfig>
        <ezpayload><![CDATA[602]]></ezpayload>
      </ezembed>
      <ezconfig>
        <ezvalue key="parent">value1</ezvalue>
      </ezconfig>
      <ezpayload><![CDATA[601]]></ezpayload>
    </ezembed>
    <ezconfig>
      <ezvalue key="custom">big</ezvalue>
    </ezconfig>
  </paragraph>
</section>',
                [],
                [
                    [],
                    [],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed',
                                    'config' => [
                                        'parent' => 'value1',
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                        [
                            'id' => '602',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => 602,
                                    'viewType' => 'embed',
                                    'config' => [
                                        'nested' => 'value2',
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['601', '602'],
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml">
  <paragraph>Here is paragraph with embed with empty config value
    <ezembed xlink:href="ezlocation://601">
      <ezconfig>
        <ezvalue key="non-empty">value1</ezvalue>
        <ezvalue key="empty"/>
      </ezconfig>
    </ezembed>
  </paragraph>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml">
  <paragraph>Here is paragraph with embed with empty config value
    <ezembed xlink:href="ezlocation://601">
      <ezconfig>
        <ezvalue key="non-empty">value1</ezvalue>
        <ezvalue key="empty"/>
      </ezconfig>
      <ezpayload><![CDATA[601]]></ezpayload>
    </ezembed>
  </paragraph>
</section>',
                [],
                [
                    [],
                    [],
                ],
                [
                    [
                        [
                            'id' => '601',
                            'viewType' => 'embed',
                            [
                                'embedParams' => [
                                    'id' => '601',
                                    'viewType' => 'embed',
                                    'config' => [
                                        'non-empty' => 'value1',
                                        'empty' => null,
                                    ],
                                ],
                            ],
                            'is_inline' => false,
                        ],
                    ],
                    ['601'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestConvert
     *
     * @param array<string> $errors
     * @param array{array<array<mixed>>, array<string>} $renderContentEmbedParams
     * @param array{array<array<mixed>>, array<string>} $renderLocationEmbedParams
     */
    public function testConvert(
        string $xmlString,
        string $expectedXmlString,
        array $errors,
        array $renderContentEmbedParams,
        array $renderLocationEmbedParams
    ): void {
        $this->loggerMock
            ->expects(self::exactly(count($errors)))
            ->method('error')
            ->withConsecutive($errors);

        $this->rendererMock->expects(self::never())->method('renderTemplate');

        [$embedContentParams, $embedContentReturnValues] = $renderContentEmbedParams;
        [$embedLocationParams, $embedLocationReturnValues] = $renderLocationEmbedParams;

        if (!empty($embedContentParams)) {
            $this->rendererMock
                ->expects(self::exactly(count($embedContentParams)))
                ->method('renderContentEmbed')
                ->withConsecutive(...$embedContentParams)
                ->willReturnOnConsecutiveCalls(...$embedContentReturnValues);
        }

        if (!empty($embedLocationParams)) {
            $this->rendererMock
                ->expects(self::exactly(count($embedLocationParams)))
                ->method('renderLocationEmbed')
                ->withConsecutive(...$embedLocationParams)
                ->willReturnOnConsecutiveCalls(...$embedLocationReturnValues);
        }

        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($xmlString);

        $document = $this->getConverter()->convert($document);

        $expectedDocument = new DOMDocument();
        $expectedDocument->preserveWhiteSpace = false;
        $expectedDocument->formatOutput = false;
        $expectedDocument->loadXML($expectedXmlString);

        self::assertEquals($expectedDocument, $document);
    }

    protected function getConverter(): Embed
    {
        return new Embed(
            $this->rendererMock,
            $this->loggerMock
        );
    }

    protected function getRendererMock(): RendererInterface&MockObject
    {
        return $this->createMock(RendererInterface::class);
    }

    protected function getLoggerMock(): LoggerInterface&MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }
}
