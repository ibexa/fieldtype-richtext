<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\TextExtractor;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;

/**
 * Extracts short text content of the given $document.
 *
 * @internal Only for use by RichText FieldType itself.
 */
final class ShortTextExtractor implements TextExtractorInterface
{
    public function extractText(DOMDocument $document): string
    {
        $result = null;
        // try to extract first paragraph/tag
        if ($section = $document->documentElement->firstChild) {
            $textDom = $section->firstChild;

            if (null !== $textDom) {
                $result = $textDom->hasChildNodes()
                    ? $textDom->firstChild->textContent
                    : $textDom->textContent;
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
