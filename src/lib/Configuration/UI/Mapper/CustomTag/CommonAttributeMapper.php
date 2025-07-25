<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper\CustomTag;

/**
 * Map RichText Custom Tag attribute of any type to proper UI config.
 *
 * @internal For internal use by RichText package
 */
class CommonAttributeMapper implements AttributeMapper
{
    public function supports(string $attributeType): bool
    {
        return true;
    }

    public function mapConfig(
        string $tagName,
        string $attributeName,
        array $customTagAttributeProperties
    ): array {
        return [
            'label' => "ibexa_richtext.custom_tags.{$tagName}.attributes.{$attributeName}.label",
            'type' => $customTagAttributeProperties['type'],
            'required' => $customTagAttributeProperties['required'],
            'defaultValue' => $customTagAttributeProperties['default_value'],
        ];
    }
}
