<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Normalizer;

use Ibexa\FieldTypeRichText\RichText\Normalizer;

/**
 * Character entity definition normalizer adds DTD containing character entity definition to
 * string input that conforms to an XML document with configured document element and default
 * namespace.
 *
 * Note: if input already contains DTD it won't be accepted for normalization.
 */
class DocumentTypeDefinition extends Normalizer
{
    /**
     * Holds a root element name of the accepted XML format.
     */
    private string $documentElement;

    /**
     * Holds a default namespace name of the accepted XML format.
     */
    private string $namespace;

    /**
     * Holds a path to the DTD file.
     */
    private string $dtdPath;

    /**
     * Holds computed a regular expression pattern for matching and replacement.
     */
    private ?string $expression = null;

    public function __construct(string $documentElement, string $namespace, string $dtdPath)
    {
        $this->documentElement = $documentElement;
        $this->namespace = $namespace;
        $this->dtdPath = $dtdPath;
    }

    /**
     * Accept if $input looks like XML document, with configured document element
     * and default namespace, but without DTD.
     */
    public function accept(string $input): bool
    {
        if (preg_match($this->getExpression(), $input, $matches)) {
            return true;
        }

        return false;
    }

    /**
     * Normalizes a given $input by adding DTD with character entity definition.
     */
    public function normalize(string $input): string
    {
        return (string)preg_replace(
            $this->getExpression(),
            "\${1}\n" . file_get_contents($this->dtdPath) . '${3}',
            $input,
        );
    }

    /**
     * Computes and returns a regular expression pattern for matching and replacement.
     */
    private function getExpression(): string
    {
        if ($this->expression === null) {
            $this->expression =
                '/(<\?xml.*\?>)?([ \t\n\r]*)(<' .
                preg_quote($this->documentElement, '/') .
                '.*xmlns="' .
                preg_quote($this->namespace, '/') .
                '".*>)/is';
        }

        return $this->expression;
    }
}
