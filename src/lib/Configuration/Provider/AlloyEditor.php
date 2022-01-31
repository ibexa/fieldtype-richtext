<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\Provider;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper;

/**
 * AlloyEditor configuration provider.
 *
 * @internal For internal use by RichText package
 */
final class AlloyEditor implements Provider
{
    /** @var array */
    private $alloyEditorConfiguration;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    /** @var \Ibexa\FieldTypeRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper */
    private $onlineEditorConfigMapper;

    public function __construct(
        array $alloyEditorConfiguration,
        ConfigResolverInterface $configResolver,
        OnlineEditorConfigMapper $onlineEditorConfigMapper
    ) {
        $this->alloyEditorConfiguration = $alloyEditorConfiguration;
        $this->configResolver = $configResolver;
        $this->onlineEditorConfigMapper = $onlineEditorConfigMapper;
    }

    public function getName(): string
    {
        return 'alloyEditor';
    }

    /**
     * @return array AlloyEditor config
     */
    public function getConfiguration(): array
    {
        return [
            'extraPlugins' => $this->getExtraPlugins(),
            'toolbars' => $this->getToolbars(),
            'classes' => $this->getCssClasses(),
            'attributes' => $this->getDataAttributes(),
            'nativeAttributes' => $this->getNativeAttributes(),
        ];
    }

    /**
     * @return array Toolbars configuration
     */
    private function getToolbars(): array
    {
        $toolbarsConfiguration = $this->getSiteAccessConfigArray(RichText::TOOLBARS_SA_SETTINGS_ID);
        $toolbars = [];

        foreach ($toolbarsConfiguration as $toolbar => $configuration) {
            $toolbars[$toolbar] = [
                'buttons' => $this->getToolbarButtons($configuration['buttons'] ?? []),
            ];
        }

        return $toolbars;
    }

    /**
     * @return string[] List of visible buttons
     */
    private function getToolbarButtons(array $buttons): array
    {
        $buttons = array_filter($buttons, static function (array $value): bool {
            return $value['visible'];
        });

        uasort($buttons, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        return array_keys($buttons);
    }

    /**
     * @return array Custom plugins
     */
    private function getExtraPlugins(): array
    {
        return $this->alloyEditorConfiguration['extra_plugins'] ?? [];
    }

    /**
     * Get custom CSS classes defined by the SiteAccess-aware configuration.
     *
     * @return array
     */
    private function getCssClasses(): array
    {
        return $this->onlineEditorConfigMapper->mapCssClassesConfiguration(
            $this->getSiteAccessConfigArray(RichText::CLASSES_SA_SETTINGS_ID)
        );
    }

    /**
     * Get custom data attributes defined by the SiteAccess-aware configuration.
     *
     * @return array
     */
    private function getDataAttributes(): array
    {
        return $this->onlineEditorConfigMapper->mapDataAttributesConfiguration(
            $this->getSiteAccessConfigArray(RichText::ATTRIBUTES_SA_SETTINGS_ID)
        );
    }

    /**
     * @return array Native attributes
     */
    private function getNativeAttributes(): array
    {
        return $this->alloyEditorConfiguration['native_attributes'] ?? [];
    }

    /**
     * Get configuration array from the SiteAccess-aware configuration, checking first for its existence.
     *
     * @param string $paramName
     *
     * @return array
     */
    private function getSiteAccessConfigArray(string $paramName): array
    {
        return $this->configResolver->hasParameter($paramName)
            ? $this->configResolver->getParameter($paramName)
            : [];
    }
}

class_alias(AlloyEditor::class, 'EzSystems\EzPlatformRichText\Configuration\Provider\AlloyEditor');
