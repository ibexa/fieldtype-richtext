<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\Configuration;

interface ProviderConfiguratorInterface
{
    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(array $configuration): array;
}
