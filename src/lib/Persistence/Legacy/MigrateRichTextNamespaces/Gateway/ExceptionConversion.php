<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\Gateway;

use Doctrine\DBAL\Exception;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;
use PDOException;

/**
 * @interal
 */
final class ExceptionConversion implements GatewayInterface
{
    private GatewayInterface $gateway;

    public function __construct(GatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function replaceDataTextAttributeValues(array $values): int
    {
        try {
            return $this->gateway->replaceDataTextAttributeValues($values);
        } catch (Exception | PDOException $exception) {
            throw DatabaseException::wrap($exception);
        }
    }
}
