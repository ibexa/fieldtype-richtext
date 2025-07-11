<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension;

use Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension exposing RichText Configuration as ibexa_richtext_config global Twig variable.
 *
 * @internal To access configuration use \Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderService
 */
final class RichTextConfigurationExtension extends AbstractExtension implements GlobalsInterface
{
    private ProviderService $configurationProvider;

    public function __construct(ProviderService $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function getGlobals(): array
    {
        $config = $this->configurationProvider->getConfiguration();

        return [
            'ibexa_richtext_config' => $config,
        ];
    }
}
