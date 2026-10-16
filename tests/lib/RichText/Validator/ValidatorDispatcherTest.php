<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\FieldTypeRichText\RichText\Exception\UnsupportedNamespaceException;
use Ibexa\FieldTypeRichText\RichText\Validator\ValidatorDispatcher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\Validator\ValidatorDispatcher
 * @covers \Ibexa\FieldTypeRichText\RichText\Exception\UnsupportedNamespaceException
 */
final class ValidatorDispatcherTest extends TestCase
{
    private const string DOCBOOK_NAMESPACE = 'http://docbook.org/ns/docbook';
    private const string XHTML5_EDIT_NAMESPACE = 'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit';

    public function testDispatchesToMappedValidator(): void
    {
        $document = self::loadDocument('<section xmlns="' . self::XHTML5_EDIT_NAMESPACE . '"/>');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects(self::once())
            ->method('validateDocument')
            ->with($document)
            ->willReturn(['error']);

        $dispatcher = new ValidatorDispatcher([
            self::DOCBOOK_NAMESPACE => null,
            self::XHTML5_EDIT_NAMESPACE => $validator,
        ]);

        self::assertSame(['error'], $dispatcher->dispatch($document));
    }

    public function testReturnsNoErrorsForNamespaceWithoutValidator(): void
    {
        $dispatcher = new ValidatorDispatcher([self::DOCBOOK_NAMESPACE => null]);

        self::assertSame([], $dispatcher->dispatch(self::loadDocument('<section xmlns="' . self::DOCBOOK_NAMESPACE . '"/>')));
    }

    /**
     * @dataProvider provideUnsupportedDocuments
     */
    public function testThrowsUnsupportedNamespaceException(string $xml, string $expectedMessage): void
    {
        $dispatcher = new ValidatorDispatcher([
            self::DOCBOOK_NAMESPACE => null,
            self::XHTML5_EDIT_NAMESPACE => null,
        ]);

        try {
            $dispatcher->dispatch(self::loadDocument($xml));
            self::fail('UnsupportedNamespaceException was expected.');
        } catch (NotFoundException $e) {
            // Existing callers catch the NotFoundException thrown before
            self::assertInstanceOf(UnsupportedNamespaceException::class, $e);
            self::assertSame($expectedMessage, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideUnsupportedDocuments(): iterable
    {
        $supported = '"' . self::DOCBOOK_NAMESPACE . '", "' . self::XHTML5_EDIT_NAMESPACE . '"';

        yield 'no namespace' => [
            '<section><para>x</para></section>',
            'The root element "section" has no namespace. Supported namespaces: ' . $supported . '.',
        ];

        yield 'HTML fragment' => [
            '<p>Hello <b>world</b></p>',
            'The root element "p" has no namespace. Supported namespaces: ' . $supported . '.',
        ];

        yield 'unsupported namespace' => [
            '<unknown xmlns="http://www.w3.org/2013/foobar"><format/></unknown>',
            'The root element "unknown" has an unsupported namespace "http://www.w3.org/2013/foobar". Supported namespaces: ' . $supported . '.',
        ];
    }

    private static function loadDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document;
    }
}
