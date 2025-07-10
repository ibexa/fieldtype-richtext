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
 * @see \Ibexa\FieldTypeRichText\RichText\Converter\Aggregate
 */
class RichTextHtml5ConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('ibexa.richtext.converter.output.xhtml5')) {
            $html5OutputConverterDefinition = $container->getDefinition('ibexa.richtext.converter.output.xhtml5');
            $taggedOutputServiceIds = $container->findTaggedServiceIds(
                'ibexa.field_type.richtext.converter.output.xhtml5'
            );
            $this->setConverterDefinitions($taggedOutputServiceIds, $html5OutputConverterDefinition);
        }

        if ($container->hasDefinition('ibexa.richtext.converter.input.xhtml5')) {
            $html5InputConverterDefinition = $container->getDefinition('ibexa.richtext.converter.input.xhtml5');
            $taggedInputServiceIds = $container->findTaggedServiceIds(
                'ibexa.field_type.richtext.converter.input.xhtml5'
            );
            $this->setConverterDefinitions($taggedInputServiceIds, $html5InputConverterDefinition);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $taggedServiceIds
     */
    protected function setConverterDefinitions(array $taggedServiceIds, Definition $converterDefinition): void
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
     * @param array<int, list<Reference>> $convertersByPriority
     *
     * @return \Symfony\Component\DependencyInjection\Reference[]
     */
    protected function sortConverters(array $convertersByPriority): array
    {
        ksort($convertersByPriority);

        return array_merge(...$convertersByPriority);
    }
}
