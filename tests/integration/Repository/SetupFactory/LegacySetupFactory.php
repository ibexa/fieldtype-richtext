<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Repository\SetupFactory;

use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy as CoreLegacySetupFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Used to setup the infrastructure for Repository Public API integration tests,
 * based on Repository with Legacy Storage Engine implementation.
 */
class LegacySetupFactory extends CoreLegacySetupFactory
{
    use RichTextSetupFactoryTrait;

    protected function externalBuildContainer(ContainerBuilder $containerBuilder): void
    {
        parent::externalBuildContainer($containerBuilder);

        $this->loadRichTextSettings($containerBuilder);
    }
}
