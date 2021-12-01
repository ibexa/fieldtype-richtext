<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Dispatcher for various validators depending on the XML document namespace.
 */
class ValidatorDispatcher implements ValidatorInterface
{
    /**
     * Mapping of namespaces to validators.
     *
     * @var \Ibexa\FieldTypeRichText\eZ\RichText\Validator[]
     */
    protected $mapping = [];

    /**
     * @param \Ibexa\FieldTypeRichText\eZ\RichText\Validator[] $validatorMap
     */
    public function __construct($validatorMap)
    {
        foreach ($validatorMap as $namespace => $validator) {
            $this->addValidator($namespace, $validator);
        }
    }

    /**
     * Adds validator mapping.
     *
     * @param string $namespace
     * @param \Ibexa\FieldTypeRichText\eZ\RichText\Validator $validator
     */
    public function addValidator($namespace, ValidatorInterface $validator = null)
    {
        $this->mapping[$namespace] = $validator;
    }

    /**
     * Dispatches DOMDocument to the namespace mapped validator.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @param \DOMDocument $document
     *
     * @return string[]
     */
    public function dispatch(DOMDocument $document)
    {
        $documentNamespace = $document->documentElement->lookupNamespaceURI(null);
        // checking for null as ezxml has no default namespace...
        if ($documentNamespace === null) {
            $documentNamespace = $document->documentElement->lookupNamespaceURI('xhtml');
        }

        foreach ($this->mapping as $namespace => $validator) {
            if ($documentNamespace === $namespace) {
                if ($validator === null) {
                    return [];
                }

                return $validator->validate($document);
            }
        }

        throw new NotFoundException('Validator', $documentNamespace);
    }

    /**
     * {@inheritdoc}
     */
    public function validateDocument(DOMDocument $xmlDocument): array
    {
        return $this->dispatch($xmlDocument);
    }
}

class_alias(ValidatorDispatcher::class, 'EzSystems\EzPlatformRichText\eZ\RichText\Validator\ValidatorDispatcher');
