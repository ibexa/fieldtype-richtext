<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Mcp\Tool;

use DOMDocument;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\Contracts\Mcp\Attribute\McpTool;
use Ibexa\Contracts\Mcp\McpCapabilityInterface;
use Ibexa\FieldTypeRichText\FieldType\RichText\Value;
use Ibexa\FieldTypeRichText\Mcp\Tool\Output\RichTextValidationResult;
use Ibexa\FieldTypeRichText\Mcp\Tool\Output\RichTextXml;
use Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @experimental
 */
final readonly class RichTextTools implements McpCapabilityInterface
{
    /**
     * Default namespace of the DocBook variant stored in RichText fields.
     */
    private const string DOCBOOK_NAMESPACE = 'http://docbook.org/ns/docbook';

    /**
     * Default namespace of the XHTML5 edit format accepted by the RichText input handler.
     */
    private const string XHTML5_EDIT_NAMESPACE = 'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit';

    public function __construct(
        private InputHandlerInterface $inputHandler,
    ) {
    }

    /**
     * @param string|null $html Plain text or HTML markup to convert. Plain text is wrapped in a single paragraph, HTML fragments such as "<p>Hello</p>" are wrapped in a RichText section, and a complete DocBook or XHTML5 edit document is converted as-is. When null or empty, an empty RichText document is returned.
     *
     * @throws \Mcp\Exception\ToolCallException
     */
    #[McpTool(
        name: 'convert_to_richtext_xml',
        title: 'Convert to RichText XML',
        description: 'Converts plain text or HTML markup into the DocBook XML stored in RichText fields. '
            . 'HTML must be well-formed XML: close every tag and write void elements as "<br/>"; '
            . 'HTML named entities such as "&nbsp;" are allowed. A complete DocBook or XHTML5 edit document '
            . 'is converted as-is. Returns an empty RichText document when no input is given. '
            . 'Check the result with validate_richtext_xml.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function convertToRichtextXml(?string $html = null): RichTextXml
    {
        if ($html === null || trim($html) === '') {
            return new RichTextXml(Value::EMPTY_VALUE);
        }

        $input = $this->wrapInputDocument($html);
        try {
            $document = $this->inputHandler->fromString($input);
        } catch (InvalidXmlException $e) {
            throw new ToolCallException(
                sprintf(
                    'Cannot convert the given input to RichText XML, it is not well-formed XML: %s',
                    implode('; ', $this->getXmlErrorMessages($e)),
                ),
                previous: $e,
            );
        } catch (NotFoundException $e) {
            throw new ToolCallException(
                sprintf('Cannot convert the given input to RichText XML: %s', $this->getUnsupportedNamespaceMessage($input)),
                previous: $e,
            );
        } catch (InvalidArgumentException $e) {
            throw new ToolCallException(
                sprintf('Cannot convert the given input to RichText XML: %s', $e->getMessage()),
                previous: $e,
            );
        }

        $xml = $document->saveXML();

        return new RichTextXml($xml === false ? Value::EMPTY_VALUE : $xml);
    }

    /**
     * @param string $xml RichText document to validate: DocBook XML, or XHTML5 edit XML which is converted to DocBook first.
     */
    #[McpTool(
        name: 'validate_richtext_xml',
        title: 'Validate RichText XML',
        description: 'Validates a RichText document the way saving a RichText field does: DocBook XML, or XHTML5 '
            . 'edit XML which is converted first. Checks the RichText schema and that internal links '
            . '(ezcontent://, ezlocation://, ezremote://) point to existing content. Returns the list of '
            . 'validation errors, empty when the document is valid.',
        annotations: new ToolAnnotations(
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function validateRichTextXml(string $xml): RichTextValidationResult
    {
        if (trim($xml) === '') {
            return new RichTextValidationResult(false, ['The XML document is empty.']);
        }

        try {
            $document = $this->inputHandler->fromString($xml);
        } catch (InvalidXmlException $e) {
            return new RichTextValidationResult(false, $this->getXmlErrorMessages($e));
        } catch (NotFoundException) {
            return new RichTextValidationResult(false, [$this->getUnsupportedNamespaceMessage($xml)]);
        } catch (InvalidArgumentException $e) {
            return new RichTextValidationResult(false, [$e->getMessage()]);
        }

        $errors = array_values($this->inputHandler->validate($document));

        return new RichTextValidationResult($errors === [], $errors);
    }

    /**
     * Line numbers are left out: the input handler prepends the HTML character entity DTD to XHTML5 edit
     * documents before parsing, so libxml reports lines that do not exist in the given input.
     *
     * @return list<string>
     */
    private function getXmlErrorMessages(InvalidXmlException $exception): array
    {
        $messages = [];
        foreach ($exception->getErrors() as $error) {
            $message = trim((string)preg_replace('/ line \d+/', '', $error->message));
            if ($message !== '') {
                $messages[] = $message;
            }
        }

        return $messages !== [] ? $messages : ['Invalid XML: unable to parse the provided string.'];
    }

    private function getUnsupportedNamespaceMessage(string $xml): string
    {
        $document = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        $namespace = $loaded ? $document->documentElement?->namespaceURI : null;

        return sprintf(
            '%s, expected DocBook (%s) or XHTML5 edit (%s).',
            $namespace === null || $namespace === ''
                ? 'The root element has no namespace'
                : sprintf('Unsupported root element namespace "%s"', $namespace),
            self::DOCBOOK_NAMESPACE,
            self::XHTML5_EDIT_NAMESPACE,
        );
    }

    /**
     * The RichText input handler only accepts complete documents in either the DocBook or the
     * XHTML5 edit namespace. Wrap anything else so that plain text and HTML fragments, which is
     * what an LLM naturally produces, can be converted too.
     */
    private function wrapInputDocument(string $html): string
    {
        $input = trim($html);

        if (str_contains($input, self::DOCBOOK_NAMESPACE) || str_contains($input, self::XHTML5_EDIT_NAMESPACE)) {
            return $input;
        }

        if (!str_contains($input, '<')) {
            $input = '<p>' . htmlspecialchars($input, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</p>';
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><section xmlns="%s">%s</section>',
            self::XHTML5_EDIT_NAMESPACE,
            $input,
        );
    }
}
