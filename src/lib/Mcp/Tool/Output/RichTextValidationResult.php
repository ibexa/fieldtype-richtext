<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Mcp\Tool\Output;

/**
 * Result of the `validate_richtext_xml` tool.
 *
 * @experimental
 */
final readonly class RichTextValidationResult
{
    /**
     * @param bool $valid Whether the document would be accepted as a RichText field value.
     * @param list<string> $errors Validation error messages. Empty when the document is valid.
     */
    public function __construct(
        public bool $valid,
        public array $errors,
    ) {
    }
}
