<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Repository\SetupFactory;

use Ibexa\Core\Base\Container\Compiler;
use RuntimeException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

trait CoreSetupFactoryTrait
{
    /**
     * Load eZ Platform Kernel settings and setup container.
     *
     * @todo refactor ezplatform-kernel SetupFactory to include that setup w/o relying on config.php
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     *
     * @throws \Exception
     */
    protected function loadCoreSettings(ContainerBuilder $containerBuilder)
    {
        // @todo refactor when refactoring kernel SetupFactory to avoid hardcoding package path
        $kernelRootDir = realpath(__DIR__ . '/../../../../vendor/ibexa/core');
        if (false === $kernelRootDir) {
            throw new RuntimeException('Unable to find the ibexa/core package directory');
        }
        $settingsPath = "{$kernelRootDir}/src/lib/Resources/settings";
        $settingsTestsPath = "{$kernelRootDir}/tests/integration/Core/Resources/settings";

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));

        $loader->load('fieldtype_external_storages.yml');
        $loader->load('fieldtype_services.yml');
        $loader->load('fieldtypes.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('io.yml');
        $loader->load('repository.yml');
        $loader->load('repository/inner.yml');
        $loader->load('repository/event.yml');
        $loader->load('repository/siteaccessaware.yml');
        $loader->load('repository/autowire.yml');
        $loader->load('roles.yml');
        $loader->load('storage_engines/common.yml');
        $loader->load('storage_engines/cache.yml');
        $loader->load('storage_engines/legacy.yml');
        $loader->load('storage_engines/shortcuts.yml');
        $loader->load('search_engines/common.yml');
        $loader->load('settings.yml');
        $loader->load('thumbnails.yml');
        $loader->load('utils.yml');
        $loader->load('policies.yml');

        $loader->load('search_engines/legacy.yml');

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsTestsPath));
        $loader->load('common.yml');
        $loader->load('integration_legacy.yml');

        // Cache settings (takes same env variables as ezplatform does, only supports "singleredis" setup)
        if (getenv('CUSTOM_CACHE_POOL') === 'singleredis') {
            /*
             * Symfony\Component\Cache\Adapter\RedisAdapter
             * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $redisClient
             * public function __construct($redisClient, $namespace = '', $defaultLifetime = 0)
             *
             * $redis = new \Redis();
             * $redis->connect('127.0.0.1', 6379, 2.5);
             */
            $containerBuilder
                ->register('ezpublish.cache_pool.driver.redis', 'Redis')
                ->addMethodCall('connect', [(getenv('CACHE_HOST') ?: '127.0.0.1'), 6379, 2.5]);

            $containerBuilder
                ->register('ibexa.cache_pool.driver', RedisAdapter::class)
                ->setArguments([new Reference('ezpublish.cache_pool.driver.redis'), '', 120]);
        }

        $containerBuilder->setParameter('ibexa.kernel.root_dir', realpath($kernelRootDir));

        $containerBuilder->addCompilerPass(new Compiler\FieldTypeRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Persistence\FieldTypeRegistryPass());

        $containerBuilder->addCompilerPass(new Compiler\Storage\ExternalStorageRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\FieldValueConverterRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\RoleLimitationConverterPass());

        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriteriaConverterPass());
        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass());
        $containerBuilder->addCompilerPass(new Compiler\Search\Legacy\SortClauseConverterPass());

        $containerBuilder->addCompilerPass(new Compiler\Search\FieldRegistryPass());

        $containerBuilder->setParameter(
            'ibexa.persistence.legacy.dsn',
            self::$dsn
        );

        $containerBuilder->setParameter(
            'ibexa.io.dir.root',
            self::$ioRootDir . '/'
        );

        // load overrides just before creating test Container
        $loader->load('override.yml');
    }
}
