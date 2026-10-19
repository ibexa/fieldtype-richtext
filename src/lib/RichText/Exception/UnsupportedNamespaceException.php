<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Throwable;

/**
 * Thrown when no validator is mapped to the namespace of the root element of a RichText document.
 *
 * Extends {@see NotFoundException} (no validator found), which was thrown before, so existing catch blocks keep
 * working; only the message tells what is wrong with the document and which namespaces are supported.
 */
final class UnsupportedNamespaceException extends NotFoundException
{
    /**
     * @param list<string> $supportedNamespaces
     */
    public function __construct(
        string $rootElementName,
        ?string $namespace,
        array $supportedNamespaces,
        ?Throwable $previous = null
    ) {
        parent::__construct('Validator', $namespace, $previous);

        if ($namespace === null) {
            $this->setMessageTemplate('The root element "%element%" has no namespace. Supported namespaces: %namespaces%.');
        } else {
            $this->setMessageTemplate('The root element "%element%" has an unsupported namespace "%namespace%". Supported namespaces: %namespaces%.');
        }
        $this->setParameters([
            '%element%' => $rootElementName,
            '%namespace%' => $namespace ?? '',
            '%namespaces%' => '"' . implode('", "', $supportedNamespaces) . '"',
        ]);
        $this->message = $this->getBaseTranslation();
    }
}
