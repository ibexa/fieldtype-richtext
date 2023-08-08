<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use DOMNode;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;

final class TextExtractor implements TextExtractorInterface
{
    public function extractText(DOMNode $node): string
    {
        $text = '';

        if ($node->childNodes !== null && $node->childNodes->count() > 0) {
            foreach ($node->childNodes as $child) {
                $text .= $this->extractText($child);
            }
        } elseif (!empty($node->nodeValue)) {
            $text .= $node->nodeValue . ' ';
        }

        return $text;
    }

    public function extractShortText(DOMDocument $document): string
    {
        $result = null;
        // try to extract first paragraph/tag
        if ($section = $document->documentElement->firstChild) {
            $textDom = $section->firstChild;

            if ($textDom && $textDom->hasChildNodes()) {
                $result = $textDom->firstChild->textContent;
            } elseif ($textDom) {
                $result = $textDom->textContent;
            }
        }

        if ($result === null) {
            $result = $document->documentElement->textContent;
        }

        // In case of newlines, extract first line. Also limit size to 255 which is maxsize on sql impl.
        $lines = preg_split('/\r\n|\n|\r/', trim($result), -1, PREG_SPLIT_NO_EMPTY);

        return empty($lines) ? '' : trim(mb_substr($lines[0], 0, 255));
    }
}
