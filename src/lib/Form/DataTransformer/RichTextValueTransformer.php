<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Form\DataTransformer;

use Ibexa\Contracts\Core\Repository\FieldType;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * DataTransformer for RichText\Value.
 */
class RichTextValueTransformer implements DataTransformerInterface
{
    private FieldType $fieldType;

    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter Converter
     */
    protected Converter $docbookToXhtml5EditConverter;

    public function __construct(FieldType $fieldType, Converter $docbookToXhtml5EditConverter)
    {
        $this->fieldType = $fieldType;
        $this->docbookToXhtml5EditConverter = $docbookToXhtml5EditConverter;
    }

    public function transform(mixed $value): string
    {
        if (!$value instanceof Value) {
            return '';
        }

        return $this->docbookToXhtml5EditConverter->convert($value->xml)->saveXML();
    }

    public function reverseTransform(mixed $value): ?Value
    {
        if (empty($value)) {
            return $this->fieldType->getEmptyValue();
        }

        return $this->fieldType->fromHash(['xml' => $value]);
    }
}
