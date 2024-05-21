<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\IbexaFieldTypeRichTextExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

class IbexaFieldTypeRichTextExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new IbexaFieldTypeRichTextExtension()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        (new ContainerParameterLoader())->loadMockedRequiredContainerParameters($this->container);
    }

    /**
     * Test RichText Semantic Configuration.
     */
    public function testRichTextConfiguration(): void
    {
        $config = Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/ibexa_fieldtype_richtext.yaml')
        );
        $this->load($config);

        // Validate Custom Tags
        self::assertTrue(
            $this->container->hasParameter(IbexaFieldTypeRichTextExtension::RICHTEXT_CUSTOM_TAGS_PARAMETER)
        );
        $expectedCustomTagsConfig = [
            'video' => [
                'template' => 'MyBundle:FieldType/RichText/tag:video.html.twig',
                'icon' => '/bundles/mybundle/fieldtype/richtext/video.svg#video',
                'attributes' => [
                    'title' => [
                        'type' => 'string',
                        'required' => true,
                        'default_value' => 'abc',
                    ],
                    'width' => [
                        'type' => 'number',
                        'required' => true,
                        'default_value' => 360,
                    ],
                    'autoplay' => [
                        'type' => 'boolean',
                        'required' => false,
                        'default_value' => null,
                    ],
                ],
                'is_inline' => false,
            ],
            'equation' => [
                'template' => 'MyBundle:FieldType/RichText/tag:equation.html.twig',
                'icon' => '/bundles/mybundle/fieldtype/richtext/equation.svg#equation',
                'attributes' => [
                    'name' => [
                        'type' => 'string',
                        'required' => true,
                        'default_value' => 'Equation',
                    ],
                    'processor' => [
                        'type' => 'choice',
                        'required' => true,
                        'default_value' => 'latex',
                        'choices' => ['latex', 'tex'],
                    ],
                ],
                'is_inline' => false,
            ],
        ];

        self::assertSame(
            $expectedCustomTagsConfig,
            $this->container->getParameter(IbexaFieldTypeRichTextExtension::RICHTEXT_CUSTOM_TAGS_PARAMETER)
        );
    }

    /**
     * Test IbexaFieldTypeRichTextExtension prepends expected and needed core settings.
     *
     * @see \Ibexa\Bundle\FieldTypeRichText\DependencyInjection\IbexaFieldTypeRichTextExtension::prepend
     */
    public function testPrepend(): void
    {
        $this->load([]);

        $actualPrependedConfig = $this->container->getExtensionConfig('ibexa');
        // merge multiple configs returned
        $actualPrependedConfig = array_merge(...$actualPrependedConfig);

        $expectedPrependedConfig = [
            'field_templates' => [
                    [
                        'template' => '@IbexaFieldTypeRichText/RichText/content_fields.html.twig',
                        'priority' => 0,
                    ],
                ],
            'fielddefinition_settings_templates' => [
                [
                    'template' => '@IbexaFieldTypeRichText/RichText/fielddefinition_settings.html.twig',
                    'priority' => 0,
                ],
            ],
        ];

        self::assertSame(
            $expectedPrependedConfig,
            $actualPrependedConfig['system']['default']
        );
    }

    /**
     * @dataProvider inlineTagDataProvider
     */
    public function testCheckingInlineCustomTagsInToolbars(string $toolbarName, ?string $expectedException): void
    {
        $config = Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/ibexa_fieldtype_richtext.yaml')
        );
        $config['custom_tags']['video']['is_inline'] = true;
        $this->container->setParameter('ibexa.site_access.list', ['admin_group']);
        $this->container->setParameter('ibexa.site_access.config.admin_group.fieldtypes.ezrichtext.toolbars', [
            $toolbarName => [
                'buttons' => [
                    'video' => [
                        'priority' => 5,
                        'visible' => true,
                    ],
                ],
            ],
        ]);

        if (is_string($expectedException)) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage($expectedException);
        }
        $this->load($config);
    }

    public function inlineTagDataProvider(): iterable
    {
        yield 'Inline tag in normal toolbar' => [
            'foo',
            "Toolbar 'foo' configured in the 'ibexa.site_access.config.admin_group.fieldtypes.ezrichtext.toolbars' scope cannot contain Custom Tag 'video'. Inline Custom Tags are not allowed in Toolbars other than 'text'.",
        ];

        yield 'Inline tag in text toolbar' => [
            'text',
            null,
        ];
    }
}
