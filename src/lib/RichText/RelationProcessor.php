<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;

final class RelationProcessor
{
    private const array EMBED_TAG_NAMES = [
        'ezembedinline', 'ezembed',
    ];

    private const array LINK_TAG_NAMES = [
        'link', 'ezlink',
    ];

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON type relations,
     * there is a service API for handling those.
     *
     *
     * @return array<int, array{locationIds: array<int, int>, contentIds: array<int, int>}> Hash with relation type as key and array of destination content ids as value.
     *
     * Example:
     * <code>
     *  array(
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK => array(
     *          "contentIds" => array( 12, 13, 14 ),
     *          "locationIds" => array( 24 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED => array(
     *          "contentIds" => array( 12 ),
     *          "locationIds" => array( 24, 45 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD => array( 12 )
     *  )
     * </code>
     */
    public function getRelations(DOMDocument $doc): array
    {
        return [
            RelationType::LINK->value => $this->getRelatedObjectIds($doc, self::LINK_TAG_NAMES),
            RelationType::EMBED->value => $this->getRelatedObjectIds($doc, self::EMBED_TAG_NAMES),
        ];
    }

    /**
     * @param list<string> $tagNames
     *
     * @return array{locationIds: array<int, int>, contentIds: array<int, int>}
     */
    private function getRelatedObjectIds(DOMDocument $xml, array $tagNames): array
    {
        $contentIds = [];
        $locationIds = [];

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        foreach ($tagNames as $tagName) {
            $xpathExpression = "//docbook:{$tagName}[starts-with( @xlink:href, 'ezcontent://' ) or starts-with( @xlink:href, 'ezlocation://' )]";
            /** @var \DOMElement $element */
            foreach ($xpath->query($xpathExpression) as $element) {
                preg_match('~^(.+)://([^#]*)?(#.*|\\s*)?$~', $element->getAttribute('xlink:href'), $matches);
                list(, $scheme, $id) = $matches;

                if (empty($id)) {
                    continue;
                }

                if ($scheme === 'ezcontent') {
                    $contentIds[] = (int)$id;
                } elseif ($scheme === 'ezlocation') {
                    $locationIds[] = (int)$id;
                }
            }
        }

        return [
            'locationIds' => array_unique($locationIds),
            'contentIds' => array_unique($contentIds),
        ];
    }
}
