<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Form\DataTransformer;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RichTextTransformer implements DataTransformerInterface
{
    /**
     * @var \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface
     */
    private $inputHandler;

    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter
     */
    private $docbook2xhtml5editConverter;

    /**
     * @param \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory $domDocumentFactory
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface $inputHandler
     * @param \Ibexa\Contracts\FieldTypeRichText\RichText\Converter $docbook2xhtml5editConverter
     */
    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        InputHandlerInterface $inputHandler,
        Converter $docbook2xhtml5editConverter
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->inputHandler = $inputHandler;
        $this->docbook2xhtml5editConverter = $docbook2xhtml5editConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value): string
    {
        if (!$value) {
            $value = Value::EMPTY_VALUE;
        }

        try {
            return $this->docbook2xhtml5editConverter->convert(
                $this->domDocumentFactory->loadXMLString((string) $value)
            )->saveXML();
        } catch (NotFoundException | InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): string
    {
        try {
            return $this->inputHandler->fromString($value)->saveXML();
        } catch (NotFoundException | InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
