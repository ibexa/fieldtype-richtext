<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\Contracts\Core\Persistence\Content\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Validator for RichText internal format links.
 */
class InternalLinkValidator implements ValidatorInterface
{
    private Handler $contentHandler;

    private LocationHandler $locationHandler;

    public function __construct(ContentHandler $contentHandler, LocationHandler $locationHandler)
    {
        $this->contentHandler = $contentHandler;
        $this->locationHandler = $locationHandler;
    }

    /**
     * Extracts and validate internal links.
     *
     * @return list<string>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validateDocument(DOMDocument $xml): array
    {
        $errors = [];

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        foreach (['link', 'ezlink'] as $tagName) {
            $xpathExpression = $this->getXPathForLinkTag($tagName);
            /** @var \DOMElement $element */
            foreach ($xpath->query($xpathExpression) as $element) {
                $url = $element->getAttribute('xlink:href');
                preg_match('~^(.+)://([^#]*)?(#.*|\\s*)?$~', $url, $matches);
                list(, $scheme, $id) = $matches;

                if (empty($id)) {
                    continue;
                }

                if (!$this->validate($scheme, $id)) {
                    $errors[] = $this->getInvalidLinkError($scheme, $url);
                }
            }
        }

        return $errors;
    }

    /**
     * Validates following link formats: 'ezcontent://<contentId>', 'ezremote://<contentRemoteId>', 'ezlocation://<locationId>'.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if given $scheme is not supported
     */
    public function validate(string $scheme, string $id): bool
    {
        try {
            switch ($scheme) {
                case 'ezcontent':
                    $this->contentHandler->loadContentInfo((int) $id);
                    break;
                case 'ezremote':
                    $this->contentHandler->loadContentInfoByRemoteId($id);
                    break;
                case 'ezlocation':
                    $this->locationHandler->load((int) $id);
                    break;
                default:
                    throw new InvalidArgumentException($scheme, "The provided scheme '{$scheme}' is not supported.");
            }
        } catch (NotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Builds error message for invalid url.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if given $scheme is not supported
     */
    private function getInvalidLinkError(string $scheme, string $url): string
    {
        switch ($scheme) {
            case 'ezcontent':
            case 'ezremote':
                return sprintf('Invalid link "%s": cannot find target content', $url);
            case 'ezlocation':
                return sprintf('Invalid link "%s": cannot find target Location', $url);
            default:
                throw new InvalidArgumentException($scheme, "Given scheme '{$scheme}' is not supported.");
        }
    }

    /**
     * Generates XPath expression for a given link tag.
     */
    private function getXPathForLinkTag(string $tagName): string
    {
        return "//docbook:{$tagName}[starts-with(@xlink:href, 'ezcontent://') or starts-with(@xlink:href, 'ezlocation://') or starts-with(@xlink:href, 'ezremote://')]";
    }
}
