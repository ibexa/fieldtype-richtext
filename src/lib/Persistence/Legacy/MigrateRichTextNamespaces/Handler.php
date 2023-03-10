<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces;

use Ibexa\FieldTypeRichText\Persistence\MigrateRichTextNamespacesHandlerInterface;

/**
 * @internal
 */
final class Handler implements MigrateRichTextNamespacesHandlerInterface
{
    /** @var iterable<\Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface> */
    private iterable $gateways;

    /**
     * @param iterable<\Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface> $gateways
     */
    public function __construct(iterable $gateways)
    {
        $this->gateways = $gateways;
    }

    public function migrateXMLNamespaces(array $values): int
    {
        $counter = 0;

        foreach ($this->gateways as $gateway) {
            $counter += $gateway->migrate($values);
        }

        return $counter;
    }
}
