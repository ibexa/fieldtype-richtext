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
 * Generates translation strings for choice custom tag attribute.
 */
final class ChoiceAttributeExtractor implements ExtractorInterface
{
    private const string CHOICE_ATTRIBUTE_TYPE = 'choice';
    private const string CHOICE_LABEL_KEY = 'ibexa_richtext.custom_tags.%s.attributes.%s.choice.%s.label';

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
        foreach ($this->customTags as $tagName => $customTag) {
            if (!in_array($tagName, $this->allowlist, true)) {
                continue;
            }

            $attributes = $customTag['attributes'] ?? [];
            foreach ($attributes as $attributeName => $attribute) {
                $type = $attribute['type'] ?? null;
                if ($type !== self::CHOICE_ATTRIBUTE_TYPE) {
                    continue;
                }

                foreach ($attribute['choices'] as $choice) {
                    if (empty($choice)) {
                        continue;
                    }

                    $this->addChoiceLabelMessage($catalogue, $tagName, $attributeName, $choice);
                }
            }
        }

        return $catalogue->getCatalogue();
    }

    private function addChoiceLabelMessage(
        MessageCatalogueBuilder $catalogue,
        string $tagName,
        string $attributeName,
        string $choice
    ): void {
        $catalogue->addMessage(
            sprintf(self::CHOICE_LABEL_KEY, $tagName, $attributeName, $choice),
            $choice
        );
    }
}
