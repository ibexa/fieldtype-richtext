<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper;

/**
 * Contracts for mapping Semantic configuration to settings exposed to templates.
 *
 * @internal For internal use for RichText package
 */
interface OnlineEditorConfigMapper
{
    /**
     * Map Online Editor custom CSS classes configuration.
     *
     * @param array<string, array<string, mixed>> $semanticConfiguration
     *
     * @return array<string, array<string, mixed>>
     */
    public function mapCssClassesConfiguration(array $semanticConfiguration): array;

    /**
     * Map Online Editor custom data attributes classes configuration.
     *
     * @param array<string, array<string, mixed>> $semanticConfiguration
     *
     * @return array<string, array<string, mixed>>
     */
    public function mapDataAttributesConfiguration(array $semanticConfiguration): array;
}
