<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Validator\CustomTemplateValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test RichText CustomTemplateValidator.
 *
 * @see \Ibexa\FieldTypeRichText\FieldType\RichText\CustomTemplateValidator
 */
class CustomTemplateValidatorTest extends TestCase
{
    private CustomTemplateValidator $validator;

    public function setUp(): void
    {
        // reuse Custom Tags configuration from common test settings
        $commonSettings = Yaml::parseFile(__DIR__ . '/../../_settings/common.yaml');
        $customTagsConfiguration = $commonSettings['parameters']['ibexa.field_type.richtext.custom_tags'];
        $customStylesConfiguration = $commonSettings['parameters']['ibexa.field_type.richtext.custom_styles'];

        $this->validator = new CustomTemplateValidator($customTagsConfiguration, $customStylesConfiguration);
    }

    /**
     * Test validating DocBook document containing Custom Tags.
     *
     * @dataProvider providerForTestValidateDocument
     *
     * @param list<string> $expectedErrors
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
     * @return list<array{DOMDocument, list<string>}>
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
  <eztemplate name="highlighted_block">
    <ezcontent>Important content</ezcontent>
  </eztemplate>
</section>
DOCBOOK
                ),
                [],
            ],
            [
                $this->createDocument(
                    <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="non_existing_style">
    <ezcontent>Text</ezcontent>
  </eztemplate>
</section>
DOCBOOK
                ),
                [],
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
                    "Missing attribute name for RichText Custom Tag 'video'",
                    "The attribute 'title' of RichText Custom Tag 'video' cannot be empty",
                    "The attribute 'width' of RichText Custom Tag 'video' cannot be empty",
                    "Unknown attribute 'unknown' of RichText Custom Tag 'equation'",
                ],
            ],
        ];
    }

    /**
     * Test that defined but not configured yet Custom Tag doesn't cause validation error.
     */
    public function testValidateDocumentAcceptsLegacyTags(): void
    {
        $document = $this->createDocument(
            <<<DOCBOOK
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
         xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
         version="5.0-variant ezpublish-1.0">
  <eztemplate name="undefined_tag">
    <ezcontent>Undefined</ezcontent>
    <ezconfig>
      <ezvalue key="title">Test</ezvalue>
    </ezconfig>
  </eztemplate>
</section>
DOCBOOK
        );

        self::assertEmpty($this->validator->validateDocument($document));
    }

    protected function createDocument(string $source): DOMDocument
    {
        $document = new DOMDocument();

        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $document->loadXml($source, LIBXML_NONET);

        return $document;
    }
}

class_alias(CustomTemplateValidator::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Validator\CustomTagsValidatorTest');
