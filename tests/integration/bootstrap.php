<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Contracts\Test\Core\Bootstrapper\Bootstrapper;
use Ibexa\Contracts\Test\Core\Bootstrapper\SchemaInstallPath;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Selects which of the two coexisting schema install paths this run exercises: the
// SchemaBuilderEvent one by default, or the Doctrine Migrations one with
// IBEXA_TEST_SCHEMA_BUILDER_EVENT_ENABLED=0. The base fixture replaces the content ibexa/core's
// ImportDataMigration inserts, so the tests see the same data either way.
$doctrineMigrationsEnabled = getenv('IBEXA_TEST_SCHEMA_BUILDER_EVENT_ENABLED') === '0';

(new Bootstrapper())->bootstrap(null, SchemaInstallPath::getBootstrapperOptions($doctrineMigrationsEnabled));
