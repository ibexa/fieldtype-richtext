<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\Contracts\FieldTypeRichText\Configuration\ProviderConfiguratorInterface;
use Ibexa\FieldTypeRichText\Configuration\Provider\ConfigurableProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurableProviderTest extends TestCase
{
    public function testSharesNameWithDecoratedProvider(): void
    {
        $inner = $this->createMock(Provider::class);
        $inner
            ->expects(self::once())
            ->method('getName')
            ->willReturn('__inner_name__');

        $provider = new ConfigurableProvider(
            $inner,
            [],
        );

        self::assertSame('__inner_name__', $provider->getName());
    }

    public function testConfigurationRemainsSameWithoutCustomConfigurators(): void
    {
        $inner = $this->createMock(Provider::class);
        $inner
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                'foo' => 'bar',
            ]);

        $provider = new ConfigurableProvider(
            $inner,
            [],
        );

        self::assertSame(['foo' => 'bar'], $provider->getConfiguration());
    }

    public function testConfigurationReplacedWithCustomConfigurators(): void
    {
        $inner = $this->createMock(Provider::class);
        $inner
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                'foo' => 'bar',
            ]);

        $configurator = $this->createMock(ProviderConfiguratorInterface::class);
        $configurator
            ->expects(self::once())
            ->method('getConfiguration')
            ->with(self::identicalTo(['foo' => 'bar']))
            ->willReturn(['bar' => 'foo']);

        $provider = new ConfigurableProvider(
            $inner,
            [$configurator],
        );

        self::assertSame(['bar' => 'foo'], $provider->getConfiguration());
    }
}
