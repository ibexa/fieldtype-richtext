<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException as APIUnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class Link implements Converter
{
    /**
     * @var \Ibexa\Contracts\Core\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \Ibexa\Contracts\Core\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        RouterInterface $router,
        LoggerInterface $logger = null
    ) {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Converts internal links (ezcontent:// and ezlocation://) to URLs.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        $document = clone $document;

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $linkAttributeExpression = "starts-with( @xlink:href, 'ezlocation://' ) or starts-with( @xlink:href, 'ezcontent://' )";
        $xpathExpression = "//docbook:link[{$linkAttributeExpression}]|//docbook:ezlink";

        /** @var \DOMElement $link */
        foreach ($xpath->query($xpathExpression) as $link) {
            // Set resolved href to number character as a default if it can't be resolved
            $hrefResolved = '#';
            $href = $link->getAttribute('xlink:href');
            $location = null;
            preg_match('~^(.+://)?([^#]*)?(#.*|\\s*)?$~', $href, $matches);
            list(, $scheme, $id, $fragment) = $matches;

            if ($scheme === 'ezcontent://') {
                try {
                    $contentInfo = $this->contentService->loadContentInfo((int) $id);
                    $location = $this->locationService->loadLocation($contentInfo->mainLocationId);
                    $hrefResolved = $this->generateUrlAliasForLocation($location, $fragment);
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->warning(
                            'While generating links for richtext, could not locate ' .
                            'Content object with ID ' . $id
                        );
                    }
                } catch (APIUnauthorizedException $e) {
                    if ($this->logger) {
                        $this->logger->notice(
                            'While generating links for richtext, unauthorized to load ' .
                            'Content object with ID ' . $id
                        );
                    }
                }
            } elseif ($scheme === 'ezlocation://') {
                try {
                    $location = $this->locationService->loadLocation((int) $id);
                    $hrefResolved = $this->generateUrlAliasForLocation($location, $fragment);
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->warning(
                            'While generating links for richtext, could not locate ' .
                            'Location with ID ' . $id
                        );
                    }
                } catch (APIUnauthorizedException $e) {
                    if ($this->logger) {
                        $this->logger->notice(
                            'While generating links for richtext, unauthorized to load ' .
                            'Location with ID ' . $id
                        );
                    }
                }
            } else {
                $hrefResolved = $href;
            }

            $hrefAttributeName = 'xlink:href';

            // For embeds set the resolved href to the separate attribute
            // Original href needs to be preserved in order to generate link parameters
            // This will need to change with introduction of UrlService and removal of URL link
            // resolving in external storage
            if ($link->localName === 'ezlink') {
                $hrefAttributeName = 'href_resolved';
            }

            $link->setAttribute($hrefAttributeName, $hrefResolved);
        }

        return $document;
    }

    private function generateUrlAliasForLocation(Location $location, string $fragment): string
    {
        $urlAlias = $this->router->generate(
            UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
            ['location' => $location]
        );

        return $urlAlias . $fragment;
    }
}
