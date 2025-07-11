<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\AbstractFieldTypeParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

/**
 * Configuration parser handling RichText field type related config.
 */
class RichText extends AbstractFieldTypeParser
{
    public const string CLASSES_SA_SETTINGS_ID = 'fieldtypes.ibexa_richtext.classes';
    private const string CLASSES_NODE_KEY = 'classes';

    public const string ATTRIBUTES_SA_SETTINGS_ID = 'fieldtypes.ibexa_richtext.attributes';
    private const string ATTRIBUTES_NODE_KEY = 'attributes';
    private const string ATTRIBUTE_TYPE_NODE_KEY = 'type';
    private const string ATTRIBUTE_TYPE_CHOICE = 'choice';
    private const string ATTRIBUTE_TYPE_BOOLEAN = 'boolean';
    private const string ATTRIBUTE_TYPE_STRING = 'string';
    private const string ATTRIBUTE_TYPE_NUMBER = 'number';

    private const string TOOLBAR_NODE_KEY = 'toolbar';
    public const string TOOLBARS_SA_SETTINGS_ID = 'fieldtypes.ibexa_richtext.' . self::TOOLBAR_NODE_KEY;

    // constants common for OE custom classes and data attributes configuration
    private const string ELEMENT_NODE_KEY = 'element';
    private const string DEFAULT_VALUE_NODE_KEY = 'default_value';
    private const string CHOICES_NODE_KEY = 'choices';
    private const string REQUIRED_NODE_KEY = 'required';
    private const string MULTIPLE_NODE_KEY = 'multiple';

    /**
     * Returns the fieldType identifier the config parser works for.
     * This is to create the right configuration node under system.<siteaccess_name>.fieldtypes.
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_richtext';
    }

    /**
     * Adds semantic configuration definition.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder Node just under ezpublish.system.<siteaccess>
     */
    public function addFieldTypeSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('embed')
                ->info('RichText embed tags configuration.')
                ->children()
                    ->arrayNode('content')
                        ->info('Configuration for RichText block-level Content embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Content embed tags.',
                                    'MyBundle:FieldType/RichText/embed:content.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_denied')
                        ->info('Configuration for RichText block-level Content embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Content embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:content_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_inline')
                        ->info('Configuration for RichText inline-level Content embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Content embed tags.',
                                    'MyBundle:FieldType/RichText/embed:content_inline.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('content_inline_denied')
                        ->info('Configuration for RichText inline-level Content embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Content embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:content_inline_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location')
                        ->info('Configuration for RichText block-level Location embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Location embed tags.',
                                    'MyBundle:FieldType/RichText/embed:location.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_denied')
                        ->info('Configuration for RichText block-level Location embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText block-level Location embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:location_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_inline')
                        ->info('Configuration for RichText inline-level Location embed tags.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Location embed tags.',
                                    'MyBundle:FieldType/RichText/embed:location_inline.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('location_inline_denied')
                        ->info('Configuration for RichText inline-level Location embed tags when embed is not permitted.')
                        ->children()
                            ->append(
                                $this->getTemplateNodeDefinition(
                                    'Template used for rendering RichText inline-level Location embed tags when embed is not permitted.',
                                    'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig'
                                )
                            )
                            ->variableNode('config')
                                ->info('Embed configuration, arbitrary configuration is allowed here.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // RichText Custom Tags configuration (list of Custom Tags enabled for current SiteAccess scope)
        $nodeBuilder
            ->arrayNode('custom_tags')
                ->info('List of RichText Custom Tags enabled for the current scope. The Custom Tags must be defined in ezpublish.ibexa_richtext.custom_tags Node.')
                ->scalarPrototype()->end()
            ->end();

        // RichText Custom Styles configuration (list of Custom Styles enabled for current SiteAccess scope)
        $nodeBuilder
            ->arrayNode('custom_styles')
                ->info('List of RichText Custom Styles enabled for the current scope. The Custom Styles must be defined in ezpublish.ibexa_richtext.custom_styles Node.')
                ->scalarPrototype()->end()
            ->end();

        // RichText Toolbars configuration (defines list of Toolbars and Buttons enabled for current SiteAccess scope)
        $nodeBuilder
            ->arrayNode(self::TOOLBAR_NODE_KEY)
                ->useAttributeAsKey('group_name')
                ->info('List of grouped Toolbars and Buttons enabled for current SiteAccess scope.')
                ->prototype('array')
                    ->children()
                        ->booleanNode('visible')
                            ->info('Is group visible on toolbar?')
                            ->defaultTrue()
                        ->end()
                        ->integerNode('priority')
                            ->info('Defines order in which group appear (255 .. -255).')
                            ->defaultValue(0)
                        ->end()
                        ->arrayNode('buttons')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->booleanNode('visible')
                                        ->info('Is button visible on toolbar?')
                                        ->defaultTrue()
                                    ->end()
                                    ->integerNode('priority')
                                        ->info('Defines order in which buttons appear (255 .. -255).')
                                        ->defaultValue(0)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->buildOnlineEditorConfiguration($nodeBuilder);
    }

    protected function getTemplateNodeDefinition(string $info, string $example): ScalarNodeDefinition
    {
        $templateNodeDefinition = new ScalarNodeDefinition('template');
        $templateNodeDefinition
            ->info($info)
            ->example($example)
            ->isRequired()
            ->cannotBeEmpty();

        return $templateNodeDefinition;
    }

    /**
     * @param string $currentScope
     */
    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        if (!empty($scopeSettings['fieldtypes'])) {
            // Workaround to be able to use Contextualizer::mapConfigArray() which only supports first level entries.
            if (isset($scopeSettings['fieldtypes']['ibexa_richtext']['custom_tags'])) {
                $scopeSettings['fieldtypes.ibexa_richtext.custom_tags'] = $scopeSettings['fieldtypes']['ibexa_richtext']['custom_tags'];
                unset($scopeSettings['fieldtypes']['ibexa_richtext']['custom_tags']);
            }

            if (isset($scopeSettings['fieldtypes']['ibexa_richtext']['custom_styles'])) {
                $scopeSettings['fieldtypes.ibexa_richtext.custom_styles'] = $scopeSettings['fieldtypes']['ibexa_richtext']['custom_styles'];
                unset($scopeSettings['fieldtypes']['ibexa_richtext']['custom_styles']);
            }

            if (isset($scopeSettings['fieldtypes']['ibexa_richtext']['embed'])) {
                foreach ($scopeSettings['fieldtypes']['ibexa_richtext']['embed'] as $type => $embedSettings) {
                    $contextualizer->setContextualParameter(
                        "fieldtypes.ibexa_richtext.embed.{$type}",
                        $currentScope,
                        $scopeSettings['fieldtypes']['ibexa_richtext']['embed'][$type]
                    );
                }
            }

            $onlineEditorSettingsMap = [
                self::CLASSES_NODE_KEY => self::CLASSES_SA_SETTINGS_ID,
                self::ATTRIBUTES_NODE_KEY => self::ATTRIBUTES_SA_SETTINGS_ID,
                self::TOOLBAR_NODE_KEY => self::TOOLBARS_SA_SETTINGS_ID,
            ];
            foreach ($onlineEditorSettingsMap as $key => $settingsId) {
                if (isset($scopeSettings['fieldtypes']['ibexa_richtext'][$key])) {
                    $scopeSettings[$settingsId] = $scopeSettings['fieldtypes']['ibexa_richtext'][$key];
                    unset($scopeSettings['fieldtypes']['ibexa_richtext'][$key]);
                }
            }
        }
    }

    public function postMap(array $config, ContextualizerInterface $contextualizer): void
    {
        $contextualizer->mapConfigArray('fieldtypes.ibexa_richtext.custom_tags', $config);
        $contextualizer->mapConfigArray('fieldtypes.ibexa_richtext.custom_styles', $config);
        $contextualizer->mapConfigArray(self::TOOLBARS_SA_SETTINGS_ID, $config);
        $contextualizer->mapConfigArray('fieldtypes.ibexa_richtext.output_custom_xsl', $config);
        $contextualizer->mapConfigArray('fieldtypes.ibexa_richtext.edit_custom_xsl', $config);
        $contextualizer->mapConfigArray('fieldtypes.ibexa_richtext.input_custom_xsl', $config);
        $contextualizer->mapConfigArray(self::CLASSES_SA_SETTINGS_ID, $config);
        // merge attributes of the same element from different scopes
        $contextualizer->mapConfigArray(
            self::ATTRIBUTES_SA_SETTINGS_ID,
            $config,
            ContextualizerInterface::MERGE_FROM_SECOND_LEVEL
        );
    }

    /**
     * Build configuration nodes strictly related to Online Editor.
     */
    private function buildOnlineEditorConfiguration(NodeBuilder $nodeBuilder): void
    {
        $invalidChoiceCallback = static function (array $v): void {
            $message = sprintf(
                'The default value must be one of the possible choices: %s, instead of "%s" ',
                implode(', ', $v[self::CHOICES_NODE_KEY]),
                $v[self::DEFAULT_VALUE_NODE_KEY]
            );

            throw new InvalidArgumentException($message, 1);
        };

        $nodeBuilder
            ->arrayNode(self::CLASSES_NODE_KEY)
                ->useAttributeAsKey(self::ELEMENT_NODE_KEY)
                ->arrayPrototype()
                    ->validate()
                        ->ifTrue(static function (array $v): bool {
                            return !empty($v[self::DEFAULT_VALUE_NODE_KEY])
                                && !in_array($v[self::DEFAULT_VALUE_NODE_KEY], $v[self::CHOICES_NODE_KEY]);
                        })
                        ->then($invalidChoiceCallback)
                    ->end()
                    ->children()
                        ->arrayNode(self::CHOICES_NODE_KEY)
                            ->scalarPrototype()->end()
                            ->isRequired()
                        ->end()
                        ->booleanNode(self::REQUIRED_NODE_KEY)
                            ->defaultFalse()
                        ->end()
                        ->scalarNode(self::DEFAULT_VALUE_NODE_KEY)
                        ->end()
                        ->booleanNode(self::MULTIPLE_NODE_KEY)
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode(self::ATTRIBUTES_NODE_KEY)
                ->useAttributeAsKey(self::ELEMENT_NODE_KEY)
                ->arrayPrototype()
                    // allow dashes in data attribute name
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->validate()
                            ->always($this->getAttributesValidatorCallback($invalidChoiceCallback))
                        ->end()
                        ->children()
                            ->enumNode(self::ATTRIBUTE_TYPE_NODE_KEY)
                                ->isRequired()
                                ->values(
                                    [
                                        self::ATTRIBUTE_TYPE_CHOICE,
                                        self::ATTRIBUTE_TYPE_BOOLEAN,
                                        self::ATTRIBUTE_TYPE_STRING,
                                        self::ATTRIBUTE_TYPE_NUMBER,
                                    ]
                                )
                            ->end()
                            ->arrayNode(self::CHOICES_NODE_KEY)
                                ->validate()
                                    ->ifEmpty()->thenUnset()
                                ->end()
                                ->scalarPrototype()
                                ->end()
                            ->end()
                            ->booleanNode(self::MULTIPLE_NODE_KEY)->defaultFalse()->end()
                            ->booleanNode(self::REQUIRED_NODE_KEY)->defaultFalse()->end()
                            ->scalarNode(self::DEFAULT_VALUE_NODE_KEY)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Return validation callback which will validate custom data attributes semantic config.
     *
     * The validation validates the following rules:
     * - if a custom data attribute is not of `choice` type, it must not define `choices` list,
     * - a `default_value` of custom data attribute must be the one from `choices` list,
     * - a custom data attribute of `boolean` type must not define `required` setting.
     */
    private function getAttributesValidatorCallback(callable $invalidChoiceCallback): callable
    {
        return static function (array $v) use ($invalidChoiceCallback): array {
            if ($v[self::ATTRIBUTE_TYPE_NODE_KEY] === self::ATTRIBUTE_TYPE_CHOICE
                && !empty($v[self::DEFAULT_VALUE_NODE_KEY])
                && !in_array($v[self::DEFAULT_VALUE_NODE_KEY], $v[self::CHOICES_NODE_KEY])
            ) {
                $invalidChoiceCallback($v);
            } elseif ($v[self::ATTRIBUTE_TYPE_NODE_KEY] === self::ATTRIBUTE_TYPE_BOOLEAN && $v[self::REQUIRED_NODE_KEY]) {
                throw new InvalidArgumentException(
                    sprintf('Boolean type does not support the "%s" setting', self::REQUIRED_NODE_KEY)
                );
            } elseif ($v[self::ATTRIBUTE_TYPE_NODE_KEY] !== self::ATTRIBUTE_TYPE_CHOICE && !empty($v[self::CHOICES_NODE_KEY])) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s type does not support the "%s" setting',
                        ucfirst($v[self::ATTRIBUTE_TYPE_NODE_KEY]),
                        self::CHOICES_NODE_KEY
                    )
                );
            }

            // at this point, for non-choice types, unset choice type-related settings
            if ($v[self::ATTRIBUTE_TYPE_NODE_KEY] !== self::ATTRIBUTE_TYPE_CHOICE) {
                unset($v[self::CHOICES_NODE_KEY], $v[self::MULTIPLE_NODE_KEY]);
            }

            return $v;
        };
    }
}
