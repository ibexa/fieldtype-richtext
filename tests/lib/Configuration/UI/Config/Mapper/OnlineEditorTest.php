<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\UI\Config\Mapper;

use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\OnlineEditor;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class OnlineEditorTest extends TestCase
{
    private OnlineEditor $mapper;

    public function setUp(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock
            ->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);
        $this->mapper = new OnlineEditor($translatorMock, 'online_editor');
    }

    /**
     * Data provider for mapCssClassesConfiguration.
     *
     * @return array<int, array{array<string, mixed>, array<string, mixed>}>
     *
     * @see testMapCssClassesConfiguration
     */
    public function getSemanticConfigurationForMapCssClassesConfiguration(): array
    {
        return [
            [
                // semantic configuration ...
                [
                    'paragraph' => [
                        'choices' => ['class1', 'class2'],
                        'required' => true,
                        'default_value' => 'class1',
                        'multiple' => true,
                    ],
                    'table' => [
                        'choices' => ['class1', 'class2'],
                        'required' => false,
                        'default_value' => 'class2',
                        'multiple' => false,
                    ],
                    'heading' => [
                        'choices' => ['class1', 'class2'],
                        'required' => false,
                        'multiple' => false,
                    ],
                ],
                // ... is mapped to:
                [
                    'paragraph' => [
                        'choices' => ['class1', 'class2'],
                        'required' => true,
                        'defaultValue' => 'class1',
                        'multiple' => true,
                        'label' => 'ibexa_richtext.classes.class.label',
                    ],
                    'table' => [
                        'choices' => ['class1', 'class2'],
                        'required' => false,
                        'defaultValue' => 'class2',
                        'multiple' => false,
                        'label' => 'ibexa_richtext.classes.class.label',
                    ],
                    'heading' => [
                        'choices' => ['class1', 'class2'],
                        'required' => false,
                        'defaultValue' => null,
                        'multiple' => false,
                        'label' => 'ibexa_richtext.classes.class.label',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for mapDataAttributesConfiguration.
     *
     * @return array<int, array{array<string, mixed>, array<string, mixed>}>
     *
     * @see testMapDataAttributesConfiguration
     */
    public function getSemanticConfigurationForMapDataAttributesConfiguration(): array
    {
        return [
            [
                // semantic configuration ...
                [
                    'paragraph' => [
                        'select-multiple-attr' => [
                            'type' => 'choice',
                            'multiple' => true,
                            'required' => true,
                            'choices' => ['value1', 'value2'],
                            'default_value' => 'value2',
                        ],
                        'select-single-attr' => [
                            'type' => 'choice',
                            'multiple' => false,
                            'required' => true,
                            'choices' => ['value1', 'value2'],
                            'default_value' => 'value2',
                        ],
                    ],
                    'heading' => [
                        'boolean-attr' => [
                            'type' => 'boolean',
                            'required' => false,
                            'default_value' => true,
                        ],
                        'text-attr' => [
                            'type' => 'string',
                            'default_value' => 'foo',
                            'required' => true,
                        ],
                    ],
                    'tr' => [
                        'number-attr' => [
                            'type' => 'number',
                            'default_value' => 1,
                            'required' => true,
                        ],
                    ],
                ],
                // ... is mapped to:
                [
                    'paragraph' => [
                        'select-multiple-attr' => [
                            'label' => 'ibexa_richtext.attributes.paragraph.select-multiple-attr.label',
                            'type' => 'choice',
                            'multiple' => true,
                            'required' => true,
                            'choices' => ['value1', 'value2'],
                            'defaultValue' => 'value2',
                        ],
                        'select-single-attr' => [
                            'label' => 'ibexa_richtext.attributes.paragraph.select-single-attr.label',
                            'type' => 'choice',
                            'multiple' => false,
                            'required' => true,
                            'choices' => ['value1', 'value2'],
                            'defaultValue' => 'value2',
                        ],
                    ],
                    'heading' => [
                        'boolean-attr' => [
                            'label' => 'ibexa_richtext.attributes.heading.boolean-attr.label',
                            'type' => 'boolean',
                            'required' => false,
                            'defaultValue' => true,
                        ],
                        'text-attr' => [
                            'label' => 'ibexa_richtext.attributes.heading.text-attr.label',
                            'type' => 'string',
                            'defaultValue' => 'foo',
                            'required' => true,
                        ],
                    ],
                    'tr' => [
                        'number-attr' => [
                            'label' => 'ibexa_richtext.attributes.tr.number-attr.label',
                            'type' => 'number',
                            'defaultValue' => 1,
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getSemanticConfigurationForMapCssClassesConfiguration
     *
     * @param array<string, mixed> $semanticConfiguration
     * @param array<string, mixed> $expectedMappedConfiguration
     */
    public function testMapCssClassesConfiguration(
        array $semanticConfiguration,
        array $expectedMappedConfiguration
    ): void {
        self::assertEquals(
            $expectedMappedConfiguration,
            $this->mapper->mapCssClassesConfiguration($semanticConfiguration)
        );
    }

    /**
     * @dataProvider getSemanticConfigurationForMapDataAttributesConfiguration
     *
     * @param array<string, mixed> $semanticConfiguration
     * @param array<string, mixed> $expectedMappedConfiguration
     */
    public function testMapDataAttributesConfiguration(
        array $semanticConfiguration,
        array $expectedMappedConfiguration
    ): void {
        self::assertEquals(
            $expectedMappedConfiguration,
            $this->mapper->mapDataAttributesConfiguration($semanticConfiguration)
        );
    }
}
