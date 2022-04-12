<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag;

/**
 * Map RichText Custom Tag attribute of supported type to proper UI config.
 *
 * @internal For internal use by RichText package
 */
interface AttributeMapper
{
    /**
     * Check if mapper supports given Custom Tag attribute type.
     *
     * @param string $attributeType
     *
     * @return bool
     */
    public function supports(string $attributeType): bool;

    /**
     * Map Configuration for the given Custom Tag attribute type.
     *
     * @param string $tagName
     * @param string $attributeName
     * @param array $customTagAttributeProperties
     *
     * @return array Mapped attribute configuration
     */
    public function mapConfig(
        string $tagName,
        string $attributeName,
        array $customTagAttributeProperties
    ): array;
}

class_alias(AttributeMapper::class, 'EzSystems\EzPlatformRichText\Configuration\UI\Mapper\CustomTag\AttributeMapper');
