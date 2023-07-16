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
class Figure implements Converter
{
    /**
     * CDATA's content cannot contain the sequence ']]>' as that will terminate the CDATA section.
     * So, if the end sequence ']]>' appears in the string, we split the text into multiple CDATA sections.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        $xpath = new DOMXPath($document);
        $xpathExpression = '//ns:figure [descendant::ns:table]';
        $ns = $document->documentElement ? $document->documentElement->namespaceURI ?: '' : '';
        $xpath->registerNamespace('ns', $ns);
        $elements = $xpath->query($xpathExpression) ?: [];

        // elements are list of <figure> elements
        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $attributes = $element->attributes;

                // Each <figure> element should only contain one table
                if ($element->childNodes[0] instanceof \DomElement) {
                    $tableElement = $element->childNodes[0];

                    /** @var \DOMAttr $attribute */
                    foreach ($attributes as $attribute) {
                        if ($attribute->name === 'class') {
                            $tableElement->setAttribute('class', $attribute->value);
                        }

                        if (strpos($attribute->name, 'data-ezattribute-') === 0) {
                            $tableElement->setAttribute($attribute->name, $attribute->value);
                        }
                    }
                }
            }
        }

        return $document;
    }
}
