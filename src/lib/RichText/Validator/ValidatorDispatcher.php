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
     * @var array<string, ValidatorInterface|null>
     */
    protected array $mapping = [];

    /**
     * @param array<string, ValidatorInterface|null> $validatorMap
     */
    public function __construct(array $validatorMap)
    {
        foreach ($validatorMap as $namespace => $validator) {
            $this->addValidator($namespace, $validator);
        }
    }

    /**
     * Adds validator mapping.
     */
    public function addValidator(string $namespace, ?ValidatorInterface $validator = null): void
    {
        $this->mapping[$namespace] = $validator;
    }

    /**
     * Dispatches DOMDocument to the namespace mapped validator.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return string[]
     */
    public function dispatch(DOMDocument $document): array
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

                return $validator->validateDocument($document);
            }
        }

        throw new NotFoundException('Validator', $documentNamespace);
    }

    public function validateDocument(DOMDocument $xmlDocument): array
    {
        return $this->dispatch($xmlDocument);
    }
}
