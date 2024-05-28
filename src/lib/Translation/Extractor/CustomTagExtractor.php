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
    private const CUSTOM_TAG_LABEL = 'ezrichtext.custom_tags.%s.label';
    private const CUSTOM_TAG_DESCRIPTION = 'ezrichtext.custom_tags.%s.description';
    private const ATTRIBUTE_LABEL = 'ezrichtext.custom_tags.%s.attributes.%s.label';

    /** @var array<string, mixed> */
    private array $customTags;

    private string $domain;

    /** @var string[] */
    private array $whitelist;

    /**
     * @param array<string, mixed> $customTags Custom tags definitions
     * @param string $domain Target translation domain
     * @param string[] $whitelist Whitelist of custom tags to extract
     */
    public function __construct(array $customTags, string $domain, array $whitelist = [])
    {
        $this->customTags = $customTags;
        $this->domain = $domain;
        $this->whitelist = $whitelist;
    }

    public function extract(): MessageCatalogue
    {
        $catalogue = new MessageCatalogueBuilder($this->domain);
        foreach ($this->customTags as $tagName => $config) {
            if (!in_array($tagName, $this->whitelist, true)) {
                continue;
            }

            $this->addCustomTagLabelMessage($catalogue, $tagName);
            $this->addCustomTagDescriptionMessage($catalogue, $tagName);

            /** @var string[] $attributes */
            $attributes = array_keys($config['attributes'] ?? []);
            foreach ($attributes as $attributeName) {
                $this->addAttributeLabelMessage($catalogue, $tagName, $attributeName);
            }
        }

        return $catalogue->getCatalogue();
    }

    private function addCustomTagLabelMessage(MessageCatalogueBuilder $catalogue, string $tagName): void
    {
        $catalogue->addMessage(sprintf(self::CUSTOM_TAG_LABEL, $tagName), $tagName);
    }

    private function addCustomTagDescriptionMessage(MessageCatalogueBuilder $catalogue, string $tagName): void
    {
        $catalogue->addMessage(sprintf(self::CUSTOM_TAG_DESCRIPTION, $tagName), $tagName);
    }

    private function addAttributeLabelMessage(
        MessageCatalogueBuilder $catalogue,
        string $tagName,
        string $attributeName
    ): void {
        $catalogue->addMessage(
            sprintf(self::ATTRIBUTE_LABEL, $tagName, $attributeName),
            $attributeName
        );
    }
}
