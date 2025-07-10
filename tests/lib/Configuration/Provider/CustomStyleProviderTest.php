<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\FieldTypeRichText\Configuration\Provider\CustomStyle;

class CustomStyleProviderTest extends BaseCustomTemplateProviderTestCase
{
    public function createProvider(): Provider
    {
        return new CustomStyle($this->configResolver, $this->mapper);
    }

    public function getExpectedProviderName(): string
    {
        return 'customStyles';
    }

    /**
     * @return array{paragraph: array{0: string}}
     */
    protected function getExpectedCustomTemplatesConfiguration(): array
    {
        return ['paragraph' => ['style1']];
    }

    protected function getCustomTemplateSiteAccessConfigParamName(): string
    {
        return 'fieldtypes.ibexa_richtext.custom_styles';
    }
}
