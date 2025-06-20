<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;

/**
 * Validator for Custom Tags input.
 *
 * The Validator checks if the given XML reflects proper Custom Tags configuration,
 * mostly existence of specific Custom Tag and its required attributes.
 */
class CustomTagsValidator implements ValidatorInterface
{
    /**
     * Custom Tags global configuration (ibexa.richtext.custom_tags Semantic Config).
     */
    private array $customTagsConfiguration;

    /**
     * @param array $customTagsConfiguration Injectable using "%ibexa.field_type.richtext.custom_tags%" DI Container parameter.
     */
    public function __construct(array $customTagsConfiguration)
    {
        $this->customTagsConfiguration = $customTagsConfiguration;
    }

    /**
     * Validate Custom Tags found in the document.
     *
     * @param \DOMDocument $xmlDocument
     *
     * @return string[] an array of error messages
     */
    public function validateDocument(DOMDocument $xmlDocument): array
    {
        $errors = [];

        $xpath = new DOMXPath($xmlDocument);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        foreach ($xpath->query('//docbook:eztemplate') as $tagElement) {
            $tagName = $tagElement->getAttribute('name');
            if (empty($tagName)) {
                $errors[] = 'Missing RichText Custom Tag name';
                continue;
            }

            if (!isset($this->customTagsConfiguration[$tagName])) {
                $errors[] = "Missing configuration for RichText CustomTag: '$tagName'";
                continue;
            }

            $nonEmptyAttributes = [];
            $tagAttributes = $this->customTagsConfiguration[$tagName]['attributes'];

            // iterate over all attributes defined in XML document to check if their names match configuration
            $configElements = $xpath->query('./docbook:ezconfig/docbook:ezvalue', $tagElement);
            foreach ($configElements as $configElement) {
                $attributeName = $configElement->getAttribute('key');
                if (empty($attributeName)) {
                    $errors[] = "Missing attribute name for RichText Custom Tag '{$tagName}'";
                    continue;
                }
                if (!isset($tagAttributes[$attributeName])) {
                    $errors[] = "Unknown attribute '{$attributeName}' of RichText Custom Tag '{$tagName}'";
                }

                // collect information about non-empty attributes
                if (!empty($configElement->textContent)) {
                    $nonEmptyAttributes[] = $attributeName;
                }
            }

            // check if all required attributes are present
            foreach ($tagAttributes as $attributeName => $attributeSettings) {
                if (empty($attributeSettings['required'])) {
                    continue;
                }

                if (!in_array($attributeName, $nonEmptyAttributes, true)) {
                    $errors[] = "The attribute '{$attributeName}' of RichText Custom Tag '{$tagName}' cannot be empty";
                }
            }
        }

        return $errors;
    }
}
