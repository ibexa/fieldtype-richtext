<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper;

use RuntimeException;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RichText Custom Style configuration mapper.
 *
 * @internal For internal use by RichText package
 */
final class CustomStyle implements CustomTemplateConfigMapper
{
    private array $customStylesConfiguration;

    private TranslatorInterface $translator;

    private Packages $packages;

    private string $translationDomain;

    public function __construct(
        array $customStylesConfiguration,
        TranslatorInterface $translator,
        string $translationDomain,
        Packages $packages
    ) {
        $this->customStylesConfiguration = $customStylesConfiguration;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
        $this->packages = $packages;
    }

    /**
     * Map Configuration for the given list of enabled Custom Styles.
     *
     * @param array $enabledCustomStyles
     *
     * @return array Mapped configuration
     */
    public function mapConfig(array $enabledCustomStyles): array
    {
        $config = [];
        foreach ($enabledCustomStyles as $styleName) {
            if (!isset($this->customStylesConfiguration[$styleName])) {
                throw new RuntimeException(
                    "Could not find RichText Custom Style configuration for {$styleName}."
                );
            }

            $customStyleConfiguration = $this->customStylesConfiguration[$styleName];
            $config[$styleName]['inline'] = $customStyleConfiguration['inline'];
            $config[$styleName]['label'] = $this->translator->trans(
                /** @Ignore */
                sprintf('ezrichtext.custom_styles.%s.label', $styleName),
                [],
                $this->translationDomain
            );
        }

        return $config;
    }
}
