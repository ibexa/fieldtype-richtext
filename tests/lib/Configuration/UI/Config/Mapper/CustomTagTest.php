<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\UI\Config\Mapper;

use ArrayObject;
use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UI Config Mapper test for RichText Custom Tags configuration.
 *
 * @see \Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag::__construct
 */
class CustomTagTest extends TestCase
{
    /**
     * @covers \Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag::mapConfig
     *
     * @dataProvider providerForTestMapConfig
     *
     * @param array<mixed> $customTagsConfiguration
     * @param array<int, string> $enabledCustomTags
     * @param array<mixed> $expectedConfig
     */
    public function testMapConfig(
        array $customTagsConfiguration,
        array $enabledCustomTags,
        array $expectedConfig
    ): void {
        $mapper = new CustomTag(
            $customTagsConfiguration,
            $this->getTranslatorInterfaceMock(),
            $this->getTranslatorBagInterfaceMock(),
            'custom_tags',
            $this->getPackagesMock(),
            new ArrayObject(
                [
                    new CustomTag\ChoiceAttributeMapper(),
                    new CustomTag\CommonAttributeMapper(),
                ]
            )
        );

        $actualConfig = $mapper->mapConfig($enabledCustomTags);

        self::assertEquals($expectedConfig, $actualConfig);
    }

    /**
     * Data provider for {@see testMapConfig}.
     *
     * @return array<int, array{
     *     array<string, mixed>,
     *     array<int, string>,
     *     array<string, mixed>
     * }>
     */
    public function providerForTestMapConfig(): array
    {
        return [
            [
                [
                    'ezyoutube' => [
                        'template' => '@ibexadesign/fields/ibexa_richtext/custom_tags/ezyoutube.html.twig',
                        'icon' => '/bundles/ibexaadminuiassets/vendors/webalys/streamlineicons/all-icons.svg#video',
                        'is_inline' => false,
                        'attributes' => [
                            'width' => [
                                'type' => 'number',
                                'required' => true,
                                'default_value' => 640,
                            ],
                            'height' => [
                                'type' => 'number',
                                'required' => true,
                                'default_value' => 360,
                            ],
                            'autoplay' => [
                                'type' => 'boolean',
                                'default_value' => false,
                                'required' => false,
                            ],
                        ],
                    ],
                    'eztwitter' => [
                        'template' => '@ibexadesign/fields/ibexa_richtext/custom_tags/eztwitter.html.twig',
                        'icon' => '/bundles/ibexaadminuiassets/vendors/webalys/streamlineicons/all-icons.svg#twitter',
                        'is_inline' => false,
                        'attributes' => [
                            'tweet_url' => [
                                'type' => 'string',
                                'required' => true,
                                'default_value' => null,
                            ],
                            'cards' => [
                                'type' => 'choice',
                                'required' => false,
                                'default_value' => '',
                                'choices' => [
                                    '',
                                    'hidden',
                                ],
                            ],
                        ],
                    ],
                ],
                ['ezyoutube', 'eztwitter'],
                [
                    'ezyoutube' => [
                        'label' => 'ibexa_richtext.custom_tags.ezyoutube.label',
                        'description' => 'ibexa_richtext.custom_tags.ezyoutube.description',
                        'icon' => '/bundles/ibexaadminuiassets/vendors/webalys/streamlineicons/all-icons.svg#video',
                        'isInline' => false,
                        'attributes' => [
                            'width' => [
                                'label' => 'ibexa_richtext.custom_tags.ezyoutube.attributes.width.label',
                                'type' => 'number',
                                'required' => true,
                                'defaultValue' => 640,
                            ],
                            'height' => [
                                'label' => 'ibexa_richtext.custom_tags.ezyoutube.attributes.height.label',
                                'type' => 'number',
                                'required' => true,
                                'defaultValue' => 360,
                            ],
                            'autoplay' => [
                                'label' => 'ibexa_richtext.custom_tags.ezyoutube.attributes.autoplay.label',
                                'type' => 'boolean',
                                'required' => false,
                                'defaultValue' => false,
                            ],
                        ],
                    ],
                    'eztwitter' => [
                        'label' => 'ibexa_richtext.custom_tags.eztwitter.label',
                        'description' => 'ibexa_richtext.custom_tags.eztwitter.description',
                        'icon' => '/bundles/ibexaadminuiassets/vendors/webalys/streamlineicons/all-icons.svg#twitter',
                        'isInline' => false,
                        'attributes' => [
                            'tweet_url' => [
                                'label' => 'ibexa_richtext.custom_tags.eztwitter.attributes.tweet_url.label',
                                'type' => 'string',
                                'required' => true,
                                'defaultValue' => null,
                            ],
                            'cards' => [
                                'label' => 'ibexa_richtext.custom_tags.eztwitter.attributes.cards.label',
                                'type' => 'choice',
                                'required' => false,
                                'defaultValue' => '',
                                'choices' => [
                                    '',
                                    'hidden',
                                ],
                                'choicesLabel' => [
                                    '' => '',
                                    'hidden' => 'hidden',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getTranslatorInterfaceMock(): TranslatorInterface&MockObject
    {
        $translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
        $translatorInterfaceMock
            ->expects(self::any())
            ->method('trans')
            ->withAnyParameters()
            ->willReturnArgument(0);

        return $translatorInterfaceMock;
    }

    private function getTranslatorBagInterfaceMock(): TranslatorBagInterface&MockObject
    {
        $catalogueMock = $this->createMock(MessageCatalogueInterface::class);
        $catalogueMock
            ->expects(self::any())
            ->method('has')
            ->withAnyParameters()
            ->willReturn(false);

        $translatorBagInterfaceMock = $this->createMock(TranslatorBagInterface::class);
        $translatorBagInterfaceMock
            ->expects(self::any())
            ->method('getCatalogue')
            ->willReturn(
                $catalogueMock
            );

        return $translatorBagInterfaceMock;
    }

    private function getPackagesMock(): Packages&MockObject
    {
        $packagesMock = $this->createMock(Packages::class);
        $packagesMock
            ->expects(self::any())
            ->method('getUrl')
            ->withAnyParameters()
            ->willReturnArgument(0);

        return $packagesMock;
    }
}
