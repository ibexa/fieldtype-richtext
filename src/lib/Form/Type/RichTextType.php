<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Form\Type;

use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Contracts\FieldTypeRichText\RichText\InputHandlerInterface;
use Ibexa\FieldTypeRichText\Form\DataTransformer\RichTextTransformer;
use Ibexa\FieldTypeRichText\RichText\DOMDocumentFactory;
use Ibexa\FieldTypeRichText\Validator\Constraints\RichText as ValidRichText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RichTextType extends AbstractType
{
    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface
     */
    private $inputHandler;

    /**
     * @var \EzSystems\EzPlatformRichText\eZ\RichText\Converter
     */
    private $docbookToXhtml5EditConverter;

    /**
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\DOMDocumentFactory $domDocumentFactory
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\InputHandlerInterface $inputHandler
     * @param \EzSystems\EzPlatformRichText\eZ\RichText\Converter $docbookToXhtml5EditConverter
     */
    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        InputHandlerInterface $inputHandler,
        Converter $docbookToXhtml5EditConverter
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->inputHandler = $inputHandler;
        $this->docbookToXhtml5EditConverter = $docbookToXhtml5EditConverter;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new RichTextTransformer(
            $this->domDocumentFactory,
            $this->inputHandler,
            $this->docbookToXhtml5EditConverter
        ));
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new ValidRichText(),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getParent(): string
    {
        return TextareaType::class;
    }

    /**
     * @inheritdoc
     */
    public function getBlockPrefix(): string
    {
        return 'richtext';
    }
}

class_alias(RichTextType::class, 'EzSystems\EzPlatformRichText\Form\Type\RichTextType');
