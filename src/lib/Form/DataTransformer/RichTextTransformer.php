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
    private DOMDocumentFactory $domDocumentFactory;

    private InputHandlerInterface $inputHandler;

    private Converter $docbook2xhtml5editConverter;

    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        InputHandlerInterface $inputHandler,
        Converter $docbook2xhtml5editConverter
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->inputHandler = $inputHandler;
        $this->docbook2xhtml5editConverter = $docbook2xhtml5editConverter;
    }

    public function transform(mixed $value): string
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

    public function reverseTransform(mixed $value): string
    {
        try {
            return $this->inputHandler->fromString($value)->saveXML();
        } catch (NotFoundException | InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
