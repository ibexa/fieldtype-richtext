<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

/**
 * Class ProgramListing.
 *
 * Processes <code>programlisting</code> DocBook tag.
 */
class ProgramListing implements Converter
{
    /**
     * CDATA's content cannot contain the sequence ']]>' as that will terminate the CDATA section.
     * So, if the end sequence ']]>' appears in the string, we split the text into multiple CDATA sections.
     */
    public function convert(DOMDocument $document): DOMDocument
    {
        $xpath = new DOMXPath($document);
        $xpathExpression = '//ns:pre';
        $ns = $document->documentElement->namespaceURI;
        $xpath->registerNamespace('ns', $ns);
        $elements = $xpath->query($xpathExpression);

        foreach ($elements as $element) {
            $element->textContent = str_replace(']]>', ']]]]><![CDATA[>', $element->textContent);
        }

        return $document;
    }
}
