<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType;

use Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText as RichTextConfigParser;
use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\IbexaFieldTypeRichTextExtension;
use Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle;
use Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Parser\AbstractParserTestCase;
use Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection\ContainerParameterLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;

class RichTextTest extends AbstractParserTestCase
{
    /**
     * Multidimensional array of configuration of multiple extensions ([extension => config]).
     *
     * @var array
     */
    private $extensionsConfig;

    /**
     * Get test configuration for multiple extensions.
     *
     * @return array
     */
    private function getExtensionsConfig(): array
    {
        if (null === $this->extensionsConfig) {
            $extensionNames = [IbexaFieldTypeRichTextExtension::EXTENSION_NAME, 'ibexa'];
            foreach ($extensionNames as $extensionName) {
                $this->extensionsConfig[$extensionName] = Yaml::parseFile(
                    dirname(__DIR__, 3) . "/Fixtures/{$extensionName}.yaml"
                );
            }
        }

        return $this->extensionsConfig;
    }

    /**
     * Load Configuration for multiple defined extensions.
     *
     * @param array $configurationValues
     *
     * @throws \Exception
     */
    protected function configureAndLoad(array $configurationValues = [])
    {
        $bundle = new IbexaFieldTypeRichTextBundle();
        $bundle->build($this->container);

        (new ContainerParameterLoader())->loadMockedRequiredContainerParameters($this->container);

        $configs = array_merge_recursive($this->getMinimalConfiguration(), $configurationValues);

        foreach ($this->container->getExtensions() as $extension) {
            if ($extension instanceof PrependExtensionInterface) {
                $extension->prepend($this->container);
            }

            $extensionAlias = $extension->getAlias();
            // when loading extension, pass only relevant configuration
            $extensionConfig = isset($configs[$extensionAlias]) ? $configs[$extensionAlias] : [];

            $extension->load([$extensionConfig], $this->container);
        }
    }

    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return \Symfony\Component\DependencyInjection\Extension\ExtensionInterface[]
     */
    protected function getContainerExtensions(): array
    {
        return [
            new IbexaCoreExtension([new RichTextConfigParser()]),
            new IbexaFieldTypeRichTextExtension(),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return $this->getExtensionsConfig();
    }

    public function testDefaultContentSettings()
    {
        $this->configureAndLoad();

        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.tags.default',
            [
                'template' => '@IbexaFieldTypeRichText/RichText/tag/default.html.twig',
            ],
            'ibexa_demo_site'
        );
        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.output_custom_xsl',
            [
                0 => [
                    'path' => '%kernel.project_dir%/vendor/ibexa/fieldtype-richtext/src/bundle/Resources/richtext/stylesheets/docbook/xhtml5/output/core.xsl',
                    'priority' => 0,
                ],
            ],
            'ibexa_demo_site'
        );
    }

    /**
     * Test Rich Text Custom Tags invalid settings, like enabling undefined Custom Tag.
     */
    public function testRichTextCustomTagsInvalidSettings()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown RichText Custom Tag \'foo\'');

        $this->configureAndLoad(
            [
                'ibexa' => [
                    'system' => [
                        'ibexa_demo_site' => [
                            'fieldtypes' => [
                                'ezrichtext' => [
                                    'custom_tags' => ['foo'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertConfigResolverParameterValue(
            'fieldtypes.ezrichtext.custom_tags',
            ['foo'],
            'ibexa_demo_site'
        );
    }

    /**
     * Test expected semantic config validation for online editor settings.
     *
     * @dataProvider getOnlineEditorInvalidSettings
     *
     * @param array $config
     * @param string $expectedExceptionMessage
     *
     * @throws \Exception
     */
    public function testOnlineEditorInvalidSettingsThrowException(
        array $config,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->configureAndLoad(
            [
                'ibexa' => [
                    'system' => [
                        'ibexa_demo_site' => [
                            'fieldtypes' => [
                                'ezrichtext' => $config,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Data provider for testOnlineEditorInvalidSettings.
     *
     * @return array
     *
     * @see testOnlineEditorInvalidSettingsThrowException
     */
    public function getOnlineEditorInvalidSettings(): array
    {
        return [
            [
                [
                    'classes' => [
                        'paragraph' => [
                            'choices' => ['class1', 'class2'],
                            'default_value' => 'class3',
                        ],
                    ],
                ],
                'The default value must be one of the possible choices',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'select-single-attr' => [
                                'type' => 'choice',
                                'choices' => ['class1', 'class2'],
                                'default_value' => 'class3',
                            ],
                        ],
                    ],
                ],
                'The default value must be one of the possible choices',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'boolean-attr' => [
                                'type' => 'boolean',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'Boolean type does not support the "required" setting',
            ],
            [
                [
                    'attributes' => [
                        'paragraph' => [
                            'boolean-attr' => [
                                'type' => 'number',
                                'choices' => ['foo'],
                            ],
                        ],
                    ],
                ],
                'Number type does not support the "choices" setting',
            ],
        ];
    }

    /**
     * @dataProvider richTextSettingsProvider
     *
     * @param array $config
     * @param array $expected
     *
     * @throws \Exception
     */
    public function testRichTextSettings(array $config, array $expected)
    {
        $this->configureAndLoad(
            [
                'ibexa' => [
                    'system' => [
                        'ibexa_demo_site' => $config,
                    ],
                ],
            ]
        );

        foreach ($expected as $key => $val) {
            $this->assertConfigResolverParameterValue($key, $val, 'ibexa_demo_site');
        }
    }

    public function richTextSettingsProvider()
    {
        return [
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'custom_tags' => ['video', 'equation'],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.custom_tags' => ['video', 'equation'],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'embed' => [
                                'content' => [
                                    'template' => 'MyBundle:FieldType/RichText/embed:content.html.twig',
                                    'config' => [
                                        'have' => [
                                            'spacesuit' => [
                                                'travel' => true,
                                            ],
                                        ],
                                    ],
                                ],
                                'location_inline_denied' => [
                                    'template' => 'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig',
                                    'config' => [
                                        'have' => [
                                            'location' => [
                                                'index' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.embed.content' => [
                        'template' => 'MyBundle:FieldType/RichText/embed:content.html.twig',
                        'config' => [
                            'have' => [
                                'spacesuit' => [
                                    'travel' => true,
                                ],
                            ],
                        ],
                    ],
                    'fieldtypes.ezrichtext.embed.location_inline_denied' => [
                        'template' => 'MyBundle:FieldType/RichText/embed:location_inline_denied.html.twig',
                        'config' => [
                            'have' => [
                                'location' => [
                                    'index' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'classes' => [
                                'paragraph' => [
                                    'choices' => ['class1', 'class2'],
                                    'required' => true,
                                    'default_value' => 'class1',
                                    'multiple' => true,
                                ],
                                'headline' => [
                                    'choices' => ['class3', 'class4'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.classes' => [
                        'paragraph' => [
                            'choices' => ['class1', 'class2'],
                            'required' => true,
                            'default_value' => 'class1',
                            'multiple' => true,
                        ],
                        'headline' => [
                            'choices' => ['class3', 'class4'],
                            'required' => false,
                            'multiple' => true,
                        ],
                    ],
                ],
            ],
            [
                [
                    'fieldtypes' => [
                        'ezrichtext' => [
                            'attributes' => [
                                'paragraph' => [
                                    'select-single-attr' => [
                                        'choices' => ['class1', 'class2'],
                                        'type' => 'choice',
                                        'required' => true,
                                        'default_value' => 'class1',
                                    ],
                                ],
                                'headline' => [
                                    'text-attr' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'fieldtypes.ezrichtext.attributes' => [
                        'paragraph' => [
                            'select-single-attr' => [
                                'choices' => ['class1', 'class2'],
                                'type' => 'choice',
                                'required' => true,
                                'default_value' => 'class1',
                                'multiple' => false,
                            ],
                        ],
                        'headline' => [
                            'text-attr' => [
                                'type' => 'string',
                                'required' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
