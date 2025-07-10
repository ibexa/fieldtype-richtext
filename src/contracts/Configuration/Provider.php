<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\Configuration;

/**
 * RichText configuration provider.
 */
interface Provider
{
    /**
     * Get a configuration provider name.
     *
     * Should consist of letters, numbers, dashes, and underscores only.
     */
    public function getName(): string;

    /**
     * Get configuration settings as an associative array.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;
}
