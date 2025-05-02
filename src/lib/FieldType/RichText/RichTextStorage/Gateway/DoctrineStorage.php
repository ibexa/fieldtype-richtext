<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway;

use Doctrine\DBAL\Connection;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway as UrlGateway;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway;

class DoctrineStorage extends Gateway
{
    protected Connection $connection;

    public function __construct(UrlGateway $urlGateway, Connection $connection)
    {
        parent::__construct($urlGateway);
        $this->connection = $connection;
    }

    /**
     * Return a list of Content ids for a list of remote ids.
     *
     * Non-existent ids are ignored.
     *
     * @param string[] $remoteIds An array of Content remote ids
     *
     * @return int[] An array of Content ids, with remote ids as keys
     */
    public function getContentIds(array $remoteIds): array
    {
        $objectRemoteIdMap = [];

        if (!empty($remoteIds)) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->select(
                    $this->connection->quoteIdentifier('id'),
                    $this->connection->quoteIdentifier('remote_id')
                )
                ->from('ezcontentobject')
                ->where($query->expr()->in('remote_id', ':remoteIds'))
                ->setParameter('remoteIds', $remoteIds, Connection::PARAM_STR_ARRAY)
            ;

            $statement = $query->executeQuery();
            foreach ($statement->fetchAllAssociative() as $row) {
                $objectRemoteIdMap[$row['remote_id']] = $row['id'];
            }
        }

        return $objectRemoteIdMap;
    }
}
