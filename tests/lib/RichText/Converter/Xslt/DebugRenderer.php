<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText\Converter\Xslt;

use Ibexa\Contracts\FieldTypeRichText\RichText\RendererInterface;

final class DebugRenderer implements RendererInterface
{
    private const string TEMPLATE_FORMAT = '<template-output name="%s" type="%s" is-inline="%s">%s</template-output>';
    private const string EMBED_CONTENT_FORMAT = '<embed-content-output content-id="%d" view-type="%s" is-inline="%s">%s</embed-content-output>';
    private const string EMBED_LOCATION_FORMAT = '<embed-location-output location-id="%d" view-type="%s" is-inline="%s">%s</embed-location-output>';

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderTag(string $name, array $parameters, bool $isInline): string
    {
        return $this->renderTemplate($name, 'tag', $parameters, $isInline);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderTemplate(string $name, string $type, array $parameters, bool $isInline): string
    {
        return sprintf(
            self::TEMPLATE_FORMAT,
            $name,
            $type,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderContentEmbed(int|string $contentId, string $viewType, array $parameters, bool $isInline): string
    {
        return sprintf(
            self::EMBED_CONTENT_FORMAT,
            $contentId,
            $viewType,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderLocationEmbed(string|int $locationId, string $viewType, array $parameters, bool $isInline): string
    {
        return sprintf(
            self::EMBED_LOCATION_FORMAT,
            $locationId,
            $viewType,
            $this->serializeIsInline($isInline),
            $this->serializeParameters($parameters)
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function serializeParameters(array $parameters): string
    {
        $lines = [];

        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $lines[] = sprintf('<param name="%s">', $name);
                    $lines[] = $this->serializeParameters($value);
                    $lines[] = sprintf('</param>');
                }
            } else {
                $lines[] = sprintf('<param name="%s">%s</param>', $name, $value);
            }
        }

        return implode('', $lines);
    }

    private function serializeIsInline(bool $isInline): string
    {
        return $isInline ? 'true' : 'false';
    }
}
