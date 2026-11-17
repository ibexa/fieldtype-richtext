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
 * Percent-encodes characters in xlink:href values which would make them invalid anyURI values,
 * so that links containing them (e.g. "[" or "]" in a query string) pass DocBook schema validation.
 */
final class LinkHref implements Converter
{
    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /**
     * Optional scheme, followed by an authority: userinfo, which is encoded, and host with port.
     * The host is left untouched, so "[" and "]" delimiting an IP literal are kept,
     * as invalid characters cannot be fixed by encoding them in a host.
     */
    private const AUTHORITY_PATTERN = '~^(?<scheme>(?:[a-z][a-z0-9+.\-]*:)?//)(?<userinfo>[^/?#@]*@)?(?<host>[^/?#]*)~i';

    private const INVALID_CHARACTERS_PATTERN = '~[\[\]#]|%(?![0-9a-f]{2})~i';

    public function convert(DOMDocument $document): DOMDocument
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xlink', self::XLINK_NAMESPACE);

        $elements = $xpath->query('//*[@xlink:href]') ?: [];
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $href = $element->getAttributeNS(self::XLINK_NAMESPACE, 'href');
            $encodedHref = $this->encodeHref($href);
            if ($encodedHref !== $href) {
                $element->setAttributeNS(self::XLINK_NAMESPACE, 'xlink:href', $encodedHref);
            }
        }

        return $document;
    }

    private function encodeHref(string $href): string
    {
        $prefix = '';
        if (preg_match(self::AUTHORITY_PATTERN, $href, $matches) === 1) {
            $prefix = $matches['scheme'] . $this->encode($matches['userinfo']) . $matches['host'];
            $href = substr($href, strlen($matches[0]));
        }

        // The first "#" starts the fragment. Any further ones are inside the fragment and need encoding.
        $parts = explode('#', $href, 2);

        return $prefix . implode('#', array_map([$this, 'encode'], $parts));
    }

    private function encode(string $value): string
    {
        return (string)preg_replace_callback(
            self::INVALID_CHARACTERS_PATTERN,
            static fn (array $matches): string => rawurlencode($matches[0]),
            $value
        );
    }
}
