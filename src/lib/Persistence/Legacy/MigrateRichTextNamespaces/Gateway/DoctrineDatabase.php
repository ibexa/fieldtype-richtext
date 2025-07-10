<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\AbstractGateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;

/**
 * @internal
 */
final class DoctrineDatabase extends AbstractGateway
{
    private const string COLUMN_DATA_TEXT = 'data_text';
    private const string FIELD_TYPE_IDENTIFIER = 'ibexa_richtext';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function migrate(array $values): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update(Gateway::CONTENT_FIELD_TABLE)
            ->set(
                self::COLUMN_DATA_TEXT,
                $this->addReplaceStatement($queryBuilder, self::COLUMN_DATA_TEXT, $values)
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'data_type_string',
                    $queryBuilder->createPositionalParameter(
                        self::FIELD_TYPE_IDENTIFIER //TODO migration needed
                    )
                )
            );

        return $queryBuilder->executeStatement();
    }
}
