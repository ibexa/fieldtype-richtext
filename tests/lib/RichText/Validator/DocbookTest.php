<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Validator\Validator;
use PHPUnit\Framework\TestCase;

class DocbookTest extends TestCase
{
    public function providerForTestValidate()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezurl://72">Hello <link xlink:href="ezurl://27">goodbye</link></link>
    </para>
</section>
',
                [
                    'link must not occur in the descendants of link',
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>Some <link xlink:href="ezcontent://601">linked <ezembedinline xlink:href="ezcontent://601">
        <ezlink xlink:href="ezcontent://106"/>
    </ezembedinline> linked</link> embeds.</para>
</section>
',
                [
                    'ezlink must not occur in the descendants of link',
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="listening loud indie rock">Nada Surf - Happy Kid</para>
</section>
',
                [],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para ezxhtml:class="">Nada Surf - Happy Kid</para>
</section>
',
                [],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <blockquote>
    <para>Some comments to people\'s comments!</para>
  </blockquote>
  <blockquote>
    <title ezxhtml:level="3">Header level 3</title>
    <title ezxhtml:level="4">Header level 4</title>
    <para>foobar quote<link xlink:href="ezurl://1044" xlink:show="none">http://ibexa.co</link> for more info.</para>
  </blockquote>
</section>
',
                [],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section
    xmlns="http://docbook.org/ns/docbook"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
    xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>test
        <superscript>1
            <emphasis role="strong">bold</emphasis>
            <emphasis>italic</emphasis>
            <emphasis role="underlined">underline</emphasis> superscript
            <link xlink:href="http://ibexa.co" xlink:show="none" xlink:title="link tile">link</link>
            <emphasis role="strikedthrough">strikedthrough</emphasis>
        </superscript>
    </para>
</section>',
                [],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section
    xmlns="http://docbook.org/ns/docbook"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
    xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>test
        <subscript>1
            <emphasis role="strong">bold</emphasis>
            <emphasis>italic</emphasis>
            <emphasis role="underlined">underline</emphasis> subscript
            <link xlink:href="http://ibexa.co" xlink:show="none" xlink:title="link tile">link</link>
            <emphasis role="strikedthrough">strikedthrough</emphasis>
        </subscript>
    </para>
</section>',
                [],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <itemizedlist mark="unsupported">
    <ezattribute>
      <ezvalue key="name-1">value 1</ezvalue>
      <ezvalue key="name-2">value 2</ezvalue>
      <ezvalue key="name-3">value 3</ezvalue>
    </ezattribute>
    <listitem ezxhtml:class="listItemClass">
      <ezattribute>
        <ezvalue key="name-2">value 2</ezvalue>
      </ezattribute>
      <para ezxhtml:class="listItemClass">
        <ezattribute>
          <ezvalue key="name-2">value 2</ezvalue>
        </ezattribute>This is a list item.</para>
    </listitem>
    <listitem ezxhtml:class="listItemClass">
      <ezattribute>
        <ezvalue key="name-3">value 3</ezvalue>
      </ezattribute>
      <para ezxhtml:class="listItemClass">
        <ezattribute>
          <ezvalue key="name-3">value 3</ezvalue>
        </ezattribute>
        <literallayout class="normal">This is a list item
with a line break.</literallayout>
      </para>
    </listitem>
  </itemizedlist>
</section>
',
                [
                    'Element section has extra content: itemizedlist',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestValidate
     */
    public function testValidate($input, $expectedErrors)
    {
        $document = new DOMDocument();
        $document->loadXML($input);

        $validator = $this->getConversionValidator();
        $errors = $validator->validateDocument($document);

        $this->assertEquals(count($expectedErrors), count($errors));

        foreach ($errors as $index => $error) {
            $this->assertStringEndsWith($expectedErrors[$index], $error);
        }
    }

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\ValidatorInterface
     */
    protected $validator;

    /**
     * @return \Ibexa\FieldTypeRichText\RichText\ValidatorInterface
     */
    protected function getConversionValidator()
    {
        $validationSchema = $this->getConversionValidationSchemas();
        if ($validationSchema !== null && $this->validator === null) {
            $this->validator = new Validator($validationSchema);
        }

        return $this->validator;
    }

    /**
     * Return an array of absolute paths to validation schemas.
     *
     * @return string[]
     */
    protected function getConversionValidationSchemas()
    {
        return [
            __DIR__ . '/../../../../src/bundle/Resources/richtext/schemas/docbook/ezpublish.rng',
            __DIR__ . '/../../../../src/bundle/Resources/richtext/schemas/docbook/docbook.iso.sch.xsl',
        ];
    }
}

class_alias(DocbookTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Validator\DocbookTest');
