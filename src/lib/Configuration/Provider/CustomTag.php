<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\Core\MVC\ConfigResolverInterface;
use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTemplateConfigMapper;

/**
 * Custom Tags configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class CustomTag implements Provider
{
    /** @var \Ibexa\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag */
    private $customTagConfigurationMapper;

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
     * @return array RichText Custom Tags config
     */
    public function getConfiguration(): array
    {
        if ($this->configResolver->hasParameter('fieldtypes.ezrichtext.custom_tags')) {
            return $this->customTagConfigurationMapper->mapConfig(
                $this->configResolver->getParameter('fieldtypes.ezrichtext.custom_tags')
            );
        }

        return [];
    }
}

class_alias(CustomTag::class, 'EzSystems\EzPlatformRichText\Configuration\Provider\CustomTag');
