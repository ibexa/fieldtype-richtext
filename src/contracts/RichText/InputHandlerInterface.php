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
     * Converts given XML String to the internal Rich Text representation.
     *
     * @param string|null $inputValue
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\FieldTypeRichText\RichText\Exception\InvalidXmlException
     *
     * @return \DOMDocument
     */
    public function fromString(?string $inputValue = null): DOMDocument;

    /**
     * Converts given DOMDocument to the internal Rich Text representation.
     *
     * @param \DOMDocument $inputValue
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return \DOMDocument
     */
    public function fromDocument(DOMDocument $inputValue): DOMDocument;

    /**
     * Returns relation data extracted from given $document (internal representation).
     *
     * @param \DOMDocument $document
     *
     * @return array
     */
    public function getRelations(DOMDocument $document): array;

    /**
     * Validate the given $document (internal representation) and returns list of errors.
     *
     * @param \DOMDocument $document
     *
     * @return array
     */
    public function validate(DOMDocument $document): array;
}

class_alias(InputHandlerInterface::class, 'EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface');
