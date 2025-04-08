<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Form\DataTransformer;

use DOMDocument;
use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\RichText\XMLSanitizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RichTextTransformerTest extends TestCase
{
    /** @var \Ibexa\FieldTypeRichText\RichText\InputHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $inputHandler;

    /** @var \Ibexa\FieldTypeRichText\RichText\Converter|\PHPUnit\Framework\MockObject\MockObject */
    private $docbook2xhtml5editConverter;

    /** @var \Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer */
    private $richTextTransformer;

    protected function setUp(): void
    {
        $this->inputHandler = $this->createMock(InputHandlerInterface::class);
        $this->docbook2xhtml5editConverter = $this->createMock(Converter::class);

        $this->richTextTransformer = new RichTextTransformer(
            // DOMDocumentFactory is final
            new DOMDocumentFactory(new XMLSanitizer()),
            $this->inputHandler,
            $this->docbook2xhtml5editConverter
        );
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer::transform
     */
    public function testTransform(): void
    {
        $outputXML = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit"><p>This is a paragraph.</p></section>';

        $outputDocument = new DOMDocument();
        $outputDocument->loadXML($outputXML);

        $inputXML =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0"><para>This is a paragraph.</para></section>';

        $this->docbook2xhtml5editConverter
            ->expects($this->once())
            ->method('convert')
            ->willReturnCallback(function (DOMDocument $doc) use ($inputXML, $outputDocument) {
                $this->assertXmlStringEqualsXmlString($inputXML, $doc->saveXML());

                return $outputDocument;
            });

        $this->assertXmlStringEqualsXmlString($outputXML, $this->richTextTransformer->transform($inputXML));
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer::transform
     */
    public function testTransformThrowsTransformationFailedException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Argument \'$xmlString\' is invalid: Start tag expected, \'<\' not found');

        $this->richTextTransformer->transform('Invalid XML');
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer::reverseTransform
     */
    public function testReverseTransform(): void
    {
        $inputXML = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '
            <section xmlns="http://ibexa.co/namespaces/ezpublish5/xhtml5/edit">
              <p>This is a paragraph.</p>
            </section>
            ';

        $outputXML =
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<section xmlns="http://docbook.org/ns/docbook" version="5.0-variant ezpublish-1.0">
                <para>This is a paragraph.</para>
            </section>
            ';

        $outputDocument = new DOMDocument();
        $outputDocument->loadXML($outputXML);

        $this->inputHandler
            ->expects($this->once())
            ->method('fromString')
            ->with($inputXML)
            ->willReturn($outputDocument);

        $this->assertXmlStringEqualsXmlString($outputXML, $this->richTextTransformer->reverseTransform($inputXML));
    }

    /**
     * @covers \Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer::reverseTransform
     *
     * @dataProvider dataProviderForReverseTransformTransformationFailedException
     */
    public function testReverseTransformTransformationFailedException(Exception $exception): void
    {
        $value = 'Invalid XML';

        $this->expectException(TransformationFailedException::class);

        $this->inputHandler
            ->expects($this->once())
            ->method('fromString')
            ->with($value)
            ->willThrowException($exception);

        $this->richTextTransformer->reverseTransform($value);
    }

    public function dataProviderForReverseTransformTransformationFailedException()
    {
        return [
            [$this->createMock(NotFoundException::class)],
            [$this->createMock(InvalidArgumentException::class)],
        ];
    }
}

class_alias(RichTextTransformerTest::class, 'EzSystems\Tests\EzPlatformRichText\Form\DataTransformer\RichTextTransformerTest');
