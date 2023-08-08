<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\TextExtractor;
use PHPUnit\Framework\TestCase;

final class TextExtractorTest extends TestCase
{
    private TextExtractor $textExtractor;

    protected function setUp(): void
    {
        $this->textExtractor = new TextExtractor();
    }

    /**
     * @dataProvider providerForTestExtractText
     */
    public function testExtractText(string $docBookXml, string $expectedText): void
    {
        $document = new DOMDocument();
        $document->loadXML($docBookXml);

        self::assertEquals(
            $expectedText,
            $this->textExtractor->extractText($document->documentElement)
        );
    }

    /**
     * @dataProvider providerForTestExtractShortText
     */
    public function testExtractShortText(string $docBookXml, string $expectedText): void
    {
        $document = new DOMDocument();
        $document->loadXML($docBookXml);

        self::assertEquals(
            $expectedText,
            $this->textExtractor->extractShortText($document)
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerForTestExtractText(): array
    {
        return [
            'simple document' => [
                $this->getSimpleDocBookXml(),
                "\n   Welcome to Ibexa \n   Ibexa  is the new generation DXP from Ibexa. \n ",
            ],
            'empty xml' => [
                $this->getEmptyXml(),
                '',
            ],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function providerForTestExtractShortText(): array
    {
        return [
            'simple document' => [
                $this->getSimpleDocBookXml(),
                'Welcome to Ibexa',
            ],
            'empty xml' => [
                $this->getEmptyXml(),
                '',
            ],
        ];
    }

    private function getSimpleDocBookXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:ezxhtml="https://ezplatform.com/xmlns/docbook/xhtml">
  <title ezxhtml:level="2">Welcome to Ibexa</title>
  <para><link xlink:href="ezurl://1" xlink:show="none">Ibexa</link> is the new generation DXP from Ibexa.</para>
</section>
XML;
    }

    private function getEmptyXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><section></section>';
    }
}
