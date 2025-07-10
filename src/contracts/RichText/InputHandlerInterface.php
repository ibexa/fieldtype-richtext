<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

use DOMDocument;

interface InputHandlerInterface
{
    /**
     * Converts a given XML String to the internal Rich Text representation.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException
     */
    public function fromString(?string $inputValue = null): DOMDocument;

    /**
     * Converts a given DOMDocument to the internal Rich Text representation.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromDocument(DOMDocument $inputValue): DOMDocument;

    /**
     * Returns relation data extracted from given $document (internal representation).
     *
     * @return array<int, array{locationIds: array<int, int>, contentIds: array<int, int>}>
     */
    public function getRelations(DOMDocument $document): array;

    /**
     * Validate the given $document (internal representation) and returns list of errors.
     *
     * @return array<int, string>
     */
    public function validate(DOMDocument $document): array;
}
