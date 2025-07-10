<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Exception;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Throwable;

class InvalidXmlException extends InvalidArgumentException
{
    /**
     * @var \LibXMLError[]
     */
    private array $errors;

    /**
     * @param list<\LibXMLError> $errors
     */
    public function __construct(string $argumentName, array $errors = [], Throwable $previous = null)
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = trim($error->message);
        }

        parent::__construct($argumentName, implode("\n", $messages), $previous);

        $this->errors = $errors;
    }

    /**
     * @return \LibXMLError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
