<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\TextExtractor;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected TextExtractorInterface $textExtractor;

    /**
     * @dataProvider providerForTestExtractText
     */
    public function testExtractText(string $docBookXml, string $expectedText): void
    {
        $document = new DOMDocument();
        $document->loadXML($docBookXml);

        self::assertEquals(
            $expectedText,
            $this->textExtractor->extractText($document)
        );
    }

    /**
     * @return array<string, array<string>>
     */
    abstract public function providerForTestExtractText(): array;
}
