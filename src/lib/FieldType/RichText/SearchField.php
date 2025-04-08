<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;

/**
 * Indexable definition for RichText field type.
 */
class SearchField implements Indexable
{
    private TextExtractorInterface $shortTextExtractor;

    private TextExtractorInterface $fullTextExtractor;

    public function __construct(
        TextExtractorInterface $shortTextExtractor,
        TextExtractorInterface $fullTextExtractor
    ) {
        $this->shortTextExtractor = $shortTextExtractor;
        $this->fullTextExtractor = $fullTextExtractor;
    }

    /**
     * Get index data for field for search backend.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDefinition
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function getIndexData(Field $field, FieldDefinition $fieldDefinition)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);

        return [
            new Search\Field(
                'value',
                $this->shortTextExtractor->extractText($document),
                new Search\FieldType\StringField()
            ),
            new Search\Field(
                'fulltext',
                $this->fullTextExtractor->extractText($document),
                new Search\FieldType\FullTextField()
            ),
        ];
    }

    /**
     * Get index field types for search backend.
     *
     * @return \Ibexa\Contracts\Core\Search\FieldType[]
     */
    public function getIndexDefinition()
    {
        return [
            'value' => new Search\FieldType\StringField(),
        ];
    }

    /**
     * Get name of the default field to be used for matching.
     *
     * As field types can index multiple fields (see MapLocation field type's
     * implementation of this interface), this method is used to define default
     * field for matching. Default field is typically used by Field criterion.
     *
     * @return string
     */
    public function getDefaultMatchField()
    {
        return 'value';
    }

    /**
     * Get name of the default field to be used for sorting.
     *
     * As field types can index multiple fields (see MapLocation field type's
     * implementation of this interface), this method is used to define default
     * field for sorting. Default field is typically used by Field sort clause.
     *
     * @return string
     */
    public function getDefaultSortField()
    {
        return $this->getDefaultMatchField();
    }
}

class_alias(SearchField::class, 'EzSystems\EzPlatformRichText\eZ\FieldType\RichText\SearchField');
