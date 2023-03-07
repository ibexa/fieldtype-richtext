<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces;

/**
 * @internal used only for RichText namespaces migration purposes
 */
interface GatewayInterface
{
    /**
     * @param array<string, string> $values
     */
    public function migrate(array $values): int;
}
