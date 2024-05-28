<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText;

final class IbexaTestKernelForTranslationTest extends IbexaTestKernel
{
    protected function skipOverridingCustomTagConfig(): bool
    {
        // Loading custom tag configuration for tests cause extraction test failure
        return true;
    }
}
