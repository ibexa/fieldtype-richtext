<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\FieldType\RichText;

use DOMDocument;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\Contracts\FieldTypeRichText\RichText\TextExtractorInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use RuntimeException;

/**
 * RichText field type.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    private InputHandlerInterface $inputHandler;

    private TextExtractorInterface $textExtractor;

    public function __construct(
        InputHandlerInterface $inputHandler,
        TextExtractorInterface $textExtractor
    ) {
        $this->inputHandler = $inputHandler;
        $this->textExtractor = $textExtractor;
    }

    /**
     * Returns the field type identifier for this field type.
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_richtext';
    }

    /**
     * Returns the name of the given field value.
     *
     * It will be used to generate content name and url alias if current field is designated
     * to be used in the content name/urlAlias pattern.
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        $result = null;
        if ($section = $value->xml->documentElement->firstChild) {
            $textDom = $section->firstChild;

            if ($textDom && $textDom->hasChildNodes()) {
                $result = $textDom->firstChild->textContent;
            } elseif ($textDom) {
                $result = $textDom->textContent;
            }
        }

        if ($result === null) {
            $result = $value->xml->documentElement->textContent;
        }

        $result = preg_replace(['/\n/', '/\s\s+/'], ' ', (string)$result);

        return trim((string)$result);
    }

    /**
     * Returns the fallback default value of a field type when no such default
     * value is provided in the field definition in content types.
     */
    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * @param Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        if ($value->xml === null) {
            return true;
        }

        return !$value->xml->documentElement->hasChildNodes();
    }

    /**
     * Inspects a given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param Value|\DOMDocument|string $inputValue
     *
     * @return Value the potentially converted and structurally plausible value
     */
    protected function createValueFromInput(mixed $inputValue): mixed
    {
        if (is_string($inputValue)) {
            $inputValue = $this->inputHandler->fromString($inputValue);
        }

        if ($inputValue instanceof DOMDocument) {
            $inputValue = new Value($this->inputHandler->fromDocument($inputValue));
        }

        return $inputValue;
    }

    /**
     * Throws an exception if a value structure is not of an expected format.
     *
     * @param Value $value
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the value does not match the expected structure
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!$value->xml instanceof DOMDocument) {
            throw new InvalidArgumentType(
                '$value->xml',
                'DOMDocument',
                $value
            );
        }
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * This is a base implementation, returning an empty array() that indicates
     * that no validation errors occurred. Overwrite in derived types, if
     * validation is supported.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $value): array
    {
        return array_map(static function (string $error): ValidationError {
            return new ValidationError("Validation of XML content failed:\n" . $error, null, [], 'xml');
        }, $this->inputHandler->validate($value->xml));
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param Value $value
     *
     * @see \Ibexa\Core\FieldType
     */
    protected function getSortInfo(BaseValue $value): string
    {
        return $this->textExtractor->extractText($value->xml);
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     * $hash accepts the following keys:
     *  - xml (XML string which complies internal format).
     *
     * @return Value
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromHash(mixed $hash): SPIValue
    {
        if (!isset($hash['xml'])) {
            throw new RuntimeException("'xml' index is missing in hash.");
        }

        return $this->acceptValue($hash['xml']);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param Value $value
     *
     * @return array{xml: string}
     */
    public function toHash(SPIValue $value): array
    {
        return ['xml' => (string)$value];
    }

    /**
     * Creates a new Value object from persistence data.
     * $fieldValue->data is supposed to be a string.
     */
    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        return new Value($fieldValue->data);
    }

    /**
     * @param Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        return new FieldValue(
            [
                'data' => $value->xml->saveXML(),
                'externalData' => null,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Returns whether the field type is searchable.
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Returns relation data extracted from value.
     *
     * Not intended for \Ibexa\Contracts\Core\Repository\Values\Content\Relation::COMMON type relations,
     * there is a service API for handling those.
     *
     * @return array<int, array{locationIds: array<int, int>, contentIds: array<int, int>}> hash with relation type as key and array of destination content ids as value.
     *
     * Example:
     * <code>
     *  array(
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::LINK => array(
     *          "contentIds" => array( 12, 13, 14 ),
     *          "locationIds" => array( 24 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::EMBED => array(
     *          "contentIds" => array( 12 ),
     *          "locationIds" => array( 24, 45 )
     *      ),
     *      \Ibexa\Contracts\Core\Repository\Values\Content\Relation::FIELD => array( 12 )
     *  )
     * </code>
     */
    public function getRelations(SPIValue $fieldValue): array
    {
        $relations = [];

        /** @var Value $fieldValue */
        if ($fieldValue->xml instanceof DOMDocument) {
            $relations = $this->inputHandler->getRelations($fieldValue->xml);
        }

        return $relations;
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('ibexa_richtext.name', 'ibexa_fieldtypes'))->setDesc('Rich text'),
        ];
    }
}
