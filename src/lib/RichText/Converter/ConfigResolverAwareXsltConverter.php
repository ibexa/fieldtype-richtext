<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use XSLTProcessor;

/**
 * Base class for Xslt converters that add custom stylesheets configured through the ConfigResolver.
 *
 * The ConfigResolver parameter is intentionally not read in the constructor, since the SiteAccess
 * scope may not be final yet at that point. It is resolved lazily, on first use of the XSLT processor.
 */
abstract class ConfigResolverAwareXsltConverter extends Xslt
{
    private ConfigResolverInterface $configResolver;

    private string $customStylesheetsParameterName;

    private bool $customStylesheetsResolved = false;

    public function __construct(
        string $stylesheet,
        ConfigResolverInterface $configResolver,
        string $customStylesheetsParameterName
    ) {
        parent::__construct($stylesheet);
        $this->configResolver = $configResolver;
        $this->customStylesheetsParameterName = $customStylesheetsParameterName;
    }

    protected function getXSLTProcessor(): XSLTProcessor
    {
        $this->resolveCustomStylesheets();

        return parent::getXSLTProcessor();
    }

    private function resolveCustomStylesheets(): void
    {
        if ($this->customStylesheetsResolved) {
            return;
        }

        $customStylesheets = $this->configResolver->getParameter($this->customStylesheetsParameterName);
        $this->addCustomStylesheets($customStylesheets ?: []);
        $this->customStylesheetsResolved = true;
    }
}
