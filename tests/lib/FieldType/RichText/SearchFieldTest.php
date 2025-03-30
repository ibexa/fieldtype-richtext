<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType\RichText;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;
use Ibexa\FieldTypeRichText\FieldType\RichText\SearchField;
use PHPUnit\Framework\TestCase;

final class SearchFieldTest extends TestCase
{
    /** @var \Ibexa\FieldTypeRichText\FieldType\RichText\SearchField */
    private SearchField $searchField;

    /** @var \Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private TextExtractorInterface $shortTextExtractor;

    /** @var \Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private TextExtractorInterface $fullTextExtractor;

    public function getDataForTestGetIndexData(): array
    {
        $simpleStubShortTextValue = 'Welcome to Ibexa';
        $simpleStubFullTextValue = "\n   Welcome to Ibexa \n   Ibexa  is the new generation DXP from Ibexa. \n ";

        return [
            'simple stub' => [
                $this->getSimpleDocBookXml(),
                [
                    new Search\Field(
                        'value',
                        $simpleStubShortTextValue,
                        new Search\FieldType\StringField()
                    ),
                    new Search\Field(
                        'fulltext',
                        $simpleStubFullTextValue,
                        new Search\FieldType\FullTextField()
                    ),
                ],
                [
                    $simpleStubShortTextValue,
                    $simpleStubFullTextValue,
                ],
            ],
            'empty xml' => [
                $this->getEmptyXml(),
                [
                    new Search\Field(
                        'value',
                        '',
                        new Search\FieldType\StringField()
                    ),
                    new Search\Field(
                        'fulltext',
                        '',
                        new Search\FieldType\FullTextField()
                    ),
                ],
                [
                    '',
                    '',
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->shortTextExtractor = $this->createMock(TextExtractorInterface::class);
        $this->fullTextExtractor = $this->createMock(TextExtractorInterface::class);
        $this->searchField = new SearchField(
            $this->shortTextExtractor,
            $this->fullTextExtractor,
        );
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\FieldType\RichText\SearchField::getIndexData
     *
     * @dataProvider getDataForTestGetIndexData
     *
     * @param array<\Ibexa\Contracts\Core\Search\Field> $expectedSearchFields
     * @param array<string> $expectedTextValues
     */
    public function testGetIndexData(string $docBookXml, array $expectedSearchFields, array $expectedTextValues): void
    {
        $field = new Field(
            [
                'id' => 1,
                'type' => 'ezrichtext',
                'value' => new FieldValue(['data' => $docBookXml]),
            ]
        );
        $fieldDefinition = new FieldDefinition();

        $this->shortTextExtractor
            ->method('extractText')
            ->willReturn($expectedTextValues[0]);

        $this->fullTextExtractor
            ->method('extractText')
            ->willReturn($expectedTextValues[1]);

        self::assertEquals(
            $expectedSearchFields,
            $this->searchField->getIndexData($field, $fieldDefinition)
        );
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
