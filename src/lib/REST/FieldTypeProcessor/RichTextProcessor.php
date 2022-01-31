<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\REST\FieldTypeProcessor;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Ibexa\Contracts\Rest\FieldTypeProcessor;

class RichTextProcessor extends FieldTypeProcessor
{
    /**
     * @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter
     */
    protected $docbookToXhtml5EditConverter;

    public function __construct(Converter $docbookToXhtml5EditConverter)
    {
        $this->docbookToXhtml5EditConverter = $docbookToXhtml5EditConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function postProcessValueHash($outgoingValueHash)
    {
        $document = new DOMDocument();
        $document->loadXML($outgoingValueHash['xml']);

        $outgoingValueHash['xhtml5edit'] = $this->docbookToXhtml5EditConverter
            ->convert($document)
            ->saveXML();

        return $outgoingValueHash;
    }
}

class_alias(RichTextProcessor::class, 'EzSystems\EzPlatformRichText\eZ\REST\FieldTypeProcessor\RichTextProcessor');
