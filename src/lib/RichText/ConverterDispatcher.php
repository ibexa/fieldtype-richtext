<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;

/**
 * Dispatcher for various converters depending on the XML document namespace.
 */
class ConverterDispatcher
{
    /**
     * Mapping of namespaces to converters.
     *
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter[]
     */
    protected $mapping = [];

    /**
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\Converter[] $converterMap
     */
    public function __construct($converterMap)
    {
        foreach ($converterMap as $namespace => $converter) {
            $this->addConverter($namespace, $converter);
        }
    }

    /**
     * Adds converter mapping.
     *
     * @param string $namespace
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\Converter|null $converter
     */
    public function addConverter($namespace, Converter $converter = null)
    {
        $this->mapping[$namespace] = $converter;
    }

    /**
     * Dispatches DOMDocument to the namespace mapped converter.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function dispatch(DOMDocument $document)
    {
        $documentNamespace = $document->documentElement->lookupNamespaceURI(null);
        // checking for null as ezxml has no default namespace...
        if ($documentNamespace === null) {
            $documentNamespace = $document->documentElement->lookupNamespaceURI('xhtml');
        }

        foreach ($this->mapping as $namespace => $converter) {
            if ($documentNamespace === $namespace) {
                if ($converter === null) {
                    return $document;
                }

                return $converter->convert($document);
            }
        }

        throw new NotFoundException('Converter', $documentNamespace);
    }
}

class_alias(ConverterDispatcher::class, 'EzSystems\EzPlatformRichText\eZ\RichText\ConverterDispatcher');
