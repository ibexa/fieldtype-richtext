<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @internal used only for RichText namespaces migration purposes
 */
abstract class AbstractGateway implements GatewayInterface
{
    /**
     * @param array<string, string> $values
     */
    protected function addReplaceStatement(
        QueryBuilder $queryBuilder,
        string $columnName,
        array $values
    ): string {
        $replaceStatements = '';

        foreach ($values as $oldValue => $newValue) {
            $oldParam = $queryBuilder->createPositionalParameter($oldValue);
            $newParam = $queryBuilder->createPositionalParameter($newValue);
            $replaceStatements = "REPLACE($columnName, $oldParam, $newParam)";

            if (false !== next($values)) {
                unset($values[$oldValue]);

                return $this->addReplaceStatement(
                    $queryBuilder,
                    $replaceStatements,
                    $values
                );
            }
        }

        return $replaceStatements;
    }
}
