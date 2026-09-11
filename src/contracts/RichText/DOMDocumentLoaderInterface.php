<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

use DOMDocument;

/**
 * Loads already stored RichText XML documents. Never throws, libxml errors are logged instead.
 */
interface DOMDocumentLoaderInterface
{
    /**
     * @param array<string, mixed> $logContext
     */
    public function loadXML(string $xml, array $logContext = []): DOMDocument;
}
