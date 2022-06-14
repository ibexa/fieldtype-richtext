<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait ContainerParameterLoaderTrait
{
    public function loadMockedRequiredContainerParameters(ContainerInterface $container): void
    {
        $projectDir = dirname(__DIR__, 3);
        $container->setParameter('kernel.project_dir', $projectDir);
        // mock list of available bundles
        $container->setParameter(
            'kernel.bundles',
            ['IbexaCoreBundle' => null, 'IbexaFieldTypeRichTextBundle' => null]
        );
        $container->setParameter(
            'kernel.bundles_metadata',
            [
                'IbexaCoreBundle' => [
                    'path' => $projectDir . '/vendor/ibexa/core/src/bundle/Core',
                    'namespace' > 'Ibexa\Bundle\Core',
                ],
                'IbexaFieldTypeRichTextBundle' => [
                    'path' => $projectDir . '/src/bundle',
                    'namespace' > 'Ibexa\Bundle\FieldTypeRichText',
                ],
            ]
        );
    }
}
