<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText;

use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler\RichTextDocbookSchemaPass;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler\RichTextHtml5ConverterPass;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\IbexaFieldTypeRichTextExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Ibexa RichText FieldType Bundle.
 */
class IbexaFieldTypeRichTextBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var \Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension $core */
        $core = $container->getExtension('ibexa');
        $core->addDefaultSettings(__DIR__ . '/Resources/config', ['default_settings.yaml']);

        $container->addCompilerPass(new RichTextHtml5ConverterPass());
        $container->addCompilerPass(new RichTextDocbookSchemaPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $this->registerConfigParser($container);

        if ($container->hasExtension('ibexa_mcp')) {
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
            $loader->load('mcp.yaml');
        }
    }

    public function registerConfigParser(ContainerBuilder $container): void
    {
        $this->getCoreExtension($container)->addConfigParser(new RichText());
    }

    protected function getCoreExtension(ContainerBuilder $container): IbexaCoreExtension
    {
        /** @var \Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension */
        return $container->getExtension('ibexa');
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (!isset($this->extension)) {
            $this->extension = new IbexaFieldTypeRichTextExtension();
        }

        return $this->extension ?: null;
    }
}
