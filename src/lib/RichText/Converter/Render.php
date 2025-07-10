<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMElement;
use DOMNode;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\RendererInterface;

/**
 * Base class for Render converters.
 */
abstract class Render
{
    protected RendererInterface $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Extracts configuration hash from an embed element.
     *
     * @return array<string, mixed>|string|null
     */
    protected function extractConfiguration(DOMElement $embed): array|string|null
    {
        $hash = [];

        $xpath = new DOMXPath($embed->ownerDocument);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $configElements = $xpath->query('./docbook:ezconfig', $embed);

        if ($configElements->length) {
            $hash = $this->extractHash($configElements->item(0));
        }

        return $hash;
    }

    /**
     * Recursively extracts data from XML hash structure.
     *
     * @return array<string, mixed>|string|null
     */
    protected function extractHash(DOMNode $configHash): array|string|null
    {
        $hash = [];

        if ($configHash->childNodes->count() === 0) {
            return null;
        }

        foreach ($configHash->childNodes as $node) {
            /** @var \DOMText|\DOMElement $node */
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $hash[$node->getAttribute('key')] = $this->extractHash($node);
            } elseif ($node->nodeType === XML_TEXT_NODE && !$node->isWhitespaceInElementContent()) {
                return $node->wholeText;
            }
        }

        return $hash;
    }
}
