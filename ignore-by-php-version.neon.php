<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

$includes = [];
if (PHP_VERSION_ID < 8_00_00) {
    $includes[] = __DIR__ . '/ignore-lte-php7.4-errors.neon';
} else  {
    $includes[] = __DIR__ . '/ignore-gte-php8.1-errors.neon';
}

$config = [];
$config['includes'] = $includes;

return $config;
