<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Psr\Log\LoggerInterface;

class RichTextStorage extends GatewayBasedStorage
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway
     */
    protected $gateway;

    /**
     * @param \Ibexa\Contracts\Core\FieldType\StorageGateway $gateway
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(StorageGateway $gateway, LoggerInterface $logger = null)
    {
        parent::__construct($gateway);
        $this->logger = $logger;
    }

    /**
     * @see \Ibexa\Contracts\Core\FieldType\FieldStorage
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        // This will select only links with non-empty 'xlink:href' attribute value
        $xpathExpression = "//docbook:link[string( @xlink:href ) and not( starts-with( @xlink:href, 'ezurl://' )" .
            "or starts-with( @xlink:href, 'ezcontent://' )" .
            "or starts-with( @xlink:href, 'ezlocation://' )" .
            "or starts-with( @xlink:href, '#' ) )]";

        $links = $xpath->query($xpathExpression);

        if (empty($links)) {
            return false;
        }

        $urlSet = [];
        $remoteIdSet = [];
        $linksInfo = [];

        /** @var \DOMElement $link */
        foreach ($links as $index => $link) {
            preg_match(
                '~^(ezremote://)?([^#]*)?(#.*|\\s*)?$~',
                $link->getAttribute('xlink:href'),
                $matches
            );
            $linksInfo[$index] = $matches;

            if (empty($matches[1])) {
                $urlSet[$matches[2]] = true;
            } else {
                $remoteIdSet[$matches[2]] = true;
            }
        }

        $urlIdMap = $this->gateway->getUrlIdMap(array_keys($urlSet));
        $contentIds = $this->gateway->getContentIds(array_keys($remoteIdSet));
        $urlLinkSet = [];

        foreach ($links as $index => $link) {
            list(, $scheme, $url, $fragment) = $linksInfo[$index];

            if (empty($scheme)) {
                // Insert the same URL only once
                if (!isset($urlIdMap[$url])) {
                    $urlIdMap[$url] = $this->gateway->insertUrl($url);
                }
                // Link the same URL only once
                if (!isset($urlLinkSet[$url])) {
                    $this->gateway->linkUrl(
                        $urlIdMap[$url],
                        $field->id,
                        $versionInfo->versionNo
                    );
                    $urlLinkSet[$url] = true;
                }
                $href = "ezurl://{$urlIdMap[$url]}{$fragment}";
            } else {
                if (!isset($contentIds[$url])) {
                    throw new NotFoundException('Content', $url);
                }
                $href = "ezcontent://{$contentIds[$url]}{$fragment}";
            }

            $link->setAttribute('xlink:href', $href);
        }

        $this->gateway->unlinkUrl(
            $field->id,
            $versionInfo->versionNo,
            array_values(
                $urlIdMap
            )
        );

        $field->value->data = $document->saveXML();

        return true;
    }

    /**
     * Modifies $field if needed, using external data (like for Urls).
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
        $document = new DOMDocument();
        $document->loadXML($field->value->data);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $xpathExpression = "//docbook:link[starts-with( @xlink:href, 'ezurl://' )]|//docbook:ezlink[starts-with( @xlink:href, 'ezurl://' )]";

        $links = $xpath->query($xpathExpression);

        if (empty($links)) {
            return;
        }

        $urlIdSet = [];
        $urlInfo = [];

        /** @var \DOMElement $link */
        foreach ($links as $index => $link) {
            preg_match(
                '~^ezurl://([^#]*)?(#.*|\\s*)?$~',
                $link->getAttribute('xlink:href'),
                $matches
            );
            $urlInfo[$index] = $matches;

            if (!empty($matches[1])) {
                $urlIdSet[$matches[1]] = true;
            }
        }

        $idUrlMap = $this->gateway->getIdUrlMap(array_keys($urlIdSet));

        foreach ($links as $index => $link) {
            list(, $urlId, $fragment) = $urlInfo[$index];

            if (isset($idUrlMap[$urlId])) {
                $href = $idUrlMap[$urlId] . $fragment;
            } else {
                // URL id is empty or not in the DB
                if (isset($this->logger)) {
                    $this->logger->error("URL with ID {$urlId} not found");
                }
                $href = '#';
            }

            $link->setAttribute('xlink:href', $href);
        }

        $field->value->data = $document->saveXML();
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds)
    {
        foreach ($fieldIds as $fieldId) {
            $this->gateway->unlinkUrl($fieldId, $versionInfo->versionNo);
        }
    }

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData()
    {
        return true;
    }
}
