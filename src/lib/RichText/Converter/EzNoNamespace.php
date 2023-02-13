<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Bundle\FieldTypeRichText\Command\MigrateNamespacesCommand;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

class EzNoNamespace implements Converter
{
    public function convert(DOMDocument $xmlDoc)
    {
        $xml = $xmlDoc->saveXML();
        $xml = MigrateNamespacesCommand::migrateNamespaces($xml);
        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }
}
