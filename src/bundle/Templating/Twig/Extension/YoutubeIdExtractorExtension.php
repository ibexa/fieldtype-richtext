<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helper for extract video id from youtube url.
 */
final class YoutubeIdExtractorExtension extends AbstractExtension
{
    private const YOUTUBE_ID_REGEX = '/(?:https?:)?(?:\/\/)?(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube(?:-nocookie)?\.com\S*?[^\w\s-])'
    . '(?P<id>[\w-]{11})(?=[^\w-]|$)(?![?=&+%\w.-]*(?:[\'"][^<>]*>|<\/a>))[?=&+%\w.-]*/i';

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ibexa_richtext_youtube_extract_id',
                [$this, 'extractId']
            ),
            new TwigFunction(
                'ez_richtext_youtube_extract_id',
                [$this, 'extractId'],
                [
                    'deprecated' => '4.0',
                    'alternative' => 'ibexa_richtext_youtube_extract_id',
                ]
            ),
        ];
    }

    /**
     * Returns youtube video id.
     *
     * @return string|null
     */
    public function extractId(string $string): ?string
    {
        preg_match(self::YOUTUBE_ID_REGEX, $string, $matches);

        return $matches['id'] ?? null;
    }
}

class_alias(YoutubeIdExtractorExtension::class, 'EzSystems\EzPlatformRichTextBundle\Templating\Twig\Extension\YoutubeIdExtractorExtension');
