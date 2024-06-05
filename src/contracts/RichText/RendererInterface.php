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
     * Renders template.
     *
     * @param string $name
     * @param string $type
     * @param array $parameters
     * @param bool $isInline
     *
     * @return string|null
     */
    public function renderTemplate($name, $type, array $parameters, $isInline);

    /**
     * Renders Content embed.
     *
     * @param int|string $contentId
     * @param string $viewType
     * @param array $parameters
     * @param bool $isInline
     *
     * @return string|null
     */
    public function renderContentEmbed($contentId, $viewType, array $parameters, $isInline);

    /**
     * Renders Location embed.
     *
     * @param int|string $locationId
     * @param string $viewType
     * @param array $parameters
     * @param bool $isInline
     *
     * @return string|null
     */
    public function renderLocationEmbed($locationId, $viewType, array $parameters, $isInline);
}
