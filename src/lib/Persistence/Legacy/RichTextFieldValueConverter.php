<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;

class RichTextFieldValueConverter implements Converter
{
    /**
     * Converts data from $value to $storageFieldValue.
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue): void
    {
        $storageFieldValue->dataText = $value->data;
        $storageFieldValue->sortKeyString = $value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue): void
    {
        $fieldValue->data = $value->dataText ?: Value::EMPTY_VALUE;
        $fieldValue->sortKey = $value->sortKeyString;
    }

    /**
     * Converts field definition data from $fieldDefinition into $storageFieldDefinition.
     */
    public function toStorageFieldDefinition(
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageDefinition,
    ): void {
        // Nothing to store
    }

    /**
     * Converts field definition data from $storageDefinition into $fieldDefinition.
     */
    public function toFieldDefinition(
        StorageFieldDefinition $storageDefinition,
        FieldDefinition $fieldDefinition,
    ): void {
        $fieldDefinition->defaultValue->data = Value::EMPTY_VALUE;
    }

    /**
     * Returns the name of the index column in the attribute table.
     *
     * Returns the name of the index column the datatype uses, which is either
     * "sort_key_int" or "sort_key_string". This column is then used for
     * filtering and sorting for this type.
     */
    public function getIndexColumn(): string
    {
        return 'sort_key_string';
    }
}
