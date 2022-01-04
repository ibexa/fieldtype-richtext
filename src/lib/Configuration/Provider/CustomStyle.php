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
 * Custom Styles configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class CustomStyle implements Provider
{
    /** @var \Ibexa\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomStyle */
    private $customStyleConfigurationMapper;

    public function __construct(
        ConfigResolverInterface $configResolver,
        CustomTemplateConfigMapper $customStyleConfigurationMapper
    ) {
        $this->configResolver = $configResolver;
        $this->customStyleConfigurationMapper = $customStyleConfigurationMapper;
    }

    public function getName(): string
    {
        return 'customStyles';
    }

    /**
     * @return array RichText Custom Styles config
     */
    public function getConfiguration(): array
    {
        if ($this->configResolver->hasParameter('fieldtypes.ezrichtext.custom_styles')) {
            return $this->customStyleConfigurationMapper->mapConfig(
                $this->configResolver->getParameter('fieldtypes.ezrichtext.custom_styles')
            );
        }

        return [];
    }
}

class_alias(CustomStyle::class, 'EzSystems\EzPlatformRichText\Configuration\Provider\CustomStyle');
