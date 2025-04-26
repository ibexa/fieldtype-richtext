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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InternalLinkValidatorTest extends TestCase
{
    private ContentHandler&MockObject $contentHandler;

    private LocationHandler&MockObject $locationHandler;

    /**
     * @before
     */
    public function setupInternalLinkValidator(): void
    {
        $this->contentHandler = $this->createMock(ContentHandler::class);
        $this->locationHandler = $this->createMock(LocationHandler::class);
    }

    public function testValidateFailOnNotSupportedSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Argument 'eznull' is invalid: The provided scheme 'eznull' is not supported.");

        $validator = $this->getInternalLinkValidator();
        $validator->validate('eznull', '1');
    }

    public function testValidateEzContentWithExistingContentId(): void
    {
        $validator = $this->getInternalLinkValidator();

        $contentId = 1;
        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId);

        self::assertTrue($validator->validate('ezcontent', (string)$contentId));
    }

    public function testValidateEzContentNonExistingContentId(): void
    {
        $validator = $this->getInternalLinkValidator();

        $contentId = 1;
        $exception = $this->createMock(NotFoundException::class);

        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($contentId)
            ->willThrowException($exception);

        self::assertFalse($validator->validate('ezcontent', (string)$contentId));
    }

    public function testValidateEzLocationWithExistingLocationId(): void
    {
        $validator = $this->getInternalLinkValidator();

        $locationId = 1;

        $this->locationHandler
            ->expects(self::once())
            ->method('load')
            ->with($locationId);

        self::assertTrue($validator->validate('ezlocation', (string)$locationId));
    }

    public function testValidateEzLocationWithNonExistingLocationId(): void
    {
        $validator = $this->getInternalLinkValidator();

        $locationId = 1;
        $exception = $this->createMock(NotFoundException::class);

        $this->locationHandler
            ->expects(self::once())
            ->method('load')
            ->with($locationId)
            ->willThrowException($exception);

        self::assertFalse($validator->validate('ezlocation', (string)$locationId));
    }

    public function testValidateEzRemoteWithExistingRemoteId(): void
    {
        $validator = $this->getInternalLinkValidator();

        $contentRemoteId = '0ba685755118cf95abb0fe25f3f6a1c8';

        $this->contentHandler
            ->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with($contentRemoteId);

        self::assertTrue($validator->validate('ezremote', $contentRemoteId));
    }

    public function testValidateEzRemoteWithNonExistingRemoteId(): void
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

    public function testValidateDocumentSkipMissingTargetId(): void
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

    public function testValidateDocumentEzContentExistingContentId(): void
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

    public function testValidateDocumentEzContentNonExistingContentId(): void
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

    public function testValidateDocumentEzContentExistingLocationId(): void
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

    public function testValidateDocumentEzContentNonExistingLocationId(): void
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

    public function testValidateDocumentEzRemoteExistingId(): void
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

    public function testValidateDocumentEzRemoteNonExistingId(): void
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

    private function assertContainsEzLocationInvalidLinkError(int $locationId, array $errors): void
    {
        $format = 'Invalid link "ezlocation://%d": cannot find target Location';

        self::assertContains(sprintf($format, $locationId), $errors);
    }

    private function assertContainsEzContentInvalidLinkError(int $contentId, array $errors): void
    {
        $format = 'Invalid link "ezcontent://%d": cannot find target content';

        self::assertContains(sprintf($format, $contentId), $errors);
    }

    private function assertContainsEzRemoteInvalidLinkError(string $contentId, array $errors): void
    {
        $format = 'Invalid link "ezremote://%s": cannot find target content';

        self::assertContains(sprintf($format, $contentId), $errors);
    }

    private function getInternalLinkValidator(array $methods = null): InternalLinkValidator&MockObject
    {
        return $this->getMockBuilder(InternalLinkValidator::class)
            ->setMethods($methods)
            ->setConstructorArgs([
                $this->contentHandler,
                $this->locationHandler,
            ])
            ->getMock();
    }

    private function createInputDocument(string $scheme, null|int|string $id): \DOMDocument
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
