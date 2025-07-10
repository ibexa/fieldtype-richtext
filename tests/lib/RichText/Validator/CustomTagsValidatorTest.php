<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Validator\CustomTagsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\CustomTagsValidator
 */
final class CustomTagsValidatorTest extends TestCase
{
    private CustomTagsValidator $validator;

    public function setUp(): void
    {
        // reuse Custom Tags configuration from common test settings
        $commonSettings = Yaml::parseFile(__DIR__ . '/../../_settings/common.yaml');
        $customTagsConfiguration = $commonSettings['parameters']['ibexa.field_type.richtext.custom_tags'];

        $this->validator = new CustomTagsValidator($customTagsConfiguration);
    }

    /**
     * Test validating DocBook document containing Custom Tags.
     *
     * @dataProvider providerForTestValidateDocument
     */
    public function testValidateDocument(DOMDocument $document, array $expectedErrors): void
    {
        self::assertEquals(
            $expectedErrors,
            $this->validator->validateDocument($document)
        );
    }

    /**
     * Data provider for testValidateDocument.
     *
     * @see testValidateDocument
     */
    public function providerForTestValidateDocument(): array
    {
        return [
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="">
  </eztemplate>
</section>
DOCBOOK
                ),
                [
                    'Missing RichText Custom Tag name',
                ],
            ],
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="video">
    <ezcontent>Content</ezcontent>
    <ezconfig>
      <ezvalue key="">Test</ezvalue>
      <ezvalue key="title">Test</ezvalue>
      <ezvalue key="width">360</ezvalue>
    </ezconfig>
  </eztemplate>
</section>
DOCBOOK
                ),
                [
                    "Missing attribute name for RichText Custom Tag 'video'",
                ],
            ],
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="video">
    <ezcontent>Content</ezcontent>
    <ezconfig>
        <ezvalue key="title">Test</ezvalue>
        <ezvalue key="unknown">Test</ezvalue>
        <ezvalue key="width">360</ezvalue>
    </ezconfig>
  </eztemplate>
</section>
DOCBOOK
                ),
                [
                    "Unknown attribute 'unknown' of RichText Custom Tag 'video'",
                ],
            ],
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="video">
    <ezcontent>Content</ezcontent>
    <ezconfig>
      <ezvalue key="autoplay">false</ezvalue>
    </ezconfig>
  </eztemplate>
</section>
DOCBOOK
                ),
                [
                    "The attribute 'title' of RichText Custom Tag 'video' cannot be empty",
                    "The attribute 'width' of RichText Custom Tag 'video' cannot be empty",
                ],
            ],
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="">
  </eztemplate>
  <eztemplate name="undefined_tag">
  </eztemplate>
  <eztemplate name="video">
    <ezcontent>Content</ezcontent>
    <ezconfig>
      <ezvalue key="">Test</ezvalue>
    </ezconfig>
  </eztemplate>
  <eztemplate name="equation">
    <ezcontent>Content</ezcontent>
    <ezconfig>
      <ezvalue key="name">Test</ezvalue>
      <ezvalue key="unknown">Test</ezvalue>
      <ezvalue key="processor">latex</ezvalue>
    </ezconfig>
  </eztemplate>
</section>
DOCBOOK
                ),
                [
                    'Missing RichText Custom Tag name',
                    "Missing configuration for RichText CustomTag: 'undefined_tag'",
                    "Missing attribute name for RichText Custom Tag 'video'",
                    "The attribute 'title' of RichText Custom Tag 'video' cannot be empty",
                    "The attribute 'width' of RichText Custom Tag 'video' cannot be empty",
                    "Unknown attribute 'unknown' of RichText Custom Tag 'equation'",
                ],
            ],
        ];
    }

    /**
     * @param string $source XML source
     */
    protected function createDocument($source): DOMDocument
    {
        $document = new DOMDocument();

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $document->loadXml($source, LIBXML_NOENT);

        return $document;
    }
}
