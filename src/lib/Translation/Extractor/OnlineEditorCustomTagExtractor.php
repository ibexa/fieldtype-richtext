<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Translation\Extractor;

use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;

/**
 * Generates translation strings for custom tags.
 */
final class OnlineEditorCustomTagExtractor implements ExtractorInterface
{
    private const CUSTOM_TAG_LABEL = 'ezrichtext.custom_tags.%s.label';
    private const CUSTOM_TAG_DESCRIPTION = 'ezrichtext.custom_tags.%s.description';
    private const ATTRIBUTE_LABEL_KEY = 'ezrichtext.custom_tags.%s.attributes.%s.label';

    /** @var array<string, mixed> */
    private array $customTags;

    private string $domain;

    /** @var string[] */
    private array $filter;

    /**
     * @param array<string, mixed> $customTags
     * @param string[] $filter
     */
    public function __construct(array $customTags, string $domain, array $filter = [])
    {
        $this->customTags = $customTags;
        $this->domain = $domain;
        $this->filter = $filter;
    }

    public function extract(): MessageCatalogue
    {
        $catalogue = new MessageCatalogue();
        foreach ($this->customTags as $tagName => $config) {
            if (!in_array($tagName, $this->filter, true)) {
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

        return $catalogue;
    }

    private function addCustomTagLabelMessage(MessageCatalogue $catalogue, string $tagName): void
    {
        $message = $this->createMessage(
            sprintf(self::CUSTOM_TAG_LABEL, $tagName),
            $tagName
        );

        $catalogue->add($message);
    }

    private function addCustomTagDescriptionMessage(MessageCatalogue $catalogue, string $tagName): void
    {
        $message = $this->createMessage(
            sprintf(self::CUSTOM_TAG_DESCRIPTION, $tagName),
            $tagName
        );

        $catalogue->add($message);
    }

    private function addAttributeLabelMessage(
        MessageCatalogue $catalogue,
        string $tagName,
        string $attributeName
    ): void {
        $message = $this->createMessage(
            sprintf(self::ATTRIBUTE_LABEL_KEY, $tagName, $attributeName),
            $attributeName
        );

        $catalogue->add($message);
    }

    private function createMessage(string $id, string $desc): XliffMessage
    {
        $message = new XliffMessage($id, $this->domain);
        $message->setNew(false);
        $message->setMeaning($desc);
        $message->setDesc($desc);
        $message->setLocaleString($desc);
        $message->addNote('key: ' . $id);

        return $message;
    }
}
