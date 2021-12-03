<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTemplateConfigMapper;

abstract class BaseCustomTemplateProviderTestCase extends BaseProviderTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomStyle */
    protected $mapper;

    abstract protected function getExpectedCustomTemplatesConfiguration(): array;

    abstract protected function getCustomTemplateSiteAccessConfigParamName(): string;

    public function setUp(): void
    {
        parent::setUp();

        $this->mapper = $this->createMock(CustomTemplateConfigMapper::class);
    }

    /**
     * @covers \EzSystems\EzPlatformRichText\SPI\Configuration\Provider::getConfiguration
     */
    final public function testGetConfiguration()
    {
        $provider = $this->createProvider();

        $tags = $this->getExpectedCustomTemplatesConfiguration();

        $this->configResolver
            ->expects($this->once())
            ->method('hasParameter')
            ->with($this->getCustomTemplateSiteAccessConfigParamName())
            ->willReturn(true);

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->getCustomTemplateSiteAccessConfigParamName())
            ->willReturn($tags);

        $this->mapper
            ->expects($this->once())
            ->method('mapConfig')
            ->with($tags)
            ->willReturnArgument(0);

        self::assertEquals(
            $tags,
            $provider->getConfiguration()
        );
    }
}

class_alias(BaseCustomTemplateProviderTestCase::class, 'EzSystems\Tests\EzPlatformRichText\Configuration\Provider\BaseCustomTemplateProviderTestCase');
