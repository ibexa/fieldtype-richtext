<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\FieldTypeRichText\RichText\ConverterDispatcher;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\InputHandler;
use Ibexa\FieldTypeRichText\RichText\Normalizer;
use Ibexa\FieldTypeRichText\RichText\RelationProcessor;
use Ibexa\FieldTypeRichText\RichText\XMLSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(InputHandler::class)]
class InputHandlerTest extends TestCase
{
    private DOMDocumentFactory $domDocumentFactory;

    private ConverterDispatcher&MockObject $converter;

    private Normalizer&MockObject $normalizer;

    private ValidatorInterface&MockObject $schemaValidator;

    private ValidatorInterface&MockObject $docbookValidator;

    private RelationProcessor $relationProcessor;

    private InputHandler $inputHandler;

    protected function setUp(): void
    {
        $this->domDocumentFactory = new DOMDocumentFactory(new XMLSanitizer());
        $this->converter = $this->createMock(ConverterDispatcher::class);
        $this->normalizer = $this->createMock(Normalizer::class);
        $this->schemaValidator = $this->createMock(ValidatorInterface::class);
        $this->docbookValidator = $this->createMock(ValidatorInterface::class);
        $this->relationProcessor = new RelationProcessor();

        $this->inputHandler = new InputHandler(
            $this->domDocumentFactory,
            $this->converter,
            $this->normalizer,
            $this->schemaValidator,
            $this->docbookValidator,
            $this->relationProcessor
        );
    }

    public function testFromString(): void
    {
        $inputXml = '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
  <p>Hello World!</p>
</section>
';

        $inputHandler = $this->getMockBuilder(InputHandler::class)
            ->setConstructorArgs([
                $this->domDocumentFactory,
                $this->converter,
                $this->normalizer,
                $this->schemaValidator,
                $this->docbookValidator,
                $this->relationProcessor,
            ])
            ->onlyMethods(['fromDocument'])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $this->normalizer
            ->expects(self::once())
            ->method('accept')
            ->with($inputXml)
            ->willReturn(false);

        $outputDocument = self::createStub(DOMDocument::class);

        $inputHandler
            ->expects(self::once())
            ->method('fromDocument')
            ->willReturnCallback(static function (DOMDocument $document) use ($inputXml, $outputDocument): DOMDocument {
                self::assertEquals($inputXml, $document->saveXML());

                return $outputDocument;
            });

        self::assertEquals($outputDocument, $inputHandler->fromString($inputXml));
    }

    public function testFromDocument(): void
    {
        $inputDocument = self::createStub(DOMDocument::class);
        $outputDocument = self::createStub(DOMDocument::class);

        $this->schemaValidator
            ->expects(self::once())
            ->method('validateDocument')
            ->with($inputDocument)
            ->willReturn([]);

        $this->converter
            ->expects(self::once())
            ->method('dispatch')
            ->with($inputDocument)
            ->willReturn($outputDocument);

        self::assertEquals($outputDocument, $this->inputHandler->fromDocument($inputDocument));
    }

    public function testFromDocumentThrowsInvalidArgumentException(): void
    {
        $inputDocument = self::createStub(DOMDocument::class);

        $this->schemaValidator
            ->expects(self::once())
            ->method('validateDocument')
            ->with($inputDocument)
            ->willReturn([
                'At least one error',
            ]);

        $this->converter
            ->expects(self::never())
            ->method('dispatch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$inputValue\' is invalid: Validation of XML content failed: At least one error');

        $this->inputHandler->fromDocument($inputDocument);
    }

    public function testGetRelations(): void
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0">
    <title>Some text</title>
    <para><link xlink:href="ezlocation://72">link1</link></para>
    <para><link xlink:href="ezlocation://61">link2</link></para>
    <para><link xlink:href="ezlocation://61">link3</link></para>
    <para><link xlink:href="ezcontent://70">link4</link></para>
    <para><link xlink:href="ezcontent://75">link5</link></para>
    <para><link xlink:href="ezcontent://75">link6</link></para>
</section>
EOT;

        $document = new DOMDocument();
        $document->loadXML($xml);

        self::assertEquals([
            RelationType::LINK->value => [
                'locationIds' => [72, 61],
                'contentIds' => [70, 75],
            ],
            RelationType::EMBED->value => [
                'locationIds' => [],
                'contentIds' => [],
            ],
        ], $this->inputHandler->getRelations($document));
    }

    public function testValidate(): void
    {
        $document = self::createStub(DOMDocument::class);
        $expectedErrors = [
            'Example error A',
            'Example error B',
            'Example error C',
        ];

        $this->docbookValidator
            ->expects(self::once())
            ->method('validateDocument')
            ->with($document)
            ->willReturn($expectedErrors);

        $actualErrors = $this->inputHandler->validate($document);

        self::assertEquals($expectedErrors, $actualErrors);
    }
}
