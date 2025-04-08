<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\FieldTypeRichText\RichText\ConverterDispatcher;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\InputHandler;
use Ibexa\FieldTypeRichText\RichText\Normalizer;
use Ibexa\FieldTypeRichText\RichText\RelationProcessor;
use Ibexa\FieldTypeRichText\RichText\XMLSanitizer;
use PHPUnit\Framework\TestCase;

class InputHandlerTest extends TestCase
{
    /**
     * @var \Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $domDocumentFactory;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\ConverterDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converter;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\Normalizer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $normalizer;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $schemaValidator;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $docbookValidator;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\RelationProcessor
     */
    private $relationProcessor;

    /**
     * @var \Ibexa\FieldTypeRichText\RichText\InputHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $inputHandler;

    /**
     * {@inheritdoc}
     */
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

    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\InputHandler::fromString
     */
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
            ->setMethods(['fromDocument'])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $this->normalizer
            ->expects(self::once())
            ->method('accept')
            ->with($inputXml)
            ->willReturn(false);

        $outputDocument = $this->createMock(DOMDocument::class);

        $inputHandler
            ->expects(self::once())
            ->method('fromDocument')
            ->willReturnCallback(function (DOMDocument $document) use ($inputXml, $outputDocument) {
                $this->assertEquals($inputXml, $document->saveXML());

                return $outputDocument;
            });

        self::assertEquals($outputDocument, $inputHandler->fromString($inputXml));
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\InputHandler::fromDocument
     */
    public function testFromDocument(): void
    {
        $inputDocument = $this->createMock(DOMDocument::class);
        $outputDocument = $this->createMock(DOMDocument::class);

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

    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\InputHandler::fromDocument
     */
    public function testFromDocumentThrowsInvalidArgumentException(): void
    {
        $inputDocument = $this->createMock(DOMDocument::class);

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

    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\InputHandler::getRelations
     */
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
            Relation::LINK => [
                'locationIds' => [72, 61],
                'contentIds' => [70, 75],
            ],
            Relation::EMBED => [
                'locationIds' => [],
                'contentIds' => [],
            ],
        ], $this->inputHandler->getRelations($document));
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\RichText\InputHandler::validate
     */
    public function testValidate(): void
    {
        $document = $this->createMock(DOMDocument::class);
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
