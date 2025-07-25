<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

/**
 * Aggregate converter converts using configured converters in prioritized order.
 */
class Aggregate implements Converter
{
    /**
     * An array of converters, sorted by priority.
     *
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter[]
     */
    protected array $converters = [];

    /**
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\Converter[] $converters An array of Converters, sorted by priority
     */
    public function __construct(array $converters = [])
    {
        $this->converters = $converters;
    }

    /**
     * Performs conversion of the given $document using configured converters.
     */
    public function convert(DOMDocument $document): DOMDocument
    {
        foreach ($this->converters as $converter) {
            $document = $converter->convert($document);
        }

        return $document;
    }

    public function addConverter(Converter $converter): void
    {
        $this->converters[] = $converter;
    }
}
