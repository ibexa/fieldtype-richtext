<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration\Provider;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\FieldTypeRichText\Configuration\Provider\CustomTag;

class CustomTagProviderTest extends BaseCustomTemplateProviderTestCase
{
    public function createProvider(): Provider
    {
        return new CustomTag($this->configResolver, $this->mapper);
    }

    public function getExpectedProviderName(): string
    {
        return 'customTags';
    }

    protected function getExpectedCustomTemplatesConfiguration(): array
    {
        return ['tag' => ['template' => 'tag.html.twig', 'attributes' => []]];
    }

    protected function getCustomTemplateSiteAccessConfigParamName(): string
    {
        return 'fieldtypes.ezrichtext.custom_tags';
    }
}
