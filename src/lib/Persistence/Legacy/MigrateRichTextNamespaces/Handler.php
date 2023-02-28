<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces;

use Ibexa\FieldTypeRichText\Persistence\MigrateRichTextNamespacesHandlerInterface;

/**
 * @interal
 */
class Handler implements MigrateRichTextNamespacesHandlerInterface
{
    private Gateway $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function replaceXMLNamespaces(array $values): int
    {
        return $this->gateway->replaceDataTextAttributeValues($values);
    }
}
