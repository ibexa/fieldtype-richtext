<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use DOMDocument;
use LibXMLError;
use RuntimeException;

/**
 * A base class for XML document handlers.
 */
abstract class XmlBase
{
    /**
     * When recording errors holds previous setting for libxml user error handling,
     * null otherwise.
     */
    protected ?bool $useInternalErrors;

    /**
     * Textual mapping for libxml error type constants.
     *
     * @var array<int, string>
     */
    protected array $errorTypes = [
        LIBXML_ERR_WARNING => 'Warning',
        LIBXML_ERR_ERROR => 'Error',
        LIBXML_ERR_FATAL => 'Fatal error',
    ];

    /**
     * Returns DOMDocument object loaded from a given XML file $path.
     */
    protected function loadFile(string $path): DOMDocument
    {
        $document = new DOMDocument();
        $document->load($path);

        return $document;
    }

    /**
     * Formats libxml error object as a string.
     *
     * Example: Error in 6:0: Expecting an element title, got nothing
     */
    protected function formatLibXmlError(LibXMLError $error): string
    {
        return sprintf(
            '%s in %d:%d: %s',
            $this->errorTypes[$error->level],
            $error->line,
            $error->column,
            trim($error->message)
        );
    }

    /**
     * Enables user handling of libxml errors and clears error buffer.
     * Previous setting for libxml error handling is remembered.
     *
     * This method is intended to be used together with {@link collectErrors()}.
     */
    protected function startRecordingErrors(): void
    {
        $this->useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
    }

    /**
     * Returns formatted errors from libxml error buffer and restores previous setting
     * for libxml error handling.
     *
     * Before calling this method error recording must be started by calling {@link startRecordingErrors()}.
     *
     * @see startRecordingErrors()
     *
     * @uses ::formatLibXmlError()
     *
     * @throws \RuntimeException If error recording is not started
     *
     * @return string[]
     */
    protected function collectErrors(): array
    {
        if ($this->useInternalErrors === null) {
            throw new RuntimeException('Error recording not started');
        }

        $xmlErrors = libxml_get_errors();
        $errors = [];
        foreach ($xmlErrors as $error) {
            $errors[] = $this->formatLibXmlError($error);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($this->useInternalErrors);
        $this->useInternalErrors = null;

        return $errors;
    }
}
