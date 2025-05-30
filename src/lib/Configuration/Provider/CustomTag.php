<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTemplateConfigMapper;

/**
 * Custom Tags configuration provider.
 *
 * @internal For internal use by RichText package
 *
 * @phpstan-type TConfigOutput array{
 *     label: string,
 *     description: string,
 *     isInline: bool,
 *     icon?: string,
 *     attributes?: array<string, TConfigAttributeOutput>
 * }
 * @phpstan-type TConfigAttributeOutput array{
 *     type: string,
 *     required: bool,
 *     defaultValue: mixed,
 *     label: string,
 *     choices?: array<string>,
 *     choicesLabel?: array<string, string>,
 * }
 */
final class CustomTag implements Provider
{
    private ConfigResolverInterface $configResolver;

    private CustomTemplateConfigMapper $customTagConfigurationMapper;

    public function __construct(
        ConfigResolverInterface $configResolver,
        CustomTemplateConfigMapper $customTagConfigurationMapper
    ) {
        $this->configResolver = $configResolver;
        $this->customTagConfigurationMapper = $customTagConfigurationMapper;
    }

    public function getName(): string
    {
        return 'customTags';
    }

    /**
     * @phpstan-return array<TConfigOutput> RichText Custom Tags config
     */
    public function getConfiguration(): array
    {
        if ($this->configResolver->hasParameter('fieldtypes.ibexa_richtext.custom_tags')) {
            return $this->customTagConfigurationMapper->mapConfig(
                $this->configResolver->getParameter('fieldtypes.ibexa_richtext.custom_tags')
            );
        }

        return [];
    }
}
