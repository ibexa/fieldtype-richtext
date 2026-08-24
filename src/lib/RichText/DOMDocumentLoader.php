<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;

/**
 * @internal
 *
 * Loads already stored, trusted XML documents suppressing libxml warnings (e.g. invalid NCName
 * in xml:id of a document stored before input sanitization was introduced), which Symfony's
 * error handler would otherwise turn into exceptions during rendering.
 *
 * Unlike {@see \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory} it performs no sanitization
 * and never throws — when the XML cannot be parsed at all, an empty \DOMDocument is returned.
 */
final class DOMDocumentLoader
{
    public static function loadXMLSuppressingWarnings(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $document->loadXML($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        return $document;
    }
}
