<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection\Compiler;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler\RichTextHtml5ConverterPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RichTextHtml5ConverterPassTest extends AbstractCompilerPassTestCase
{
    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RichTextHtml5ConverterPass());
    }

    public function testCollectProviders(): void
    {
        $configurationResolver = new Definition();
        $this->setDefinition(
            'ibexa.richtext.converter.output.xhtml5',
            $configurationResolver
        );

        $configurationProvider = new Definition();
        $configurationProvider->addTag('ibexa.field_type.richtext.converter.output.xhtml5');
        $this->setDefinition('ezrichtext.converter.test1', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('ibexa.field_type.richtext.converter.output.xhtml5', ['priority' => 10]);
        $this->setDefinition('ezrichtext.converter.test2', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('ibexa.field_type.richtext.converter.output.xhtml5', ['priority' => 5]);
        $this->setDefinition('ezrichtext.converter.test3', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('ibexa.field_type.richtext.converter.output.xhtml5', ['priority' => 5]);
        $this->setDefinition('ezrichtext.converter.test4', $configurationProvider);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'ibexa.richtext.converter.output.xhtml5',
            0,
            [
                new Reference('ezrichtext.converter.test1'),
                new Reference('ezrichtext.converter.test3'),
                new Reference('ezrichtext.converter.test4'),
                new Reference('ezrichtext.converter.test2'),
            ]
        );
    }
}
