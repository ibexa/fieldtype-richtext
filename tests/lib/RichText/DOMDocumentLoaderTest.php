<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use Ibexa\FieldTypeRichText\RichText\DOMDocumentLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ibexa\FieldTypeRichText\RichText\DOMDocumentLoader
 */
final class DOMDocumentLoaderTest extends TestCase
{
    /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $logger;

    private DOMDocumentLoader $loader;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->loader = new DOMDocumentLoader($this->logger);
    }

    public function testLoadValidXML(): void
    {
        $this->logger->expects(self::never())->method('warning');

        $document = $this->loader->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook"><para>Lorem</para></section>'
        );

        self::assertNotNull($document->documentElement);
        self::assertSame('section', $document->documentElement->localName);
    }

    public function testLoadXMLWithInvalidXmlIdLogsWarningWithContext(): void
    {
        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'RichText XML document loaded with libxml errors',
                self::callback(static function (array $context): bool {
                    return $context['contentId'] === 42
                        && $context['fieldId'] === 7
                        && count($context['errors']) === 1
                        && strpos($context['errors'][0], '227') !== false;
                })
            );

        $document = $this->loader->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook"><para xml:id="227">Lorem</para></section>',
            ['contentId' => 42, 'fieldId' => 7]
        );

        self::assertNotNull($document->documentElement);
        self::assertStringContainsString('xml:id="227"', (string)$document->saveXML());
    }

    public function testLoadUnparseableXMLReturnsEmptyDocumentAndLogs(): void
    {
        $this->logger->expects(self::once())->method('warning');

        $document = $this->loader->loadXML('<section><para>unclosed</section>');

        self::assertNull($document->documentElement);
    }

    public function testLoadXMLRestoresLibxmlErrorHandling(): void
    {
        $previous = libxml_use_internal_errors(false);

        $this->loader->loadXML('<broken');

        self::assertFalse(libxml_use_internal_errors($previous));
        self::assertSame([], libxml_get_errors());
    }
}
