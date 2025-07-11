<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\DependencyInjection;

use Ibexa\Contracts\Core\Container\Encore\ConfigurationDumper as IbexaEncoreConfigurationDumper;
use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * Ibexa RichText Field Type Bundle extension.
 */
class IbexaFieldTypeRichTextExtension extends Extension implements PrependExtensionInterface
{
    public const string EXTENSION_NAME = 'ibexa_fieldtype_richtext';

    public const string RICHTEXT_CUSTOM_STYLES_PARAMETER = 'ibexa.field_type.richtext.custom_styles';
    public const string RICHTEXT_CUSTOM_TAGS_PARAMETER = 'ibexa.field_type.richtext.custom_tags';
    public const string RICHTEXT_ALLOY_EDITOR_PARAMETER = 'ibexa.field_type.richtext.alloy_editor';
    public const string RICHTEXT_CONFIGURATION_PROVIDER_TAG = 'ibexa.field_type.richtext.configuration.provider';

    private const string RICHTEXT_TEXT_TOOLBAR_NAME = 'text';

    private const array WEBPACK_CONFIG_NAMES = [
        'ibexa.richtext.config.manager.js' => [
            'ibexa.richtext.config.manager.js' => [],
        ],
    ];

    public function getAlias(): string
    {
        return self::EXTENSION_NAME;
    }

    /**
     * Load Ibexa RichText Field Type Bundle configuration.
     *
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $settingsLoader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config/settings')
        );
        $settingsLoader->load('fieldtypes.yaml');
        $settingsLoader->load('fieldtype_services.yaml');
        $settingsLoader->load('fieldtype_external_storages.yaml');
        $settingsLoader->load('indexable_fieldtypes.yaml');
        $settingsLoader->load('storage_engines/legacy/migrate_rich_text_namespaces.yaml');
        $settingsLoader->load('storage_engines/legacy/external_storage_gateways.yaml');
        $settingsLoader->load('storage_engines/legacy/field_value_converters.yaml');

        $container
            ->registerForAutoconfiguration(Provider::class)
            ->addTag(static::RICHTEXT_CONFIGURATION_PROVIDER_TAG);

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('fieldtype_services.yaml');
        $loader->load('rest.yaml');
        $loader->load('templating.yaml');
        $loader->load('form.yaml');
        $loader->load('translation.yaml');
        $loader->load('configuration.yaml');
        $loader->load('api.yaml');
        $loader->load('command.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $this->registerRichTextConfiguration($config, $container);

        (new IbexaEncoreConfigurationDumper($container))->dumpCustomConfiguration(
            self::WEBPACK_CONFIG_NAMES
        );
    }

    /**
     * Register parameters of global RichText configuration.
     */
    private function registerRichTextConfiguration(array $config, ContainerBuilder $container): void
    {
        $customTagsConfig = $config['custom_tags'] ?? [];
        $customStylesConfig = $config['custom_styles'] ?? [];
        $alloyEditorConfig = $config['alloy_editor'] ?? [];

        /** @var array<int, string> $availableSiteAccesses */
        $availableSiteAccesses = $container->hasParameter('ibexa.site_access.list')
            ? $container->getParameter('ibexa.site_access.list')
            : [];

        $this->validateCustomTemplatesConfig(
            $availableSiteAccesses,
            $customTagsConfig,
            'custom_tags',
            'Tag',
            $container
        );
        $this->validateInlineCustomTagToolbarsConfig(
            $availableSiteAccesses,
            $customTagsConfig,
            $container,
        );
        $this->validateCustomTemplatesConfig(
            $availableSiteAccesses,
            $customStylesConfig,
            'custom_styles',
            'Style',
            $container
        );

        $container->setParameter(static::RICHTEXT_CUSTOM_TAGS_PARAMETER, $customTagsConfig);
        $container->setParameter(static::RICHTEXT_CUSTOM_STYLES_PARAMETER, $customStylesConfig);
        $container->setParameter(static::RICHTEXT_ALLOY_EDITOR_PARAMETER, $alloyEditorConfig);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @throws \Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependIbexaConfiguration($container);
        $this->prependIbexaRichTextConfiguration($container);
        $this->prependBazingaJsTranslationConfiguration($container);
        $this->prependJMSTranslation($container);
    }

    private function prependIbexaConfiguration(ContainerBuilder $container): void
    {
        $coreExtensionConfigFile = realpath(__DIR__ . '/../Resources/config/prepend/ezpublish.yaml');
        $container->prependExtensionConfig('ibexa', Yaml::parseFile($coreExtensionConfigFile));
        $container->addResource(new FileResource($coreExtensionConfigFile));
    }

    private function prependIbexaRichTextConfiguration(ContainerBuilder $container): void
    {
        $richTextExtensionConfigFile = realpath(__DIR__ . '/../Resources/config/prepend/ibexa_richtext.yaml');
        $container->prependExtensionConfig(self::EXTENSION_NAME, Yaml::parseFile($richTextExtensionConfigFile));
        $container->addResource(new FileResource($richTextExtensionConfigFile));
    }

    private function prependBazingaJsTranslationConfiguration(ContainerBuilder $container): void
    {
        $configFile = __DIR__ . '/../Resources/config/bazinga_js_translation.yaml';
        $config = Yaml::parseFile($configFile);
        $container->prependExtensionConfig('bazinga_js_translation', $config);
        $container->addResource(new FileResource($configFile));
    }

    private function prependJMSTranslation(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('jms_translation', [
            'configs' => [
                'ibexa_fieldtype_richtext' => [
                    'dirs' => [
                        __DIR__ . '/../../../src/',
                    ],
                    'output_dir' => __DIR__ . '/../Resources/translations/',
                    'output_format' => 'xliff',
                    'excluded_dirs' => ['Behat', 'Tests', 'node_modules'],
                    'extractors' => [
                        'ibexa.translation_extractor.field_type.ibexa_richtext.custom_tags',
                        'ibexa.translation_extractor.field_type.ibexa_richtext.custom_tags.choices',
                    ],
                ],
            ],
        ]);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * Validate Custom Templates (Tags, Styles) SiteAccess-defined configuration against a global one.
     *
     * @param array<int, string> $availableSiteAccesses a list of available SiteAccesses
     * @param array<string, mixed> $config Custom Template configuration
     * @param string $nodeName Custom Template node name
     * @param string $type Custom Template type name
     */
    private function validateCustomTemplatesConfig(
        array $availableSiteAccesses,
        array $config,
        string $nodeName,
        string $type,
        ContainerBuilder $container
    ): void {
        $namespace = 'ibexa.site_access.config';
        $definedCustomTemplates = array_keys($config);
        // iterate manually through available Scopes as scope context is not available
        foreach ($availableSiteAccesses as $siteAccessName) {
            $enabledTemplatesParamName = "{$namespace}.{$siteAccessName}.fieldtypes.ibexa_richtext.{$nodeName}";
            if (!$container->hasParameter($enabledTemplatesParamName)) {
                continue;
            }

            foreach ($container->getParameter($enabledTemplatesParamName) as $customTemplateName) {
                if (!in_array($customTemplateName, $definedCustomTemplates)) {
                    throw new InvalidConfigurationException(
                        "Unknown RichText Custom {$type} '{$customTemplateName}' (required by the '{$siteAccessName}' SiteAccess)"
                    );
                }
            }
        }
    }

    /**
     * Validate the presence of inline Custom Tags in Toolbars.
     *
     * @param array<int, string> $availableSiteAccesses a list of available SiteAccesses
     * @param array<int, string> $customTagsConfig Custom Tags configuration
     */
    private function validateInlineCustomTagToolbarsConfig(
        array $availableSiteAccesses,
        array $customTagsConfig,
        ContainerBuilder $container
    ): void {
        $customTags = $this->getInlineCustomTags($customTagsConfig);
        foreach ($this->getToolbarsBySiteAccess($availableSiteAccesses, $container) as $siteAccess => $toolbar) {
            foreach ($toolbar as $toolbarName => $toolbarContent) {
                $this->checkForInlineTagsInToolbar($toolbarName, $toolbarContent, $customTags, $siteAccess);
            }
        }
    }

    /**
     * @return iterable<array> Iterable containing arrays with toolbars and their buttons
     */
    private function getToolbarsBySiteAccess(array $availableSiteAccesses, ContainerBuilder $container): iterable
    {
        foreach ($availableSiteAccesses as $siteAccessName) {
            $paramName = "ibexa.site_access.config.{$siteAccessName}.fieldtypes.ibexa_richtext.toolbars";
            if (!$container->hasParameter($paramName)) {
                continue;
            }

            yield $paramName => $container->getParameter($paramName);
        }
    }

    /**
     * @return string[]
     */
    private function getInlineCustomTags(array $customTagsConfig): array
    {
        $customTags = array_filter(
            $customTagsConfig,
            static function (array $customTag): bool {
                return $customTag['is_inline'] ?? false;
            }
        );

        return array_keys($customTags);
    }

    /**
     * @param array<string, mixed> $toolbarContent
     * @param array<int, string> $customTags
     */
    private function checkForInlineTagsInToolbar(
        string $toolbarName,
        array $toolbarContent,
        array $customTags,
        string $siteAccess
    ): void {
        // "text" toolbar is the only one that can contain inline tags
        if (self::RICHTEXT_TEXT_TOOLBAR_NAME === $toolbarName) {
            return;
        }

        foreach ($toolbarContent['buttons'] as $buttonName => $buttonConfig) {
            if (in_array($buttonName, $customTags, true)) {
                throw new InvalidConfigurationException(
                    sprintf(
                        "Toolbar '%s' configured in the '%s' scope cannot contain Custom Tag '%s'. Inline Custom Tags are not allowed in Toolbars other than '%s'.",
                        $toolbarName,
                        $siteAccess,
                        $buttonName,
                        self::RICHTEXT_TEXT_TOOLBAR_NAME
                    )
                );
            }
        }
    }
}
