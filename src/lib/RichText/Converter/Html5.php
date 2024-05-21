<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\FieldTypeRichText\RichText\Converter\Xslt as XsltConverter;

/**
 * Adds ConfigResolver awareness to the Xslt converter.
 */
class Html5 extends XsltConverter
{
    public function __construct($stylesheet, ConfigResolverInterface $configResolver)
    {
        $customStylesheets = $configResolver->getParameter('fieldtypes.ezrichtext.output_custom_xsl');
        $customStylesheets = $customStylesheets ?: [];
        parent::__construct($stylesheet, $customStylesheets);
    }
}
