<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

class EzNoNamespace implements Converter
{
    public function convert(DOMDocument $xmlDoc)
    {
        $xml = $xmlDoc->saveXML();
        $xml = str_replace('xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml"', 'xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"', $xml);
        $xml = str_replace('xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom"', 'xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"', $xml);
        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }
}
