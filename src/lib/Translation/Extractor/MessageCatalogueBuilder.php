<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Translation\Extractor;

use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Model\MessageCatalogue;

/**
 * Utility class for building translation messages catalogue.
 */
final class MessageCatalogueBuilder
{
    private string $domain;

    private MessageCatalogue $catalogue;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
        $this->catalogue = new MessageCatalogue();
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function reset(): void
    {
        $this->catalogue = new MessageCatalogue();
    }

    public function getCatalogue(): MessageCatalogue
    {
        return $this->catalogue;
    }

    public function addMessage(string $id, string $desc): void
    {
        $this->catalogue->add($this->createMessage($id, $desc));
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
