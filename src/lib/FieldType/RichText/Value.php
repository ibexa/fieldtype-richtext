<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for RichText field type.
 */
class Value extends BaseValue
{
    public const string EMPTY_VALUE = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0"/>
EOT;

    public DOMDocument $xml;

    /**
     * Initializes a new RichText Value object with $xmlDoc in.
     */
    public function __construct(DOMDocument|string|null $xml = null)
    {
        if ($xml instanceof DOMDocument) {
            $this->xml = $xml;
        } else {
            $this->xml = new DOMDocument();
            $this->xml->loadXML($xml === null ? self::EMPTY_VALUE : $xml);
        }
    }

    /**
     * @see \Ibexa\Core\FieldType\Value
     */
    public function __toString(): string
    {
        $xml = $this->xml->saveXML();

        return $xml === false ? self::EMPTY_VALUE : $xml;
    }
}
