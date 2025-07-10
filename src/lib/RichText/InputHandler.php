<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;

class InputHandler implements InputHandlerInterface
{
    private DOMDocumentFactory $domDocumentFactory;

    private ConverterDispatcher $converter;

    private Normalizer $normalizer;

    private ValidatorInterface $schemaValidator;

    private ValidatorInterface $docbookValidator;

    private RelationProcessor $relationProcessor;

    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        ConverterDispatcher $inputConverter,
        Normalizer $inputNormalizer,
        ValidatorInterface $schemaValidator,
        ValidatorInterface $dockbookValidator,
        RelationProcessor $relationProcessor
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->converter = $inputConverter;
        $this->normalizer = $inputNormalizer;
        $this->schemaValidator = $schemaValidator;
        $this->docbookValidator = $dockbookValidator;
        $this->relationProcessor = $relationProcessor;
    }

    public function fromString(?string $inputValue = null): DOMDocument
    {
        if (empty($inputValue)) {
            $inputValue = Value::EMPTY_VALUE;
        }

        if ($this->normalizer->accept($inputValue)) {
            $inputValue = $this->normalizer->normalize($inputValue);
        }

        return $this->fromDocument($this->domDocumentFactory->loadXMLString($inputValue));
    }

    public function fromDocument(DOMDocument $inputValue): DOMDocument
    {
        $errors = $this->schemaValidator->validateDocument($inputValue);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                '$inputValue',
                'Validation of XML content failed: ' . implode("\n", $errors)
            );
        }

        return $this->converter->dispatch($inputValue);
    }

    /**
     * @return array<int, array{locationIds: array<int, int>, contentIds: array<int, int>}>
     */
    public function getRelations(DOMDocument $document): array
    {
        return $this->relationProcessor->getRelations($document);
    }

    public function validate(DOMDocument $document): array
    {
        return $this->docbookValidator->validateDocument($document);
    }
}
