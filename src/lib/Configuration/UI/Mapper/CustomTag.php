<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper;

use Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag\AttributeMapper;
use JMS\TranslationBundle\Annotation\Ignore;
use RuntimeException;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RichText Custom Tag configuration mapper.
 *
 * @internal For internal use by RichText package
 *
 * @phpstan-type TConfig array{
 *     label: string,
 *     description: string,
 *     is_inline: bool,
 *     icon?: string,
 *     attributes: array<string, TConfigAttribute>
 * }
 * @phpstan-type TConfigAttribute array{
 *     type: string,
 *     required: bool,
 *     default_value: mixed,
 *     choices?: array<string>,
 * }
 *
 * @phpstan-import-type TConfigOutput from \Ibexa\FieldTypeRichText\Configuration\Provider\CustomTag
 * @phpstan-import-type TConfigAttributeOutput from \Ibexa\FieldTypeRichText\Configuration\Provider\CustomTag
 */
final class CustomTag implements CustomTemplateConfigMapper
{
    /** @phpstan-var array<TConfig> */
    private array $customTagsConfiguration;

    private TranslatorInterface $translator;

    private TranslatorBagInterface $translatorBag;

    private Packages $packages;

    /** @var iterable<\Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag\AttributeMapper> */
    private iterable $customTagAttributeMappers;

    /** @var array<\Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag\AttributeMapper> */
    private array $supportedTagAttributeMappersCache;

    private string $translationDomain;

    /**
     * CustomTag configuration mapper constructor.
     *
     * Note: type-hinting Translator to have an instance which implements
     * both TranslatorInterface and TranslatorBagInterface.
     *
     * @phpstan-param array<TConfig> $customTagsConfiguration
     *
     * @param iterable<\Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag\AttributeMapper> $customTagAttributeMappers
     */
    public function __construct(
        array $customTagsConfiguration,
        TranslatorInterface $translator,
        TranslatorBagInterface $translatorBag,
        string $translationDomain,
        Packages $packages,
        iterable $customTagAttributeMappers
    ) {
        $this->customTagsConfiguration = $customTagsConfiguration;
        $this->translator = $translator;
        $this->translatorBag = $translatorBag;
        $this->translationDomain = $translationDomain;
        $this->packages = $packages;
        $this->customTagAttributeMappers = $customTagAttributeMappers;
        $this->supportedTagAttributeMappersCache = [];
    }

    /**
     * Map Configuration for the given list of enabled Custom Tags.
     *
     * @phpstan-param array<string> $enabledCustomTags
     *
     * @phpstan-return array<TConfigOutput> Mapped configuration
     */
    public function mapConfig(array $enabledCustomTags): array
    {
        $config = [];
        foreach ($enabledCustomTags as $tagName) {
            if (!isset($this->customTagsConfiguration[$tagName])) {
                throw new RuntimeException(
                    "Could not find RichText Custom Tag configuration for {$tagName}."
                );
            }

            $customTagConfiguration = $this->customTagsConfiguration[$tagName];

            $config[$tagName] = [
                'label' => "ezrichtext.custom_tags.{$tagName}.label",
                'description' => "ezrichtext.custom_tags.{$tagName}.description",
                'isInline' => $customTagConfiguration['is_inline'],
            ];

            if (!empty($customTagConfiguration['icon'])) {
                $config[$tagName]['icon'] = $this->packages->getUrl(
                    $customTagConfiguration['icon']
                );
            }

            foreach ($customTagConfiguration['attributes'] as $attributeName => $properties) {
                $typeMapper = $this->getAttributeTypeMapper(
                    $tagName,
                    $attributeName,
                    $properties['type']
                );
                $config[$tagName]['attributes'][$attributeName] = $typeMapper->mapConfig(
                    $tagName,
                    $attributeName,
                    $properties
                );
            }
        }

        return $this->translateLabels($config);
    }

    /**
     * Get first available Custom Tag Attribute Type mapper.
     */
    private function getAttributeTypeMapper(
        string $tagName,
        string $attributeName,
        string $attributeType
    ): AttributeMapper {
        if (isset($this->supportedTagAttributeMappersCache[$attributeType])) {
            return $this->supportedTagAttributeMappersCache[$attributeType];
        }

        foreach ($this->customTagAttributeMappers as $attributeMapper) {
            // get first supporting, order of these mappers is controlled by 'priority' DI tag attribute
            if ($attributeMapper->supports($attributeType)) {
                return $this->supportedTagAttributeMappersCache[$attributeType] = $attributeMapper;
            }
        }

        throw new RuntimeException(
            "RichText Custom Tag configuration: unsupported attribute type '{$attributeType}' of the '{$attributeName}' attribute in '{$tagName}' Custom Tag"
        );
    }

    /**
     * Process Custom Tags config and translate labels for UI.
     *
     * @param array<string, TConfigOutput> $config
     *
     * @return array<string, TConfigOutput> processed Custom Tags config with translated labels
     */
    private function translateLabels(array $config): array
    {
        foreach ($config as $tagName => $tagConfig) {
            $config[$tagName]['label'] = $this->translator->trans(
                /** @Ignore */
                $tagConfig['label'],
                [],
                $this->translationDomain
            );
            $config[$tagName]['description'] = $this->translator->trans(
                /** @Ignore */
                $tagConfig['description'],
                [],
                $this->translationDomain
            );

            if (empty($tagConfig['attributes'])) {
                continue;
            }

            $transCatalogue = $this->translatorBag->getCatalogue();
            foreach ($tagConfig['attributes'] as $attributeName => $attributeConfig) {
                $config[$tagName]['attributes'][$attributeName]['label'] = $this->translator->trans(
                    /** @Ignore */
                    $attributeConfig['label'],
                    [],
                    $this->translationDomain
                );

                if (isset($config[$tagName]['attributes'][$attributeName]['choicesLabel'])) {
                    foreach ($config[$tagName]['attributes'][$attributeName]['choicesLabel'] as $choice => $label) {
                        $translatedLabel = $choice;
                        if ($transCatalogue->has($label, $this->translationDomain)) {
                            $translatedLabel = $this->translator->trans(
                                /** @Ignore */
                                $label,
                                [],
                                $this->translationDomain
                            );
                        }

                        $config[$tagName]['attributes'][$attributeName]['choicesLabel'][$choice] = $translatedLabel;
                    }
                }
            }
        }

        return $config;
    }
}

class_alias(CustomTag::class, 'EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag');
