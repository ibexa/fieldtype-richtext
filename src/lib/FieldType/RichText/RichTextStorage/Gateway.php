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
     * @param array<string> $remoteIds An array of Content remote ids
     *
     * @return array<string, int> An array of Content ids, with remote ids as keys
     */
    abstract public function getContentIds(array $remoteIds): array;

    /**
     * Returns a list of URLs for a list of URL ids.
     *
     * Non-existent ids are ignored.
     *
     * @param int[]|string[] $ids An array of URL ids
     *
     * @return array<int, string> array of URLs, with ids as keys
     */
    public function getIdUrlMap(array $ids): array
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
     * @return array<string, int> An array of URL ids, with URLs as keys
     */
    public function getUrlIdMap(array $urls): array
    {
        return $this->urlGateway->getUrlIdMap($urls);
    }

    /**
     * Inserts a new $url and returns its id.
     */
    public function insertUrl(string $url): int
    {
        return (int)$this->urlGateway->insertUrl($url);
    }

    /**
     * Creates a link to URL with $urlId for field with $fieldId in $versionNo.
     */
    public function linkUrl(int $urlId, int $fieldId, int $versionNo): void
    {
        $this->urlGateway->linkUrl($urlId, $fieldId, $versionNo);
    }

    /**
     * Removes a link to URL for $fieldId in $versionNo and cleans up possibly orphaned URLs.
     *
     * @param int[] $excludeUrlIds
     */
    public function unlinkUrl(int $fieldId, int $versionNo, array $excludeUrlIds = []): void
    {
        $this->urlGateway->unlinkUrl($fieldId, $versionNo, $excludeUrlIds);
    }
}
