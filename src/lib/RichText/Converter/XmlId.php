<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

/**
 * @internal
 *
 * Sanitizes xml:id values which are not valid NCNames and rewrites internal links pointing at them.
 */
final class XmlId implements Converter
{
    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /** @see https://www.w3.org/TR/xml/#NT-NameStartChar */
    private const NCNAME_START_CHAR = 'A-Z_a-z'
        . '\\x{C0}-\\x{D6}\\x{D8}-\\x{F6}\\x{F8}-\\x{2FF}'
        . '\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}'
        . '\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}'
        . '\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}';

    private const NCNAME_CHAR = self::NCNAME_START_CHAR
        . '\\-.0-9\\x{B7}\\x{300}-\\x{36F}\\x{203F}-\\x{2040}';

    public function convert(DOMDocument $document): DOMDocument
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xlink', self::XLINK_NAMESPACE);

        $sanitizedFragmentMap = $this->sanitizeIds($xpath);
        if (!empty($sanitizedFragmentMap)) {
            $this->rewriteInternalLinks($xpath, $sanitizedFragmentMap);
        }

        return $document;
    }

    /**
     * @return array<string, string> map of original href fragment => sanitized href fragment
     */
    private function sanitizeIds(DOMXPath $xpath): array
    {
        $elements = $xpath->query('//*[@xml:id]') ?: [];

        $usedIds = [];
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $id = $element->getAttribute('xml:id');
            if ($this->isValidNCName($id)) {
                $usedIds[$id] = true;
            }
        }

        $sanitizedFragmentMap = [];
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $id = $element->getAttribute('xml:id');
            if ($this->isValidNCName($id)) {
                continue;
            }

            if ($id === '') {
                $element->removeAttribute('xml:id');
                continue;
            }

            $sanitizedId = $this->buildUniqueId($this->sanitizeId($id), $usedIds);
            $usedIds[$sanitizedId] = true;
            if (!isset($sanitizedFragmentMap['#' . $id])) {
                $sanitizedFragmentMap['#' . $id] = '#' . $sanitizedId;
            }
            $element->setAttribute('xml:id', $sanitizedId);
        }

        return $sanitizedFragmentMap;
    }

    /**
     * @param array<string, string> $sanitizedFragmentMap
     */
    private function rewriteInternalLinks(DOMXPath $xpath, array $sanitizedFragmentMap): void
    {
        $links = $xpath->query('//*[starts-with(@xlink:href, "#")]') ?: [];
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $href = $link->getAttribute('xlink:href');
            if (isset($sanitizedFragmentMap[$href])) {
                $link->setAttribute('xlink:href', $sanitizedFragmentMap[$href]);
            }
        }
    }

    private function isValidNCName(string $id): bool
    {
        return preg_match('/^[' . self::NCNAME_START_CHAR . '][' . self::NCNAME_CHAR . ']*\z/u', $id) === 1;
    }

    private function sanitizeId(string $id): string
    {
        $sanitizedId = (string)preg_replace('/[^' . self::NCNAME_CHAR . ']/u', '_', $id);
        if (preg_match('/^[' . self::NCNAME_START_CHAR . ']/u', $sanitizedId) !== 1) {
            $sanitizedId = '_' . $sanitizedId;
        }

        return $sanitizedId;
    }

    /**
     * @param array<string, true> $usedIds
     */
    private function buildUniqueId(string $id, array $usedIds): string
    {
        $uniqueId = $id;
        $suffix = 1;
        while (isset($usedIds[$uniqueId])) {
            $uniqueId = $id . '_' . $suffix++;
        }

        return $uniqueId;
    }
}
