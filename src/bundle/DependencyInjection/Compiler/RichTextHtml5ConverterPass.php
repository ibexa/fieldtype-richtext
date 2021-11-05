<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass for the RichText Aggregate converter tags.
 *
 * @see \EzSystems\EzPlatformRichText\eZ\RichText\Converter\Aggregate
 */
class RichTextHtml5ConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('ezrichtext.converter.output.xhtml5')) {
            $html5OutputConverterDefinition = $container->getDefinition('ezrichtext.converter.output.xhtml5');
            $taggedOutputServiceIds = $container->findTaggedServiceIds('ezrichtext.converter.output.xhtml5');
            $this->setConverterDefinitions($taggedOutputServiceIds, $html5OutputConverterDefinition);
        }

        if ($container->hasDefinition('ezrichtext.converter.input.xhtml5')) {
            $html5InputConverterDefinition = $container->getDefinition('ezrichtext.converter.input.xhtml5');
            $taggedInputServiceIds = $container->findTaggedServiceIds('ezrichtext.converter.input.xhtml5');
            $this->setConverterDefinitions($taggedInputServiceIds, $html5InputConverterDefinition);
        }
    }

    /**
     * @param array $taggedServiceIds
     * @param \Symfony\Component\DependencyInjection\Definition $converterDefinition
     */
    protected function setConverterDefinitions(array $taggedServiceIds, Definition $converterDefinition)
    {
        $convertersByPriority = [];
        foreach ($taggedServiceIds as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? (int)$tag['priority'] : 0;
                $convertersByPriority[$priority][] = new Reference($id);
            }
        }

        if (count($convertersByPriority) > 0) {
            $converterDefinition->setArguments(
                [$this->sortConverters($convertersByPriority)]
            );
        }
    }

    /**
     * Transforms a two-dimensional array of converters, indexed by priority,
     * into a flat array of Reference objects.
     *
     * @param array $convertersByPriority
     *
     * @return \Symfony\Component\DependencyInjection\Reference[]
     */
    protected function sortConverters(array $convertersByPriority)
    {
        ksort($convertersByPriority);

        return array_merge(...$convertersByPriority);
    }
}

class_alias(RichTextHtml5ConverterPass::class, 'EzSystems\EzPlatformRichTextBundle\DependencyInjection\Compiler\RichTextHtml5ConverterPass');
