<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway as UrlGateway;

/**
 * Abstract gateway class for RichText type.
 * Handles data that is not directly included in raw XML value from the field (i.e. URLs).
 */
abstract class Gateway extends StorageGateway
{
    protected UrlGateway $urlGateway;

    public function __construct(UrlGateway $urlGateway)
    {
        $this->urlGateway = $urlGateway;
    }

    /**
     * Returns a list of Content ids for a list of remote ids.
     *
     * Non-existent ids are ignored.
     *
     * @param array $remoteIds An array of Content remote ids
     *
     * @return array An array of Content ids, with remote ids as keys
     */
    abstract public function getContentIds(array $remoteIds);

    /**
     * Returns a list of URLs for a list of URL ids.
     *
     * Non-existent ids are ignored.
     *
     * @param int[]|string[] $ids An array of URL ids
     *
     * @return array An array of URLs, with ids as keys
     */
    public function getIdUrlMap(array $ids)
    {
        $ids = array_map(intval(...), $ids);

        return $this->urlGateway->getIdUrlMap($ids);
    }

    /**
     * Returns a list of URL ids for a list of URLs.
     *
     * Non-existent URLs are ignored.
     *
     * @param string[] $urls An array of URLs
     *
     * @return array An array of URL ids, with URLs as keys
     */
    public function getUrlIdMap(array $urls)
    {
        return $this->urlGateway->getUrlIdMap($urls);
    }

    /**
     * Inserts a new $url and returns its id.
     *
     * @param string $url The URL to insert in the database
     *
     * @return int|string
     */
    public function insertUrl($url)
    {
        return $this->urlGateway->insertUrl($url);
    }

    /**
     * Creates link to URL with $urlId for field with $fieldId in $versionNo.
     *
     * @param int|string $urlId
     * @param int|string $fieldId
     * @param int $versionNo
     */
    public function linkUrl($urlId, $fieldId, $versionNo): void
    {
        $this->urlGateway->linkUrl((int)$urlId, (int)$fieldId, $versionNo);
    }

    /**
     * Removes link to URL for $fieldId in $versionNo and cleans up possibly orphaned URLs.
     *
     * @param int|string $fieldId
     * @param int $versionNo
     * @param int[] $excludeUrlIds
     */
    public function unlinkUrl($fieldId, $versionNo, array $excludeUrlIds = []): void
    {
        $this->urlGateway->unlinkUrl((int)$fieldId, $versionNo, $excludeUrlIds);
    }
}
