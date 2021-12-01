<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;

class InputHandler implements InputHandlerInterface
{
    /**
     * @var \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\ConverterDispatcher
     */
    private $converter;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\Normalizer
     */
    private $normalizer;

    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface
     */
    private $schemaValidator;

    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface
     */
    private $docbookValidator;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\RelationProcessor
     */
    private $relationProcessor;

    /**
     * @param \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory $domDocumentFactory
     * @param \Ibexa\FieldTypeRichText\RichText\ConverterDispatcher $inputConverter
     * @param \Ibexa\FieldTypeRichText\RichText\Normalizer $inputNormalizer
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface $schemaValidator
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface $dockbookValidator
     * @param \Ibexa\FieldTypeRichText\RichText\RelationProcessor $relationProcessor
     */
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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
     * @inheritdoc
     */
    public function getRelations(DOMDocument $document): array
    {
        return $this->relationProcessor->getRelations($document);
    }

    /**
     * @inheritdoc
     */
    public function validate(DOMDocument $document): array
    {
        return $this->docbookValidator->validateDocument($document);
    }
}

class_alias(InputHandler::class, 'EzSystems\EzPlatformRichText\eZ\RichText\InputHandler');
