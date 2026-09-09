<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RichText extends Constraint
{
    public string $message = 'Invalid value';

    /**
     * @param array<string, mixed>|null $options Deprecated options array
     * @param array<string>|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        ?array $options = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        if ($options !== null) {
            trigger_deprecation(
                'ibexa/fieldtype-richtext',
                '6.0',
                'Passing an options array to "%s" is deprecated, use named arguments instead.',
                static::class
            );

            $message ??= $options['message'] ?? null;
        }

        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
