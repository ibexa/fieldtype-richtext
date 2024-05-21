<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension;

use DOMDocument;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter as RichTextConverterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RichTextConverterExtension extends AbstractExtension
{
    /** @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter */
    private $richTextOutputConverter;

    /** @var \Ibexa\Contracts\FieldTypeRichText\RichText\Converter */
    private $richTextEditConverter;

    public function __construct(
        RichTextConverterInterface $richTextOutputConverter,
        RichTextConverterInterface $richTextEditConverter
    ) {
        $this->richTextOutputConverter = $richTextOutputConverter;
        $this->richTextEditConverter = $richTextEditConverter;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'ez_richtext_to_html5',
                [$this, 'richTextToHtml5'],
                [
                    'is_safe' => ['html'],
                    'deprecated' => '4.0',
                    'alternative' => 'ibexa_richtext_to_html5',
                ]
            ),
            new TwigFilter(
                'ez_richtext_to_html5_edit',
                [$this, 'richTextToHtml5Edit'],
                [
                    'is_safe' => ['html'],
                    'deprecated' => '4.0',
                    'alternative' => 'ibexa_richtext_to_html5_edit',
                ]
            ),
            new TwigFilter(
                'ibexa_richtext_to_html5',
                [$this, 'richTextToHtml5'],
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'ibexa_richtext_to_html5_edit',
                [$this, 'richTextToHtml5Edit'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function richTextToHtml5(DOMDocument $xmlData): string
    {
        return $this->richTextOutputConverter->convert($xmlData)->saveHTML() ?: '';
    }

    public function richTextToHtml5Edit(DOMDocument $xmlData): string
    {
        return $this->richTextEditConverter->convert($xmlData)->saveHTML() ?: '';
    }
}
