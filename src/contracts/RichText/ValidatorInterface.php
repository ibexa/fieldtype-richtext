<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

use DOMDocument;

interface ValidatorInterface
{
    /**
     * Validate the given $xmlDocument and returns list of errors.
     *
     * @return string[]
     */
    public function validateDocument(DOMDocument $xmlDocument): array;
}
