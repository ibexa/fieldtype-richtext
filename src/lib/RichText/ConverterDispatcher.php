<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Dispatcher for various converters depending on the XML document namespace.
 */
class ConverterDispatcher
{
    /**
     * Mapping of namespaces to converters.
     *
     * @var array<string, \Ibexa\Contracts\FieldTypeRichText\RichText\Converter|null>
     */
    protected array $mapping = [];

    /**
     * @param array<string, \Ibexa\Contracts\FieldTypeRichText\RichText\Converter|null> $converterMap
     */
    public function __construct(array $converterMap)
    {
        foreach ($converterMap as $namespace => $converter) {
            $this->addConverter($namespace, $converter);
        }
    }

    /**
     * Adds converter mapping.
     */
    public function addConverter(string $namespace, ?Converter $converter = null): void
    {
        $this->mapping[$namespace] = $converter;
    }

    /**
     * Dispatches DOMDocument to the namespace mapped converter.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function dispatch(DOMDocument $document): DOMDocument
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
