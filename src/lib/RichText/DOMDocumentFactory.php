<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException;

final class DOMDocumentFactory
{
    private XMLSanitizer $xmlSanitizer;

    public function __construct(XMLSanitizer $xmlSanitizer)
    {
        $this->xmlSanitizer = $xmlSanitizer;
    }

    /**
     * Creates \DOMDocument from given $xmlString.
     *
     * @throws \Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException
     */
    public function loadXMLString(string $xmlString): DOMDocument
    {
        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        // Options:
        // - substitute entities
        // - disable network access
        // - relax parser limits for document size/complexity
        $xmlString = (string)$this->xmlSanitizer->sanitizeXMLString($xmlString);
        $success = $document->loadXML($xmlString, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_NONET | LIBXML_PARSEHUGE);
        if (!$success) {
            throw new InvalidXmlException('$xmlString', libxml_get_errors());
        }

        return $this->xmlSanitizer->convertCDATAToText($document);
    }
}
