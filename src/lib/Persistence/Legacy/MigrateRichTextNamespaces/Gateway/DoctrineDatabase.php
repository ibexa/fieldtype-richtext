<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Doctrine\DBAL\Connection;
use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

/**
 * @internal
 */
final class DoctrineDatabase extends Gateway
{
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
        $qb->update(self::CONTENT_ATTRIBUTE_TABLE);

        $columnName = 'data_text';

        foreach ($values as $oldValue => $newValue) {
            $oldParam = $qb->createPositionalParameter($oldValue);
            $newParam = $qb->createPositionalParameter($newValue);

            $qb->set(
                $columnName,
                "REPLACE($columnName, $oldParam, $newParam)"
            );
        }

        $qb
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
}
