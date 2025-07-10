<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

use DOMDocument;

/**
 * Interface for rich text conversion.
 */
interface Converter
{
    /**
     * Converts a given $xmlDoc into another \DOMDocument object.
     */
    public function convert(DOMDocument $xmlDoc): DomDocument;
}
