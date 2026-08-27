<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\DOMDocumentLoaderInterface;
use LibXMLError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class DOMDocumentLoader implements DOMDocumentLoaderInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function loadXML(string $xml, array $logContext = []): DOMDocument
    {
        $document = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $document->loadXML($xml);
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        if (!empty($errors)) {
            $this->logger->warning(
                'RichText XML document loaded with libxml errors',
                $logContext + ['errors' => array_map([$this, 'formatError'], $errors)]
            );
        }

        return $document;
    }

    private function formatError(LibXMLError $error): string
    {
        return sprintf('[line %d] %s', $error->line, trim($error->message));
    }
}
