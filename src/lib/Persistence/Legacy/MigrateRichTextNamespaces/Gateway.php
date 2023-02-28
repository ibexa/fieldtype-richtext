<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces;

/**
 * @internal
 */
abstract class Gateway
{
    public const CONTENT_ATTRIBUTE_TABLE = 'ezcontentobject_attribute';

    /**
     * @param array<string, string> $values
     */
    abstract public function replaceDataTextAttributeValues(array $values): int;
}
