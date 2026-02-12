<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class RichTextDocbookSchemaPass implements CompilerPassInterface
{
    public const string SCHEMA_FRAGMENTS_PARAM = 'ibexa.field_type.richtext.docbook.schema_fragments';
    private const string VALIDATOR_RESOURCES_PARAM = 'ibexa.field_type.richtext.validator.docbook.resources';
    private const string BASE_SCHEMA = 'ezpublish.rng';

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string> $fragments */
        $fragments = $container->hasParameter(self::SCHEMA_FRAGMENTS_PARAM)
            ? (array)$container->getParameter(self::SCHEMA_FRAGMENTS_PARAM)
            : [];

        if (empty($fragments)) {
            return;
        }

        /** @var string $cacheDir */
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $combinedSchemaPath = $cacheDir . '/richtext/docbook_combined.rng';

        // Generate combined schema
        $this->generateCombinedSchema($container, $fragments, $combinedSchemaPath);

        // Replace base schema with combined in validator resources
        /** @var array<string> $resources */
        $resources = $container->getParameter(self::VALIDATOR_RESOURCES_PARAM);
        $resources = array_map(
            static fn (string $path): string => str_contains($path, self::BASE_SCHEMA) ? $combinedSchemaPath : $path,
            $resources
        );
        $container->setParameter(self::VALIDATOR_RESOURCES_PARAM, $resources);
    }

    /**
     * @param array<string> $fragments
     */
    private function generateCombinedSchema(
        ContainerBuilder $container,
        array $fragments,
        string $outputPath
    ): void {
        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');
        $baseSchemaPath = $projectDir . '/vendor/ibexa/fieldtype-richtext/src/bundle/Resources/richtext/schemas/docbook/ezpublish.rng';

        $includeLines = [
            '<include href="' . $baseSchemaPath . '">',
            '  <!-- Override extension points to make them combinable if they are not already -->',
            '  <define name="ez.extension.blocks" combine="choice">',
            '    <notAllowed/>',
            '  </define>',
            '  <define name="ez.extension.inlines" combine="choice">',
            '    <notAllowed/>',
            '  </define>',
            '</include>',
        ];

        foreach ($fragments as $fragment) {
            $resolvedPath = str_replace('%kernel.project_dir%', $projectDir, $fragment);
            $includeLines[] = '<include href="' . $resolvedPath . '"/>';
        }

        $includesXml = implode("\n  ", $includeLines);

        $schema = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <grammar xmlns="http://relaxng.org/ns/structure/1.0"
                     xmlns:db="http://docbook.org/ns/docbook"
                     xmlns:ez="http://ibexa.co/xmlns/ezpublish/docbook"
                     xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml"
                     xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom"
                     xmlns:xlink="http://www.w3.org/1999/xlink"
                     xmlns:a="http://ibexa.co/xmlns/annotation"
                     xmlns:m="http://ibexa.co/xmlns/module"
                     ns="http://docbook.org/ns/docbook"
                     datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">

              $includesXml

            </grammar>
            XML;

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        file_put_contents($outputPath, $schema);
    }
}