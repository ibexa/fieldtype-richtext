<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy;

use Doctrine\DBAL\Connection;

class ContentModelGateway
{
    public const DB_TABLE_CONTENTOBJECT_ATTRIBUTE = 'ezcontentobject_attribute';
    public const FIELD_TYPE_IDENTIFIER = 'ezrichtext';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function countRichtextAttributes(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('count(distinct a.id)')
            ->from(self::DB_TABLE_CONTENTOBJECT_ATTRIBUTE, 'a')
            ->where(
                $query->expr()->eq(
                    'a.data_type_string',
                    ':data_type'
                )
            )
            ->setParameter(':data_type', self::FIELD_TYPE_IDENTIFIER);

        $statement = $query->execute();

        return (int) $statement->fetchOne();
    }

    public function getContentObjectAttributeIds(int $startId, int $limit): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('a.id')
            ->from(self::DB_TABLE_CONTENTOBJECT_ATTRIBUTE, 'a')
            ->where(
                $query->expr()->eq(
                    'a.data_type_string',
                    ':data_type'
                )
            )->andWhere(
                $query->expr()->gt(
                    'a.id',
                    ':start_id'
                )
            )
            ->groupBy('a.id')
            ->orderBy('a.id')
            ->setMaxResults($limit)
            ->setParameter(':data_type', self::FIELD_TYPE_IDENTIFIER)
            ->setParameter(':start_id', $startId);

        $statement = $query->execute();

        return $statement->fetchAllAssociative();
    }

    public function getContentObjectAttributes(int $contentAttributeIdStart, int $contentAttributeIdStop): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('a.id, a.version, a.contentobject_id, a.language_code, a.data_text')
            ->from(self::DB_TABLE_CONTENTOBJECT_ATTRIBUTE, 'a')
            ->where(
                $query->expr()->eq(
                    'a.data_type_string',
                    ':data_type'
                )
            )->andWhere(
                $query->expr()->gt(
                    'a.id',
                    ':content_attribute_id_start'
                )
            )->andWhere(
                $query->expr()->lte(
                    'a.id',
                    ':content_attribute_id_stop'
                )
            )
            ->orderBy('a.id')
            ->setParameter(':data_type', self::FIELD_TYPE_IDENTIFIER)
            ->setParameter(':content_attribute_id_start', $contentAttributeIdStart)
            ->setParameter(':content_attribute_id_stop', $contentAttributeIdStop);

        $statement = $query->execute();

        return $statement->fetchAllAssociative();
    }

    public function updateContentObjectAttribute($xml, $contentId, $attributeId, $version, $languageCode)
    {
        $updateQuery = $this->connection->createQueryBuilder();
        $updateQuery->update('ezcontentobject_attribute')
            ->set('data_text', ':newxml')
            ->where(
                $updateQuery->expr()->eq(
                    'data_type_string',
                    ':datatypestring'
                )
            )
            ->andWhere(
                $updateQuery->expr()->eq(
                    'contentobject_id',
                    ':contentId'
                )
            )
            ->andWhere(
                $updateQuery->expr()->eq(
                    'id',
                    ':attributeid'
                )
            )
            ->andWhere(
                $updateQuery->expr()->eq(
                    'version',
                    ':version'
                )
            )
            ->andWhere(
                $updateQuery->expr()->eq(
                    'language_code',
                    ':languagecode'
                )
            )
            ->setParameter(':newxml', $xml)
            ->setParameter(':datatypestring', self::FIELD_TYPE_IDENTIFIER)
            ->setParameter(':contentId', $contentId)
            ->setParameter(':attributeid', $attributeId)
            ->setParameter(':version', $version)
            ->setParameter(':languagecode', $languageCode);
        $updateQuery->execute();
    }
}
