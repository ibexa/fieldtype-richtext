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
    public const EMPTY_VALUE = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0"/>
EOT;

    /**
     * XML content as DOMDocument.
     *
     * @var \DOMDocument
     */
    public $xml;

    /**
     * Initializes a new RichText Value object with $xmlDoc in.
     *
     * @param \DOMDocument|string $xml
     */
    public function __construct($xml = null)
    {
        $this->xml = $this->normalizeXML($xml);
    }

    /**
     * @see \Ibexa\Core\FieldType\Value
     */
    public function __toString()
    {
        return isset($this->xml) ? (string)$this->xml->saveXML() : self::EMPTY_VALUE;
    }

    /**
     * @param \DOMDocument|string $xml
     */
    private function normalizeXML($xml): DOMDocument
    {
        if ($xml instanceof DOMDocument) {
            return $xml;
        }

        if ($xml === null || $xml === self::EMPTY_VALUE) {
            return $this->getEmptyXML();
        }

        $value = new DOMDocument();
        $value->loadXML($xml);

        return $value;
    }

    /**
     * Returns DOM document with empty value.
     */
    private function getEmptyXML(): DOMDocument
    {
        static $value = null;
        if ($value === null) {
            $value = new DOMDocument();
            $value->loadXML(self::EMPTY_VALUE);
        }

        return clone $value;
    }
}

class_alias(Value::class, 'EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value');
