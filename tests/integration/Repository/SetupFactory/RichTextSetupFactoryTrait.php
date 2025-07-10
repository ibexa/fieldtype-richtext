<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Repository\SetupFactory;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

trait RichTextSetupFactoryTrait
{
    /**
     * Load RichText package container settings.
     *
     * @throws \Exception
     */
    protected function loadRichTextSettings(ContainerBuilder $containerBuilder)
    {
        $settingsPath = realpath(__DIR__ . '/../../../../src/bundle/Resources/config/settings/');
        if (false === $settingsPath) {
            throw new RuntimeException('Unable to find RichText package settings');
        }

        // load core settings
        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));
        $loader->load('fieldtypes.yaml');
        $loader->load('fieldtype_services.yaml');
        $loader->load('fieldtype_external_storages.yaml');
        $loader->load('indexable_fieldtypes.yaml');
        $loader->load('storage_engines/legacy/external_storage_gateways.yaml');
        $loader->load('storage_engines/legacy/field_value_converters.yaml');

        // load test settings
        $loader = new YamlFileLoader(
            $containerBuilder,
            new FileLocator(__DIR__ . '/../../../../tests/lib/_settings')
        );
        $loader->load('common.yaml');

        $containerBuilder->addCompilerPass(new Compiler\RichTextHtml5ConverterPass());
    }
}
