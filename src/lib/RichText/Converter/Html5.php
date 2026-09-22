<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\FieldTypeRichText\RichText\Converter\Xslt as XsltConverter;
use XSLTProcessor;

/**
 * Adds ConfigResolver awareness to the Xslt converter.
 */
class Html5 extends XsltConverter
{
    private ConfigResolverInterface $configResolver;

    private bool $customStylesheetsResolved = false;

    public function __construct(string $stylesheet, ConfigResolverInterface $configResolver)
    {
        parent::__construct($stylesheet);
        $this->configResolver = $configResolver;
    }

    protected function getXSLTProcessor(): XSLTProcessor
    {
        $this->resolveCustomStylesheets();

        return parent::getXSLTProcessor();
    }

    /**
     * Resolves the custom stylesheets lazily, since ConfigResolver parameters must not be read
     * in the constructor (the SiteAccess scope may not be final yet at that point).
     */
    private function resolveCustomStylesheets(): void
    {
        if ($this->customStylesheetsResolved) {
            return;
        }

        $customStylesheets = $this->configResolver->getParameter('fieldtypes.ibexa_richtext.output_custom_xsl');
        $this->addCustomStylesheets($customStylesheets ?: []);
        $this->customStylesheetsResolved = true;
    }
}
