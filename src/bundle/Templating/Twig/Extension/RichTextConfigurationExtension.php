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
 * Twig extension exposing RichText Configuration as ez_richtext_config global Twig variable.
 *
 * @internal To access configuration use \Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderService
 */
final class RichTextConfigurationExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var \Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderService */
    private $configurationProvider;

    public function __construct(ProviderService $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function getGlobals(): array
    {
        $config = $this->configurationProvider->getConfiguration();

        return [
            /** @deprecated ez_richtext_config is deprecated since 4.0, use ibexa_richtext_config instead */
            'ez_richtext_config' => $config,
            'ibexa_richtext_config' => $config,
        ];
    }
}
