<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;

final class ConfigurableProvider implements Provider
{
    private Provider $inner;

    /** @var iterable<\Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderConfiguratorInterface> */
    private iterable $configurators;

    /**
     * @param iterable<\Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderConfiguratorInterface> $configurators
     */
    public function __construct(
        Provider $inner,
        iterable $configurators
    ) {
        $this->inner = $inner;
        $this->configurators = $configurators;
    }

    public function getName(): string
    {
        return $this->inner->getName();
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        $configuration = $this->inner->getConfiguration();
        foreach ($this->configurators as $configurator) {
            $configuration = $configurator->getConfiguration($configuration);
        }

        return $configuration;
    }
}
