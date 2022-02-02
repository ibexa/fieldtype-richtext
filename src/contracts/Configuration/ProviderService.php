<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\Configuration;

/**
 * RichText configuration provider API.
 *
 * To provide custom configuration implement \Ibexa\Contracts\FieldTypeRichText\Configuration\Provider
 * instead.
 *
 * @see \Ibexa\Contracts\FieldTypeRichText\Configuration\Provider
 */
interface ProviderService
{
    /**
     * Provide RichText package configuration in the form of associative multidimensional array.
     *
     * @return array
     */
    public function getConfiguration(): array;
}

class_alias(ProviderService::class, 'EzSystems\EzPlatformRichText\API\Configuration\ProviderService');
