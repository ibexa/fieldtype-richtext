<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\FieldTypeRichText\RichText;

/**
 * RichText field type renderer interface, to be implemented in MVC layer.
 */
interface RendererInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function renderTemplate(
        string $name,
        string $type,
        array $parameters,
        bool $isInline,
    ): string|null;

    /**
     * @param array<mixed> $parameters
     */
    public function renderContentEmbed(
        int $contentId,
        string $viewType,
        array $parameters,
        bool $isInline,
    ): string|null;

    /**
     * @param array<mixed> $parameters
     */
    public function renderLocationEmbed(
        int $locationId,
        string $viewType,
        array $parameters,
        bool $isInline,
    ): string|null;
}
