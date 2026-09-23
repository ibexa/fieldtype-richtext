<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText\Converter;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

/**
 * Adds ConfigResolver awareness to the Xslt converter.
 */
class Html5 extends ConfigResolverAwareXsltConverter
{
    public function __construct(string $stylesheet, ConfigResolverInterface $configResolver)
    {
        parent::__construct($stylesheet, $configResolver, 'fieldtypes.ibexa_richtext.output_custom_xsl');
    }
}
