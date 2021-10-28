<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\FieldTypeRichText\RichText\Validator\ValidatorAggregate;
use PHPUnit\Framework\TestCase;

class ValidatorAggregateTest extends TestCase
{
    /**
     * @covers \EzSystems\EzPlatformRichText\RichText\ValidatorAggregate::validateDocument
     */
    public function testValidateDocument(): void
    {
        $doc = $this->createMock(DOMDocument::class);

        $expectedErrors = [];
        $validators = [];

        for ($i = 0; $i < 3; ++$i) {
            $errorMessage = "Validation error $i";

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->expects($this->once())
                ->method('validateDocument')
                ->with($doc)
                ->willReturn([$errorMessage]);

            $expectedErrors[] = $errorMessage;
            $validators[] = $validator;
        }

        $aggregate = new ValidatorAggregate($validators);
        $actualErrors = $aggregate->validateDocument($doc);

        $this->assertEquals($expectedErrors, $actualErrors);
    }
}

class_alias(ValidatorAggregateTest::class, 'EzSystems\Tests\EzPlatformRichText\RichText\Validator\ValidatorAggregateTest');
