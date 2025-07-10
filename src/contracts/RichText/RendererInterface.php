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
    public function renderTemplate(
        string $name,
        string $type,
        array $parameters,
        bool $isInline,
    ): string|null;

    public function renderContentEmbed(
        int|string $contentId,
        string $viewType,
        array $parameters,
        bool $isInline,
    ): string|null;

    public function renderLocationEmbed(
        int|string $locationId,
        string $viewType,
        array $parameters,
        bool $isInline,
    ): string|null;
}
