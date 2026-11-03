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

    private const SCHEME_PATTERN = '~^[a-z][a-z0-9+.\-]*://~i';

    private const USERINFO_PATTERN = '~^[^/?#@]*@~';

    /**
     * Host and port, where "[" and "]" are allowed to delimit an IP literal.
     * Left untouched, as invalid characters cannot be fixed by encoding them in a host.
     */
    private const HOST_PATTERN = '~^[^/?#]*~';

    private const INVALID_CHARACTERS_PATTERN = '~[\[\]]|%(?![0-9a-f]{2})~i';

    private const INVALID_FRAGMENT_CHARACTERS_PATTERN = '~[\[\]#]|%(?![0-9a-f]{2})~i';

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
        $uri = $href;
        $fragment = null;
        $fragmentPosition = strpos($href, '#');
        if ($fragmentPosition !== false) {
            $uri = substr($href, 0, $fragmentPosition);
            $fragment = substr($href, $fragmentPosition + 1);
        }

        $prefix = '';
        if (preg_match(self::SCHEME_PATTERN, $uri, $matches) === 1) {
            $prefix = $matches[0];
            $uri = substr($uri, strlen($matches[0]));

            if (preg_match(self::USERINFO_PATTERN, $uri, $matches) === 1) {
                $prefix .= $this->encode(self::INVALID_CHARACTERS_PATTERN, $matches[0]);
                $uri = substr($uri, strlen($matches[0]));
            }

            if (preg_match(self::HOST_PATTERN, $uri, $matches) === 1) {
                $prefix .= $matches[0];
                $uri = substr($uri, strlen($matches[0]));
            }
        }

        $encodedHref = $prefix . $this->encode(self::INVALID_CHARACTERS_PATTERN, $uri);
        if ($fragment !== null) {
            $encodedHref .= '#' . $this->encode(self::INVALID_FRAGMENT_CHARACTERS_PATTERN, $fragment);
        }

        return $encodedHref;
    }

    private function encode(
        string $pattern,
        string $value
    ): string {
        return (string)preg_replace_callback(
            $pattern,
            static fn (array $matches): string => rawurlencode($matches[0]),
            $value
        );
    }
}
