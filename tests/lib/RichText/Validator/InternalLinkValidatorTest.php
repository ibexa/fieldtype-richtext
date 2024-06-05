<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use Ibexa\Contracts\Core\Persistence\Content\Handler as ContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\FieldTypeRichText\RichText\Validator\InternalLinkValidator;
use PHPUnit\Framework\TestCase;

class InternalLinkValidatorTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Persistence\Content\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $contentHandler;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $locationHandler;

    /**
     * @before
     */
    public function setupInternalLinkValidator()
    {
        $this->contentHandler = $this->createMock(ContentHandler::class);
        $this->locationHandler = $this->createMock(LocationHandler::class);
    }

    public function testValidateFailOnNotSupportedSchema()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Argument 'eznull' is invalid: The provided scheme 'eznull' is not supported.");

        $validator = $this->getInternalLinkValidator();
        $validator->validate('eznull', 1);
    }

    public function testValidateEzContentWithExistingContentId()
    {
        $validator = $this->getInternalLinkValidator();

        $contentId = 1;
        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId);

        self::assertTrue($validator->validate('ezcontent', $contentId));
    }

    public function testValidateEzContentNonExistingContentId()
    {
        $validator = $this->getInternalLinkValidator();

        $contentId = 1;
        $exception = $this->createMock(NotFoundException::class);

        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId)
            ->willThrowException($exception);

        self::assertFalse($validator->validate('ezcontent', $contentId));
    }

    public function testValidateEzLocationWithExistingLocationId()
    {
        $validator = $this->getInternalLinkValidator();

        $locationId = 1;

        $this->locationHandler
            ->expects(self::once())
            ->method('load')
            ->with($locationId);

        self::assertTrue($validator->validate('ezlocation', $locationId));
    }

    public function testValidateEzLocationWithNonExistingLocationId()
    {
        $validator = $this->getInternalLinkValidator();

        $locationId = 1;
        $exception = $this->createMock(NotFoundException::class);

        $this->locationHandler
            ->expects(self::once())
            ->method('load')
            ->with($locationId)
            ->willThrowException($exception);

        self::assertFalse($validator->validate('ezlocation', $locationId));
    }

    public function testValidateEzRemoteWithExistingRemoteId()
    {
        $validator = $this->getInternalLinkValidator();

        $contentRemoteId = '0ba685755118cf95abb0fe25f3f6a1c8';

        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with($contentRemoteId);

        self::assertTrue($validator->validate('ezremote', $contentRemoteId));
    }

    public function testValidateEzRemoteWithNonExistingRemoteId()
    {
        $validator = $this->getInternalLinkValidator();

        $contentRemoteId = '0ba685755118cf95abb0fe25f3f6a1c8';
        $exception = $this->createMock(NotFoundException::class);

        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with($contentRemoteId)
            ->willThrowException($exception);

        self::assertFalse($validator->validate('ezremote', $contentRemoteId));
    }

    public function testValidateDocumentSkipMissingTargetId()
    {
        $scheme = 'ezcontent';
        $contentId = null;

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::never())
            ->method('validate')
            ->with($scheme, $contentId);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $contentId)
        );

        self::assertEmpty($errors);
    }

    public function testValidateDocumentEzContentExistingContentId()
    {
        $scheme = 'ezcontent';
        $contentId = 1;

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $contentId)
            ->willReturn(true);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $contentId)
        );

        self::assertEmpty($errors);
    }

    public function testValidateDocumentEzContentNonExistingContentId()
    {
        $scheme = 'ezcontent';
        $contentId = 1;

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $contentId)
            ->willReturn(false);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $contentId)
        );

        self::assertCount(1, $errors);
        $this->assertContainsEzContentInvalidLinkError($contentId, $errors);
    }

    public function testValidateDocumentEzContentExistingLocationId()
    {
        $scheme = 'ezlocation';
        $locationId = 1;

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $locationId)
            ->willReturn(true);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $locationId)
        );

        self::assertEmpty($errors);
    }

    public function testValidateDocumentEzContentNonExistingLocationId()
    {
        $scheme = 'ezlocation';
        $locationId = 1;

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $locationId)
            ->willReturn(false);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $locationId)
        );

        self::assertCount(1, $errors);
        $this->assertContainsEzLocationInvalidLinkError($locationId, $errors);
    }

    public function testValidateDocumentEzRemoteExistingId()
    {
        $scheme = 'ezremote';
        $contentRemoteId = '0ba685755118cf95abb0fe25f3f6a1c8';

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $contentRemoteId)
            ->willReturn(true);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $contentRemoteId)
        );

        self::assertEmpty($errors);
    }

    public function testValidateDocumentEzRemoteNonExistingId()
    {
        $scheme = 'ezremote';
        $contentRemoteId = '0ba685755118cf95abb0fe25f3f6a1c8';

        $validator = $this->getInternalLinkValidator(['validate']);
        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($scheme, $contentRemoteId)
            ->willReturn(false);

        $errors = $validator->validateDocument(
            $this->createInputDocument($scheme, $contentRemoteId)
        );

        self::assertCount(1, $errors);
        $this->assertContainsEzRemoteInvalidLinkError($contentRemoteId, $errors);
    }

    private function assertContainsEzLocationInvalidLinkError($locationId, array $errors)
    {
        $format = 'Invalid link "ezlocation://%d": cannot find target Location';

        self::assertContains(sprintf($format, $locationId), $errors);
    }

    private function assertContainsEzContentInvalidLinkError($contentId, array $errors)
    {
        $format = 'Invalid link "ezcontent://%d": cannot find target content';

        self::assertContains(sprintf($format, $contentId), $errors);
    }

    private function assertContainsEzRemoteInvalidLinkError($contentId, array $errors)
    {
        $format = 'Invalid link "ezremote://%s": cannot find target content';

        self::assertContains(sprintf($format, $contentId), $errors);
    }

    /**
     * @return \Ibexa\FieldTypeRichText\FieldType\RichText\InternalLinkValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getInternalLinkValidator(array $methods = null)
    {
        return $this->getMockBuilder(InternalLinkValidator::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $this->contentHandler,
                $this->locationHandler,
            ])
            ->getMock();
    }

    private function createInputDocument($scheme, $id)
    {
        $url = $scheme . '://' . $id;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="' . $url . '">Content link</link>
    </para>
</section>';

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }
}
