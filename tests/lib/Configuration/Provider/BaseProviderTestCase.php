<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use PHPUnit\Framework\TestCase;

abstract class BaseProviderTestCase extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    protected $configResolver;

    public function setUp(): void
    {
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    abstract public function createProvider(): Provider;

    abstract public function getExpectedProviderName(): string;

    /**
     * @covers \Ibexa\Contracts\FieldTypeRichText\Configuration\Provider::getName
     */
    final public function testGetName(): void
    {
        self::assertSame(
            $this->getExpectedProviderName(),
            $this->createProvider()->getName()
        );
    }
}

class_alias(BaseProviderTestCase::class, 'EzSystems\Tests\EzPlatformRichText\Configuration\Provider\BaseProviderTestCase');
