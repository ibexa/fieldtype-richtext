<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Translation\Extractor;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration\Parser\FieldType\RichText;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;

final class OnlineEditorCustomAttributesExtractor implements ExtractorInterface
{
    private const string MESSAGE_DOMAIN = 'online_editor';
    private const string ATTRIBUTES_MESSAGE_ID_PREFIX = 'ibexa_richtext.attributes';
    private const string CLASS_LABEL_MESSAGE_ID = 'ibexa_richtext.classes.class.label';

    private ConfigResolverInterface $configResolver;

    /**
     * @var string[]
     */
    private array $siteAccessList;

    /**
     * @param string[] $siteAccessList
     */
    public function __construct(ConfigResolverInterface $configResolver, array $siteAccessList)
    {
        $this->configResolver = $configResolver;
        $this->siteAccessList = $siteAccessList;
    }

    /**
     * Iterate over each scope and extract custom attributes label names.
     */
    public function extract(): MessageCatalogue
    {
        $catalogue = new MessageCatalogue();

        $catalogue->add($this->createMessage(self::CLASS_LABEL_MESSAGE_ID, 'Class'));

        foreach ($this->siteAccessList as $scope) {
            if (!$this->configResolver->hasParameter(RichText::ATTRIBUTES_SA_SETTINGS_ID, null, $scope)) {
                continue;
            }
            $this->extractMessagesForScope($catalogue, $scope);
        }

        return $catalogue;
    }

    private function createMessage(string $id, string $desc): XliffMessage
    {
        $message = new XliffMessage($id, self::MESSAGE_DOMAIN);
        $message->setNew(false);
        $message->setMeaning($desc);
        $message->setDesc($desc);
        $message->setLocaleString($desc);
        $message->addNote('key: ' . $id);

        return $message;
    }

    /**
     * Extract messages from the given scope into the catalogue.
     */
    private function extractMessagesForScope(MessageCatalogue $catalogue, string $scope): void
    {
        $attributes = $this->configResolver->getParameter(
            RichText::ATTRIBUTES_SA_SETTINGS_ID,
            null,
            $scope
        );
        foreach ($attributes as $elementName => $attributesConfig) {
            foreach (array_keys($attributesConfig) as $attributeName) {
                $messageId = sprintf(
                    '%s.%s.%s.label',
                    self::ATTRIBUTES_MESSAGE_ID_PREFIX,
                    $elementName,
                    $attributeName
                );
                // by default let's use attribute name
                $catalogue->add(
                    $this->createMessage($messageId, $attributeName)
                );
            }
        }
    }
}

class_alias(OnlineEditorCustomAttributesExtractor::class, 'EzSystems\EzPlatformRichText\Translation\Extractor\OnlineEditorCustomAttributesExtractor');
