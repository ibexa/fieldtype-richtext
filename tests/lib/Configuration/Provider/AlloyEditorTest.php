<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\FieldTypeRichText\Configuration\Provider\AlloyEditor;
use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper;

class AlloyEditorTest extends BaseProviderTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\FieldTypeRichText\Configuration\UI\Mapper\OnlineEditorConfigMapper */
    private $mapper;

    public function setUp(): void
    {
        parent::setUp();

        $this->mapper = $this->createMock(OnlineEditorConfigMapper::class);
    }

    public function createProvider(ConfigResolverInterface $configResolver = null): Provider
    {
        return new AlloyEditor(
            [
                'extra_plugins' => ['plugin1', 'plugin2'],
            ],
            $configResolver ?? $this->configResolver,
            $this->mapper
        );
    }

    public function getExpectedProviderName(): string
    {
        return 'alloyEditor';
    }

    /**
     * @covers \Ibexa\Tests\FieldTypeRichText\Configuration\Provider\AlloyEditorTest::createProvider
     */
    public function testGetConfiguration(): void
    {
        $provider = $this->createProvider();

        $this->configResolver
            ->expects(self::any())
            ->method('hasParameter')
            ->willReturn(false);

        $this->mapper
            ->expects(self::once())
            ->method('mapCssClassesConfiguration')
            ->with([])
            ->willReturn(['class1', 'class2']);

        $this->mapper
            ->expects(self::once())
            ->method('mapDataAttributesConfiguration')
            ->with([])
            ->willReturn(['attr1', 'attr2']);

        self::assertEquals(
            [
                'extraPlugins' => ['plugin1', 'plugin2'],
                'toolbars' => [],
                'classes' => ['class1', 'class2'],
                'attributes' => ['attr1', 'attr2'],
                'nativeAttributes' => [],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfiguration(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar' => [
                    'buttons' => [
                        'test_button' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'extraPlugins' => ['plugin1', 'plugin2'],
                'toolbars' => [
                    'test_toolbar' => [
                        'buttons' => ['test_button'],
                    ],
                ],
                'classes' => [],
                'attributes' => [],
                'nativeAttributes' => [],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationPriority(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar' => [
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => true,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'extraPlugins' => ['plugin1', 'plugin2'],
                'toolbars' => [
                    'test_toolbar' => [
                        'buttons' => ['test_button_first', 'test_button_middle', 'test_button_last'],
                    ],
                ],
                'classes' => [],
                'attributes' => [],
                'nativeAttributes' => [],
            ],
            $provider->getConfiguration()
        );
    }

    public function testToolbarConfigurationVisibility(): void
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);

        $configResolver
            ->method('hasParameter')
            ->willReturn(true);

        $configResolver
            ->method('getParameter')
            ->willReturnCallback($this->buildTestToolbarReturnCallback([
                'test_toolbar' => [
                    'buttons' => [
                        'test_button_last' => [
                            'visible' => true,
                            'priority' => -100,
                        ],
                        'test_button_first' => [
                            'visible' => true,
                            'priority' => 100,
                        ],
                        'test_button_middle' => [
                            'visible' => false,
                            'priority' => 0,
                        ],
                    ],
                ],
            ]));

        $provider = $this->createProvider($configResolver);

        self::assertEquals(
            [
                'extraPlugins' => ['plugin1', 'plugin2'],
                'toolbars' => [
                    'test_toolbar' => [
                        'buttons' => ['test_button_first', 'test_button_last'],
                    ],
                ],
                'classes' => [],
                'attributes' => [],
                'nativeAttributes' => [],
            ],
            $provider->getConfiguration()
        );
    }

    private function buildTestToolbarReturnCallback(array $buttonsConfig): callable
    {
        return static function (string $paramName) use ($buttonsConfig): ?array {
            $map = [
                RichText::TOOLBARS_SA_SETTINGS_ID => $buttonsConfig,
                RichText::CLASSES_SA_SETTINGS_ID => [],
                RichText::ATTRIBUTES_SA_SETTINGS_ID => [],
            ];

            return $map[$paramName] ?? null;
        };
    }
}
