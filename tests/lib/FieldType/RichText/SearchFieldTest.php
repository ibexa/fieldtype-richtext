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
use Ibexa\FieldTypeRichText\FieldType\RichText\SearchField;
use PHPUnit\Framework\TestCase;

final class SearchFieldTest extends TestCase
{
    /** @var \Ibexa\FieldTypeRichText\FieldType\RichText\SearchField */
    private $searchField;

    public function getDataForTestGetIndexData(): array
    {
        return [
            'simple stub' => [
                $this->getSimpleDocBookXml(),
                [
                    new Search\Field(
                        'value',
                        'Welcome to eZ Platform',
                        new Search\FieldType\StringField()
                    ),
                    new Search\Field(
                        'fulltext',
                        "\n   Welcome to eZ Platform \n   eZ Platform  is the new generation DXP from eZ Systems. \n ",
                        new Search\FieldType\FullTextField()
                    ),
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
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->searchField = new SearchField();
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\FieldType\RichText\SearchField::getIndexData
     *
     * @dataProvider getDataForTestGetIndexData
     *
     * @param array $expectedSearchFields
     */
    public function testGetIndexData(string $docBookXml, array $expectedSearchFields): void
    {
        $field = new Field(
            [
                'id' => 1,
                'type' => 'ezrichtext',
                'value' => new FieldValue(['data' => $docBookXml]),
            ]
        );
        $fieldDefinition = new FieldDefinition();

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
  <title ezxhtml:level="2">Welcome to eZ Platform</title>
  <para><link xlink:href="ezurl://1" xlink:show="none">eZ Platform</link> is the new generation DXP from eZ Systems.</para>
</section>
XML;
    }

    private function getEmptyXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><section></section>';
    }
}

class_alias(SearchFieldTest::class, 'EzSystems\Tests\EzPlatformRichText\FieldType\RichText\SearchFieldTest');
