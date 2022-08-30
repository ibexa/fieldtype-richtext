<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Repository\SetupFactory;

use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy as CoreLegacySetupFactory;
use Ibexa\Core\Base\ServiceContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Used to setup the infrastructure for Repository Public API integration tests,
 * based on Repository with Legacy Storage Engine implementation.
 */
class LegacySetupFactory extends CoreLegacySetupFactory
{
    use CoreSetupFactoryTrait;
    use RichTextSetupFactoryTrait;

    /**
     * Returns the service container used for initialization of the repository.
     *
     * @return \Ibexa\Core\Base\ServiceContainer
     *
     * @throws \Exception
     */
    public function getServiceContainer()
    {
        if (!isset(self::$serviceContainer)) {
            /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder */
            $containerBuilder = new ContainerBuilder();

            $this->externalBuildContainer($containerBuilder);

            self::$serviceContainer = new ServiceContainer(
                $containerBuilder,
                __DIR__,
                'var/cache',
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
     *
     * @throws \Exception
     */
    protected function externalBuildContainer(ContainerBuilder $containerBuilder)
    {
        parent::externalBuildContainer($containerBuilder);

        $this->loadCoreSettings($containerBuilder);
        $this->loadRichTextSettings($containerBuilder);
    }
}
