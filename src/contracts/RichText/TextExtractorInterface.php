<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

use DOMDocument;
use DOMNode;

interface TextExtractorInterface
{
    /**
     * Extracts text content of the given $node.
     */
    public function extractText(DOMNode $node): string;

    /**
     * Extracts short text content of the given $document.
     *
     * @internal Only for use by RichText FieldType itself.
     */
    public function extractShortText(DOMDocument $document): string;
}
