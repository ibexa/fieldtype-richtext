<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Validator\Constraints;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RichTextValidator extends ConstraintValidator
{
    private InputHandlerInterface $inputHandler;

    public function __construct(InputHandlerInterface $inputHandler)
    {
        $this->inputHandler = $inputHandler;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (is_string($value)) {
            try {
                $value = $this->inputHandler->fromString($value);
            } catch (InvalidXmlException $e) {
                foreach ($e->getErrors() as $error) {
                    $this->context->addViolation($error->message);
                }
            }
        }

        if (!($value instanceof DOMDocument)) {
            return;
        }

        foreach ($this->inputHandler->validate($value) as $error) {
            $this->context->addViolation($error);
        }
    }
}
