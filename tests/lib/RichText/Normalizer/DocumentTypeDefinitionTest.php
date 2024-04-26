<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Normalizer;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Normalizer\DocumentTypeDefinition;
use PHPUnit\Framework\TestCase;

class DocumentTypeDefinitionTest extends TestCase
{
    public function providerForTestNormalize()
    {
        return [
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>Behold the pound pudding in a pond: &pound;</p>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE section [
    <!ENTITY pound "&#163;">
]>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>Behold the pound pudding in a pond: &pound;</p>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>Behold the pound pudding in a pond: £</p>
</section>',
            ],
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/weird_drink.dtd',
                '<?xml version="1.0"

 encoding="UTF-8"?>
<section
xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>You will need &ingredients;.</p>
  <p>Then you &recipe;.</p>
  <p>Serve chilled.</p>
  <p>The price is &price;&yen;.</p>
</section>',
                '<?xml version="1.0"

 encoding="UTF-8"?>
<!DOCTYPE section [
    <!ENTITY ingredients "chili pepper, black pepper, bat wings (dried and grounded) and tomato juice">
    <!ENTITY recipe "combine the ingredients and shake">
    <!ENTITY price "165">
    <!ENTITY yen "&#165;">
]>
<section
xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>You will need &ingredients;.</p>
  <p>Then you &recipe;.</p>
  <p>Serve chilled.</p>
  <p>The price is &price;&yen;.</p>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>You will need chili pepper, black pepper, bat wings (dried and grounded) and tomato juice.</p>
  <p>Then you combine the ingredients and shake.</p>
  <p>Serve chilled.</p>
  <p>The price is 165¥.</p>
</section>',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestNormalize
     *
     * @param string $documentElement
     * @param string $namespace
     * @param string $dtdPath
     * @param string $input
     */
    public function testAccept($documentElement, $namespace, $dtdPath, $input)
    {
        $normalizer = $this->getNormalizer($documentElement, $namespace, $dtdPath);

        self::assertTrue($normalizer->accept($input));
    }

    /**
     * @dataProvider providerForTestNormalize
     *
     * @param string $documentElement
     * @param string $namespace
     * @param string $dtdPath
     * @param string $input Ignored
     */
    public function testAcceptNoXmlDeclaration($documentElement, $namespace, $dtdPath, $input)
    {
        $normalizer = $this->getNormalizer($documentElement, $namespace, $dtdPath);

        self::assertTrue($normalizer->accept(
            <<<XML
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>You will need chili pepper, black pepper, bat wings (dried and grounded) and tomato juice.</p>
  <p>Then you combine the ingredients and shake.</p>
  <p>Serve chilled.</p>
  <p>The price is 165¥.</p>
</section>
XML
        ));
    }

    /**
     * @dataProvider providerForTestNormalize
     *
     * @param string $documentElement
     * @param string $namespace
     * @param string $dtdPath
     * @param string $input
     * @param string $expectedOutput
     * @param string $expectedSaved
     */
    public function testNormalize($documentElement, $namespace, $dtdPath, $input, $expectedOutput, $expectedSaved)
    {
        $normalizer = $this->getNormalizer($documentElement, $namespace, $dtdPath);

        $output = $normalizer->normalize($input);

        self::assertEquals($expectedOutput, $output);

        $normalizedDocument = new DOMDocument();
        $normalizedDocument->loadXML($output, LIBXML_NOENT);
        $expectedDocument = new DOMDocument();
        $expectedDocument->loadXML($expectedSaved, LIBXML_NOENT);

        self::assertEquals($expectedDocument, $normalizedDocument);
    }

    public function providerForTestRefuse()
    {
        return [
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '`eZ` flavored **markdown**',
            ],
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '<?xml version="1.0" encoding="UTF-8"?>this is not exactly well formed...',
            ],
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '<?xml version="1.0" encoding="UTF-8"?>
<truck xmlns="http://example.com/something">
  <helicopter>Something something tra la la</helicopter>
</truck>',
            ],
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <para ezxhtml:class="paraClass">This is a paragraph.</para>
</section>',
            ],
            [
                'section',
                'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit',
                __DIR__ . '/_fixtures/pound.dtd',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns:xhtml="http://ibexa.co/namespaces/ezpublish3/xhtml/"
         xmlns:image="http://ibexa.co/namespaces/ezpublish3/image/"
         xmlns:custom="http://ibexa.co/namespaces/ezpublish3/custom/">
  <section>
    <header>This is a heading.</header>
  </section>
</section>
',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRefuse
     *
     * @param string $documentElement
     * @param string $namespace
     * @param string $dtdPath
     * @param string $input
     */
    public function testRefuse($documentElement, $namespace, $dtdPath, $input)
    {
        $normalizer = $this->getNormalizer($documentElement, $namespace, $dtdPath);

        self::assertFalse($normalizer->accept($input));
    }

    protected function getNormalizer($documentElement, $namespace, $dtdPath)
    {
        return new DocumentTypeDefinition($documentElement, $namespace, $dtdPath);
    }
}

class_alias(DocumentTypeDefinitionTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Normalizer\DocumentTypeDefinitionTest');
