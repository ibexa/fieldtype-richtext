<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\DependencyInjection;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration extends SiteAccessConfiguration
{
    public const CUSTOM_TAG_ATTRIBUTE_TYPES = ['number', 'string', 'boolean', 'choice', 'link'];

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(IbexaFieldTypeRichTextExtension::EXTENSION_NAME);

        $rootNode = $treeBuilder->getRootNode();

        $sections = $rootNode->children();
        $this
            ->addCustomTagsSection($sections);
        $this
            ->addCustomStylesSection($sections);
        $this
            ->addAlloyEditorSection($sections)
            ->end();

        return $treeBuilder;
    }

    /**
     * Define RichText Custom Tags Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezrichtext:
     *     custom_tags:
     * </code>
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $ezRichTextNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function addCustomTagsSection(NodeBuilder $ezRichTextNode)
    {
        return $ezRichTextNode
                ->arrayNode('custom_tags')
                ->normalizeKeys(false)
                // workaround: take into account Custom Tag names when merging configs
                ->useAttributeAsKey('tag')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('template')
                            ->isRequired()
                        ->end()
                        ->scalarNode('icon')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('is_inline')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('attributes')
                            ->useAttributeAsKey('attribute')
                            ->arrayPrototype()
                                ->beforeNormalization()
                                    ->always(
                                        static function ($v) {
                                            // Workaround: set empty value to be able to unset it later on (see validation for "choices")
                                            if (!isset($v['choices'])) {
                                                $v['choices'] = [];
                                            }

                                            return $v;
                                        }
                                    )
                                ->end()
                                ->validate()
                                    ->ifTrue(
                                        static function ($v) {
                                            return $v['type'] === 'choice' && !empty($v['required']) && empty($v['choices']);
                                        }
                                    )
                                    ->thenInvalid('List of choices for required choice type attribute has to be non-empty')
                                ->end()
                                ->validate()
                                    ->ifTrue(
                                        static function ($v) {
                                            return !empty($v['choices']) && $v['type'] !== 'choice';
                                        }
                                    )
                                    ->thenInvalid('List of choices is supported by choices type only.')
                                ->end()
                                ->children()
                                    ->enumNode('type')
                                        ->isRequired()
                                        ->values(static::CUSTOM_TAG_ATTRIBUTE_TYPES)
                                    ->end()
                                    ->booleanNode('required')
                                        ->defaultFalse()
                                    ->end()
                                    ->scalarNode('default_value')
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('choices')
                                        ->scalarPrototype()->end()
                                        ->performNoDeepMerging()
                                        ->validate()
                                            ->ifEmpty()->thenUnset()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Define RichText Custom Styles Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezpublish:
     *     ezrichtext:
     *         custom_styles:
     * </code>
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $ezRichTextNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function addCustomStylesSection(NodeBuilder $ezRichTextNode)
    {
        return $ezRichTextNode
                ->arrayNode('custom_styles')
                // workaround: take into account Custom Styles names when merging configs
                    ->useAttributeAsKey('style')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('template')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('inline')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;
    }

    /**
     * Define RichText AlloyEditor Semantic Configuration.
     *
     * The configuration is available at:
     * <code>
     * ezpublish:
     *     ezrichtext:
     *         alloy_editor:
     *             extra_plugins: [plugin1, plugin2]
     *             extra_buttons:
     *                 paragraph: [button1, button2]
     *                 embed: [button1]
     *             native_attributes:
     *                 table: [border]
     * </code>
     *
     * Please note extra_buttons setting will be deprecated in eZ Platform 3.x.
     * The alternative and more flexible solution will be introduced.
     * So you will need to update Online Editor Extra Buttons as part of eZ Platform 3.x upgrade.
     *
     * @param \Symfony\Component\Config\Definition\Builder\NodeBuilder $ezRichTextNode
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function addAlloyEditorSection(NodeBuilder $ezRichTextNode)
    {
        return $ezRichTextNode
                ->arrayNode('alloy_editor')
                    ->children()
                        ->arrayNode('extra_plugins')
                            ->example(['plugin1', 'plugin2'])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('extra_buttons')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->example(['button1', 'button2'])
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                        ->arrayNode('native_attributes')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->example(['border', 'width'])
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;
    }
}
