<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Mcp\Tool\Output;

/**
 * RichText document as returned by the `convert_to_richtext_xml` tool.
 *
 * @experimental
 */
final readonly class RichTextXml
{
    /**
     * @param string $xml RichText DocBook XML document, usable as the "xml" key of a RichText field value.
     */
    public function __construct(
        public string $xml,
    ) {
    }
}
