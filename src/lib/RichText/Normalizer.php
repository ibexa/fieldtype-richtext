<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

/**
 * Abstract class for XML normalization of string input.
 */
abstract class Normalizer
{
    /**
     * Check if normalizer accepts given $input for normalization.
     */
    abstract public function accept(string $input): bool;

    /**
     * Normalizes a given $input and returns the result.
     */
    abstract public function normalize(string $input): string;
}
