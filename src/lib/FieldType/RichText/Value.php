<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\DOMDocumentLoaderInterface;
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
     * @param \DOMDocument|string|null $xml passing a string is deprecated since 4.6.33, only \DOMDocument will be accepted in 6.0
     */
    public function __construct($xml = null)
    {
        if ($xml instanceof DOMDocument) {
            $this->xml = $xml;

            return;
        }

        if ($xml !== null) {
            trigger_deprecation(
                'ibexa/fieldtype-richtext',
                '4.6.33',
                'Passing string as $xml argument of %s() is deprecated and will not be supported in 6.0. '
                . 'Pass \DOMDocument instead, e.g. loaded with %s service.',
                __METHOD__,
                DOMDocumentLoaderInterface::class
            );
        }

        $this->xml = new DOMDocument();
        $this->xml->loadXML($xml ?? self::EMPTY_VALUE);
    }

    /**
     * @see \Ibexa\Core\FieldType\Value
     */
    public function __toString()
    {
        return isset($this->xml) ? (string)$this->xml->saveXML() : self::EMPTY_VALUE;
    }
}

class_alias(Value::class, 'EzSystems\EzPlatformRichText\eZ\FieldType\RichText\Value');
