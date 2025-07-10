<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTemplateConfigMapper;
use PHPUnit\Framework\MockObject\MockObject;

abstract class BaseCustomTemplateProviderTestCase extends BaseProviderTestCase
{
    protected CustomTemplateConfigMapper&MockObject $mapper;

    /**
     * @return array<mixed>
     */
    abstract protected function getExpectedCustomTemplatesConfiguration(): array;

    abstract protected function getCustomTemplateSiteAccessConfigParamName(): string;

    public function setUp(): void
    {
        parent::setUp();

        $this->mapper = $this->createMock(CustomTemplateConfigMapper::class);
    }

    /**
     * @covers \Ibexa\Contracts\FieldTypeRichText\Configuration\Provider::getConfiguration
     */
    final public function testGetConfiguration(): void
    {
        $provider = $this->createProvider();

        $tags = $this->getExpectedCustomTemplatesConfiguration();

        $this->configResolver
            ->expects(self::once())
            ->method('hasParameter')
            ->with($this->getCustomTemplateSiteAccessConfigParamName())
            ->willReturn(true);

        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with($this->getCustomTemplateSiteAccessConfigParamName())
            ->willReturn($tags);

        $this->mapper
            ->expects(self::once())
            ->method('mapConfig')
            ->with($tags)
            ->willReturnArgument(0);

        self::assertEquals(
            $tags,
            $provider->getConfiguration()
        );
    }
}
