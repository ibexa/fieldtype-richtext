<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;
use Ibexa\FieldTypeRichText\RichText\XmlBase;
use RuntimeException;
use XSLTProcessor;

/**
 * Validates XML document using ISO Schematron (as XSLT stylesheet), XSD and RELAX NG schemas.
 */
class Validator extends XmlBase implements ValidatorInterface
{
    /**
     * Paths to the schema files.
     *
     * @var string[]
     */
    protected array $schemas;

    /**
     * @param string[] $schemas Paths to schema files to use for validation
     */
    public function __construct(array $schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * Performs validation on given $document using injected schema files and returns validation errors.
     *
     * Handles ISO Schematron (as XSLT stylesheet), XSD and RELAX NG schemas.
     *
     * @throws \RuntimeException If schema file does not exist or can not be handled
     *
     * @return string[] An array of validation errors
     */
    public function validateDocument(DOMDocument $document): array
    {
        $this->startRecordingErrors();
        $additionalErrors = [];

        foreach ($this->schemas as $schema) {
            $errors = $this->validateBySchema($document, $schema);
            if (!empty($errors)) {
                $additionalErrors = array_merge($additionalErrors, $errors);
            }
        }

        $errors = $this->collectErrors();
        if (isset($additionalErrors)) {
            $errors = array_merge($errors, $additionalErrors);
        }

        return $errors;
    }

    /**
     * Performs validation on given $document using given $schema file and returns validation errors.
     *
     * @return string[]
     *
     * @throws \RuntimeException If given $schema file does not exist or can not be handled
     */
    protected function validateBySchema(DOMDocument $document, string $schema): array
    {
        if (!file_exists($schema) || !is_file($schema)) {
            throw new RuntimeException(
                "Validation of XML document cannot be performed, file '{$schema}' does not exist."
            );
        }

        $additionalErrors = [];
        $pathInfo = pathinfo($schema);
        switch ($pathInfo['extension']) {
            case 'xsd':
                $document->schemaValidate($schema);
                break;
            case 'rng':
                $document->relaxNGValidate($schema);
                break;
            case 'xsl':
                $additionalErrors = $this->schematronValidate($document, $schema);
                break;
            default:
                throw new RuntimeException(
                    'Validator is capable of handling ISO Schematron (as XSLT stylesheet), ' .
                    "XSD and RELAX NG schema files, ending in .xsl, .xsd or .rng.\n" .
                    "File '{$schema}' does not seem to be any of these."
                );
        }

        return $additionalErrors;
    }

    /**
     * Validates given $document using XSLT stylesheet converted from ISO Schematron schema
     * and returns an array or error messages.
     *
     * @return string[]
     */
    protected function schematronValidate(DOMDocument $document, string $filename): array
    {
        $stylesheet = $this->loadFile($filename);
        $xsltProcessor = new XSLTProcessor();
        $xsltProcessor->importStyleSheet($stylesheet);

        $result = $xsltProcessor->transformToDoc($document);

        $xpath = new DOMXPath($result);
        $xpath->registerNamespace('svrl', 'http://purl.oclc.org/dsdl/svrl');
        $xpathExpression = '//svrl:failed-assert';

        $failures = [];
        $failedAsserts = $xpath->query($xpathExpression);

        foreach ($failedAsserts as $failedAssert) {
            $failures[] = $this->formatSVRLFailure($failedAssert);
        }

        return $failures;
    }

    /**
     * Returns SVRL assertion failure as a string.
     */
    protected function formatSVRLFailure(DOMElement $failedAssert): string
    {
        $location = $failedAssert->getAttribute('location');

        return (strlen($location) ? $location . ': ' : '') . $failedAssert->textContent;
    }
}
