<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText;

use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler\RichTextHtml5ConverterPass;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\IbexaFieldTypeRichTextExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
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
        $this->registerConfigParser($container);
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
