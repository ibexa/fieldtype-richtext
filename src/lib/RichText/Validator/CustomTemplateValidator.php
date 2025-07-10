<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Validator;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Ibexa\Contracts\FieldTypeRichText\RichText\ValidatorInterface;

/**
 * Validator for Custom Tags and Styles input.
 *
 * The Validator checks if the given XML reflects proper Custom Tags or Styles configuration,
 * mostly the existence of a specific Custom Tag or Style and their required attributes.
 */
final class CustomTemplateValidator implements ValidatorInterface
{
    /**
     * Custom Tags global configuration (ibexa.richtext.custom_tags Semantic Config).
     *
     * @var array<string,array{template:string, is_inline:bool, icon:string, attributes:array<string, array{type: string, required: bool, default_value: mixed}>}>
     */
    private array $customTagsConfiguration;

    /**
     * Custom Styles global configuration (ibexa.richtext.custom_styles Semantic Config).
     *
     * @var array<string,array{template:string,inline:bool}>
     */
    private array $customStylesConfiguration;

    /**
     * CustomTemplateValidator constructor.
     *
     * @param array<string,array{template:string, is_inline:bool, icon:string, attributes:array<string, array{type: string, required: bool, default_value: mixed}>}> $customTagsConfiguration
     * @param array<string,array{template:string, inline:bool}> $customStylesConfiguration
     */
    public function __construct(
        array $customTagsConfiguration,
        array $customStylesConfiguration
    ) {
        $this->customTagsConfiguration = $customTagsConfiguration;
        $this->customStylesConfiguration = $customStylesConfiguration;
    }

    /**
     * Validate Custom Tags found in the document.
     *
     * @return string[] an array of error messages
     */
    public function validateDocument(DOMDocument $xmlDocument): array
    {
        $configuredTemplateNames = array_merge(array_keys($this->customTagsConfiguration), array_keys($this->customStylesConfiguration));
        $errors = [];

        $xpath = new DOMXPath($xmlDocument);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');

        $eztemplateElements = $xpath->query('//docbook:eztemplate');
        if ($eztemplateElements instanceof DOMNodeList) {
            foreach ($eztemplateElements as $tagElement) {
                if (!$tagElement instanceof DOMElement) {
                    continue;
                }
                $tagName = $tagElement->getAttribute('name');
                if (empty($tagName)) {
                    $errors[] = 'Missing RichText Custom Tag name';
                    continue;
                }

                if (!in_array($tagName, $configuredTemplateNames, true)) {
                    @trigger_error(
                        "Configuration for RichText Custom Tag or Custom Style '{$tagName}' not found. " .
                        'Custom Tags and Custom Style configuration is required since 7.1, its lack will result in validation error in 8.x',
                        E_USER_DEPRECATED
                    );
                    continue;
                }

                // Custom Styles does not have any attributes, so we can skip validation for them
                if (isset($this->customStylesConfiguration[$tagName])) {
                    continue;
                }

                $nonEmptyAttributes = [];
                $tagAttributes = $this->customTagsConfiguration[$tagName]['attributes'];

                // iterate over all attributes defined in XML document to check if their names match configuration
                $configElements = $xpath->query('./docbook:ezconfig/docbook:ezvalue', $tagElement);
                if ($configElements instanceof DOMNodeList) {
                    foreach ($configElements as $configElement) {
                        if (!$configElement instanceof DOMElement) {
                            continue;
                        }
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
                }

                // check if all required attributes are present
                foreach ($tagAttributes as $attributeName => $attributeSettings) {
                    if (empty($attributeSettings['required'])) {
                        continue;
                    }

                    if (!in_array($attributeName, $nonEmptyAttributes)) {
                        $errors[] = "The attribute '{$attributeName}' of RichText Custom Tag '{$tagName}' cannot be empty";
                    }
                }
            }
        }

        return $errors;
    }
}
