<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Validator\Constraints;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException;
use Ibexa\FieldTypeRichText\Validator\Constraints\RichText;
use Ibexa\FieldTypeRichText\Validator\Constraints\RichTextValidator;
use LibXMLError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RichTextValidatorTest extends TestCase
{
    private InputHandlerInterface&MockObject $inputHandler;

    private ExecutionContextInterface&MockObject $executionContext;

    private RichTextValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputHandler = $this->createMock(InputHandlerInterface::class);
        $this->executionContext = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new RichTextValidator($this->inputHandler);
        $this->validator->initialize($this->executionContext);
    }

    public function testValidateInvalidXMLString(): void
    {
        $xml = 'THIS IS INVALID XML';

        $expectedErrors = [
            $this->createLibXMLError('This is not XML string: A'),
            $this->createLibXMLError('This is not XML string: B'),
        ];

        $this->inputHandler
            ->expects(self::once())
            ->method('fromString')
            ->with($xml)
            ->willThrowException($this->createInvalidXmlExceptionMock($expectedErrors));

        $this->executionContext
            ->method('addViolation')
            ->willReturnOnConsecutiveCalls($this->fetchErrorMessages($expectedErrors));

        $this->inputHandler
            ->expects(self::never())
            ->method('validate');

        $this->validator->validate($xml, new RichText());
    }

    public function testValidateNonXMLValue(): void
    {
        $object = new stdClass();

        $this->inputHandler
            ->expects(self::never())
            ->method('fromString');

        $this->inputHandler
            ->expects(self::never())
            ->method('validate');

        $this->executionContext
            ->expects(self::never())
            ->method('addViolation');

        $this->validator->validate($object, new RichText());
    }

    public function testValidateDOMDocument(): void
    {
        $doc = $this->createMock(DOMDocument::class);

        $expectedErrors = [
            'This is not XML string: A',
            'This is not XML string: B',
        ];

        $this->inputHandler
            ->expects(self::never())
            ->method('fromString');

        $this->inputHandler
            ->expects(self::once())
            ->method('validate')
            ->with($doc)
            ->willReturn($expectedErrors);

        $this->executionContext
            ->expects(self::exactly(count($expectedErrors)))
            ->method('addViolation')
            ->willReturnOnConsecutiveCalls($expectedErrors);

        $this->validator->validate($doc, new RichText());
    }

    /**
     * @param array<LibXMLError> $errors
     */
    private function createInvalidXmlExceptionMock(array $errors): InvalidXmlException
    {
        $ex = $this->createMock(InvalidXmlException::class);
        $ex->expects(self::once())
            ->method('getErrors')
            ->willReturn($errors);

        return $ex;
    }

    private function createLibXMLError(string $message): LibXMLError
    {
        $error = new LibXMLError();
        $error->message = $message;

        return $error;
    }

    /**
     * @param array<LibXMLError> $errors
     *
     * @return array<string>
     */
    private function fetchErrorMessages(array $errors): array
    {
        return array_map(static function (LibXMLError $error): string {
            return $error->message;
        }, $errors);
    }
}
