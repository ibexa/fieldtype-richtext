<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Form\Type;

use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form Type representing ezrichtext field type.
 */
class RichTextFieldType extends AbstractType
{
    /** @var \Ibexa\Contracts\Core\Repository\FieldTypeService */
    protected $fieldTypeService;

    /** @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter */
    protected $docbookToXhtml5EditConverter;

    public function __construct(FieldTypeService $fieldTypeService, Converter $docbookToXhtml5EditConverter)
    {
        $this->fieldTypeService = $fieldTypeService;
        $this->docbookToXhtml5EditConverter = $docbookToXhtml5EditConverter;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'ezplatform_fieldtype_ezrichtext';
    }

    public function getParent(): ?string
    {
        return TextareaType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new RichTextValueTransformer(
            $this->fieldTypeService->getFieldType('ezrichtext'),
            $this->docbookToXhtml5EditConverter
        ));
    }
}
