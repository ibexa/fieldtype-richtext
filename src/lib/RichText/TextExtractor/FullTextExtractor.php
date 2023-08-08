<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\TextExtractor;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;

/**
 * Extracts text content of the given $document.
 *
 * @internal Only for use by RichText FieldType itself.
 */
final class FullTextExtractor implements TextExtractorInterface
{
    public function extractText(DOMDocument $document): string
    {
        return $this->extractTextFromNode($document->documentElement);
    }

    private function extractTextFromNode(DOMNode $node): string
    {
        $text = '';

        if ($node->childNodes !== null && $node->childNodes->count() > 0) {
            foreach ($node->childNodes as $child) {
                $text .= $this->extractTextFromNode($child);
            }
        } elseif (!empty($node->nodeValue)) {
            $text .= $node->nodeValue . ' ';
        }

        return $text;
    }
}
