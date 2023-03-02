<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle;
use Ibexa\Contracts\Core\Test\IbexaTestKernel as BaseIbexaTestKernel;
use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;

final class IbexaTestKernel extends BaseIbexaTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaFieldTypeRichTextBundle();
        yield new DAMADoctrineTestBundle();
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield from parent::getExposedServicesByClass();

        yield GatewayInterface::class;
    }
}
