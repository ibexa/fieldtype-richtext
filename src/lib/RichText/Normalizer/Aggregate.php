<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Normalizer;

use Ibexa\FieldTypeRichText\RichText\Normalizer;

/**
 * Aggregate normalizer converts using configured normalizers in prioritized order.
 */
class Aggregate extends Normalizer
{
    /**
     * An array of normalizers, sorted by priority.
     *
     * @var \Ibexa\FieldTypeRichText\RichText\Normalizer[]
     */
    protected array $normalizers;

    /**
     * @param \Ibexa\FieldTypeRichText\RichText\Normalizer[] $normalizers An array of Normalizers, sorted by priority
     */
    public function __construct(array $normalizers = [])
    {
        $this->normalizers = $normalizers;
    }

    /**
     * Check if normalizer accepts given $input for normalization.
     *
     * This implementation always returns true.
     */
    public function accept(string $input): bool
    {
        return true;
    }

    /**
     * Normalizes a given $input by calling aggregated normalizers.
     */
    public function normalize(string $input): string
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->accept($input)) {
                $input = $normalizer->normalize($input);
            }
        }

        return $input;
    }
}
