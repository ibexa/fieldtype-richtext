<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Configuration\UI\Mapper;

/**
 * @internal For internal use by RichText package
 */
interface CustomTemplateConfigMapper
{
    public function mapConfig(array $enabledCustomTemplates): array;
}
