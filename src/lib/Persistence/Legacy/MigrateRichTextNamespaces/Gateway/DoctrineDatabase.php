<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;

/**
 * @internal
 */
final class DoctrineDatabase implements GatewayInterface
{
    private const CONTENT_ATTRIBUTE_TABLE = 'ezcontentobject_attribute';

    private const COLUMN_DATA_TEXT_NAME = 'data_text';
    private const FIELD_TYPE_IDENTIFIER = 'ezrichtext';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array<string, string> $values
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function replaceDataTextAttributeValues(array $values): int
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->update(self::CONTENT_ATTRIBUTE_TABLE)
            ->set(
                self::COLUMN_DATA_TEXT_NAME,
                $this->addReplaceStatements($qb, self::COLUMN_DATA_TEXT_NAME, $values)
            )
            ->andWhere(
                $qb->expr()->eq(
                    'data_type_string',
                    $qb->createPositionalParameter(
                        self::FIELD_TYPE_IDENTIFIER
                    )
                )
            );

        return (int)$qb->execute();
    }

    /**
     * @param array<string, string> $values
     */
    private function addReplaceStatements(
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

                return $this->addReplaceStatements(
                    $queryBuilder,
                    $replaceStatements,
                    $values
                );
            }
        }

        return $replaceStatements;
    }
}
