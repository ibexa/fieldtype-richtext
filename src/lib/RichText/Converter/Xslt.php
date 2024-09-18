<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\FieldTypeRichText\RichText\XmlBase;
use RuntimeException;
use XSLTProcessor;

/**
 * Converts DOMDocument objects using XSLT stylesheets.
 */
class Xslt extends XmlBase implements Converter
{
    /**
     * Path to stylesheet to use.
     */
    protected string $stylesheet;

    /**
     * Array of XSL stylesheets to add to the main one, grouped by priority.
     *
     * @var array<int, array<string>>
     */
    protected array $customStylesheets = [];

    private XSLTProcessor $xsltProcessor;

    /**
     * @param string $stylesheet Stylesheet to use for conversion
     * @param array<array{priority: int, path: string}> $customStylesheets Array of XSL stylesheets. Each entry consists in a hash having "path" and "priority" keys.
     */
    public function __construct(string $stylesheet, array $customStylesheets = [])
    {
        $this->stylesheet = $stylesheet;

        // Grouping stylesheets by priority.
        foreach ($customStylesheets as $customStylesheet) {
            $this->customStylesheets[(int)$customStylesheet['priority']][] = $customStylesheet['path'];
        }
    }

    /**
     * Returns the XSLTProcessor to use to transform internal XML to HTML5.
     *
     * @throws \RuntimeException
     * @throws \DOMException
     */
    protected function getXSLTProcessor(): XSLTProcessor
    {
        if (isset($this->xsltProcessor)) {
            return $this->xsltProcessor;
        }

        $xslDoc = $this->loadFile($this->stylesheet);

        // Now loading custom xsl stylesheets dynamically.
        // According to XSL spec, each <xsl:import> tag MUST be loaded BEFORE any other element.
        $insertBeforeEl = $xslDoc->documentElement->firstChild;
        foreach ($this->getSortedCustomStylesheets() as $stylesheet) {
            if (!file_exists($stylesheet)) {
                throw new RuntimeException("Cannot find XSL stylesheet for RichText rendering: $stylesheet");
            }

            $newEl = $xslDoc->createElement('xsl:import');
            $hrefAttr = $xslDoc->createAttribute('href');

            // Prevents showing XSLTProcessor::importStylesheet() warning on Windows file system
            $hrefAttr->value = str_replace('\\', '/', $stylesheet);

            $newEl->appendChild($hrefAttr);
            $xslDoc->documentElement->insertBefore($newEl, $insertBeforeEl);
        }
        // Now reload XSL DOM to "refresh" it.
        $xslDoc->loadXML($xslDoc->saveXML());

        $this->xsltProcessor = new XSLTProcessor();
        $this->xsltProcessor->importStyleSheet($xslDoc);
        $this->xsltProcessor->registerPHPFunctions();

        return $this->xsltProcessor;
    }

    /**
     * Returns custom stylesheets to load, sorted.
     * The order is from the lowest priority to the highest since in case of a conflict,
     * the last loaded XSL template always wins.
     *
     * @return list<string>
     */
    protected function getSortedCustomStylesheets(): array
    {
        ksort($this->customStylesheets);

        // flatten [priority => stylesheet[]] array to return a simple list
        return array_merge(...$this->customStylesheets);
    }

    /**
     * Performs conversion of the given $document using XSLT stylesheet.
     *
     * @throws \DOMException
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function convert(DOMDocument $xmlDoc): DOMDocument
    {
        if (!file_exists($this->stylesheet)) {
            throw new InvalidArgumentException(
                'stylesheetPath',
                "Conversion of XML document cannot be performed, file '{$this->stylesheet}' does not exist."
            );
        }

        $processor = $this->getXSLTProcessor();

        $this->startRecordingErrors();

        $document = $processor->transformToDoc($xmlDoc);

        $errors = $this->collectErrors();

        if (!empty($errors) || $document === false) {
            throw new InvalidArgumentException(
                '$xmlDoc',
                'Transformation of XML content failed: ' . implode("\n", $errors)
            );
        }

        return $document;
    }
}
