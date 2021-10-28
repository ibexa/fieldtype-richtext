<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Form\DataTransformer;

use eZ\Publish\API\Repository\FieldType;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * DataTransformer for RichText\Value.
 */
class RichTextValueTransformer implements DataTransformerInterface
{
    /** @var FieldType */
    private $fieldType;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter Converter
     */
    protected $docbookToXhtml5EditConverter;

    public function __construct(FieldType $fieldType, Converter $docbookToXhtml5EditConverter)
    {
        $this->fieldType = $fieldType;
        $this->docbookToXhtml5EditConverter = $docbookToXhtml5EditConverter;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function transform($value)
    {
        if (!$value instanceof Value) {
            return '';
        }

        return $this->docbookToXhtml5EditConverter->convert($value->xml)->saveXML();
    }

    /**
     * @param mixed $value
     *
     * @return Value|null
     */
    public function reverseTransform($value)
    {
        if ($value === null || empty($value)) {
            return $this->fieldType->getEmptyValue();
        }

        return $this->fieldType->fromHash(['xml' => $value]);
    }
}

class_alias(RichTextValueTransformer::class, 'EzSystems\EzPlatformRichText\Form\DataTransformer\RichTextValueTransformer');
