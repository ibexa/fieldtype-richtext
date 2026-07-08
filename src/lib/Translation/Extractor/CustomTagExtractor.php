<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Translation\Extractor;

use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;

/**
 * Generates translation strings for custom tags.
 */
final class CustomTagExtractor implements ExtractorInterface
{
    private const string CUSTOM_TAG_LABEL = 'ibexa_richtext.custom_tags.%s.label';
    private const string CUSTOM_TAG_DESCRIPTION = 'ibexa_richtext.custom_tags.%s.description';
    private const string ATTRIBUTE_LABEL = 'ibexa_richtext.custom_tags.%s.attributes.%s.label';

    /** @var array<string, mixed> */
    private array $customTags;

    private string $domain;

    /** @var string[] */
    private array $allowlist;

    /**
     * @param array<string, mixed> $customTags Custom tags definitions
     * @param string $domain Target translation domain
     * @param string[] $allowlist Whitelist of custom tags to extract
     */
    public function __construct(array $customTags, string $domain, array $allowlist = [])
    {
        $this->customTags = $customTags;
        $this->domain = $domain;
        $this->allowlist = $allowlist;
    }

    public function extract(): MessageCatalogue
    {
        $catalogue = new MessageCatalogueBuilder($this->domain);
        foreach ($this->customTags as $tagName => $config) {
            if (!in_array($tagName, $this->allowlist, true)) {
                continue;
            }

            $this->addCustomTagLabelMessage($catalogue, $tagName, $config);
            $this->addCustomTagDescriptionMessage($catalogue, $tagName, $config);

            /** @var array<string, mixed> $attributes */
            $attributes = $config['attributes'] ?? [];
            foreach ($attributes as $attributeName => $attributeConfig) {
                $this->addAttributeLabelMessage($catalogue, $tagName, $attributeName, $attributeConfig);
            }
        }

        return $catalogue->getCatalogue();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addCustomTagLabelMessage(MessageCatalogueBuilder $catalogue, string $tagName, array $config): void
    {
        $catalogue->addMessage(sprintf(self::CUSTOM_TAG_LABEL, $tagName), $config['label'] ?? $tagName);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addCustomTagDescriptionMessage(MessageCatalogueBuilder $catalogue, string $tagName, array $config): void
    {
        $catalogue->addMessage(sprintf(self::CUSTOM_TAG_DESCRIPTION, $tagName), $config['description'] ?? $tagName);
    }

    /**
     * @param array<string, mixed> $attributeConfig
     */
    private function addAttributeLabelMessage(
        MessageCatalogueBuilder $catalogue,
        string $tagName,
        string $attributeName,
        array $attributeConfig
    ): void {
        $catalogue->addMessage(
            sprintf(self::ATTRIBUTE_LABEL, $tagName, $attributeName),
            $attributeConfig['label'] ?? $attributeName
        );
    }
}
