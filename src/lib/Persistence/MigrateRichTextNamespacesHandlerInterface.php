<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence;

/**
 * @interal
 */
interface MigrateRichTextNamespacesHandlerInterface
{
    /**
     * @param array<string, string> $values
     */
    public function replaceXMLNamespaces(array $values): int;
}
