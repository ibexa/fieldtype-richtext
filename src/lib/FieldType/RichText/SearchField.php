<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Search;

/**
 * Indexable definition for RichText field type.
 */
class SearchField implements Indexable
{
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
                self::extractShortText($document),
                new Search\FieldType\StringField()
            ),
            new Search\Field(
                'fulltext',
                $this->extractText($document->documentElement),
                new Search\FieldType\FullTextField()
            ),
        ];
    }

    /**
     * Extracts text content of the given $node.
     *
     * @param \DOMNode $node
     *
     * @return string
     */
    private function extractText(DOMNode $node)
    {
        $text = '';

        if ($node->childNodes !== null && $node->childNodes->count() > 0) {
            foreach ($node->childNodes as $child) {
                $text .= $this->extractText($child);
            }
        } elseif (!empty($node->nodeValue)) {
            $text .= $node->nodeValue . ' ';
        }

        return $text;
    }

    /**
     * Extracts short text content of the given $document.
     *
     * @internal Only for use by RichText FieldType itself.
     *
     * @param \DOMDocument $document
     *
     * @return string
     */
    public static function extractShortText(DOMDocument $document): string
    {
        $result = null;
        // try to extract first paragraph/tag
        if ($section = $document->documentElement->firstChild) {
            $textDom = $section->firstChild;

            if ($textDom && $textDom->hasChildNodes()) {
                $result = $textDom->firstChild->textContent;
            } elseif ($textDom) {
                $result = $textDom->textContent;
            }
        }

        if ($result === null) {
            $result = $document->documentElement->textContent;
        }

        // In case of newlines, extract first line. Also limit size to 255 which is maxsize on sql impl.
        $lines = preg_split('/\r\n|\n|\r/', trim($result), -1, PREG_SPLIT_NO_EMPTY);

        return empty($lines) ? '' : trim(mb_substr($lines[0], 0, 255));
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
