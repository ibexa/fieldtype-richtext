<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

/*
 * Regenerates pruned/docbook.rng and pruned/ezpublish.rng, see DocbookSchemaPruner.
 *
 * Development tool for this repository, run after changing docbook.rng or ezpublish.rng.
 *
 * Usage: php prune-rng.php [target_dir]
 */

use Ibexa\FieldTypeRichText\RichText\Schema\DocbookSchemaPruner;

require_once __DIR__ . '/../../../../../../vendor/autoload.php';

$targetDir = $argv[1] ?? __DIR__ . '/pruned';
if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
    fwrite(STDERR, "Cannot create directory '{$targetDir}'\n");
    exit(1);
}

try {
    $schemas = (new DocbookSchemaPruner())->prune(__DIR__ . '/docbook.rng', __DIR__ . '/ezpublish.rng');
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

foreach ($schemas as $file => $document) {
    if ($document->save($targetDir . '/' . $file) === false) {
        fwrite(STDERR, "Cannot write '{$targetDir}/{$file}'\n");
        exit(1);
    }
}
