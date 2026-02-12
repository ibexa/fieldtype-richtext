<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection\Compiler;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Compiler\RichTextDocbookSchemaPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RichTextDocbookSchemaPassTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/richtext_schema_pass_test_' . uniqid();
        mkdir($this->cacheDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->cacheDir);
    }

    public function testProcessWithNoFragmentsParameterDoesNothing(): void
    {
        $container = new ContainerBuilder();

        $pass = new RichTextDocbookSchemaPass();
        $pass->process($container);

        self::assertDirectoryDoesNotExist($this->cacheDir . '/richtext');
    }

    public function testProcessWithEmptyFragmentsDoesNothing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter(RichTextDocbookSchemaPass::SCHEMA_FRAGMENTS_PARAM, []);

        $pass = new RichTextDocbookSchemaPass();
        $pass->process($container);

        self::assertDirectoryDoesNotExist($this->cacheDir . '/richtext');
    }

    public function testProcessGeneratesCombinedSchemaAndUpdatesValidatorResources(): void
    {
        $projectDir = $this->cacheDir;
        $this->createBaseSchema($projectDir);

        $fragmentPath = $this->cacheDir . '/custom_fragment.rng';
        file_put_contents($fragmentPath, '<grammar/>');

        $baseSchemaPath = '/some/path/to/ezpublish.rng';
        $otherSchemaPath = '/some/other/schema.rng';

        $container = $this->createContainer($projectDir, [$fragmentPath], [
            $baseSchemaPath,
            $otherSchemaPath,
        ]);

        $pass = new RichTextDocbookSchemaPass();
        $pass->process($container);

        $combinedSchemaPath = $this->cacheDir . '/richtext/docbook_combined.rng';
        self::assertFileExists($combinedSchemaPath);

        $content = file_get_contents($combinedSchemaPath);
        $expectedBaseHref = $projectDir . '/vendor/ibexa/fieldtype-richtext/src/bundle/Resources/richtext/schemas/docbook/ezpublish.rng';
        self::assertStringContainsString('<include href="' . $expectedBaseHref . '">', $content);
        self::assertStringContainsString('<include href="' . $fragmentPath . '"/>', $content);
        self::assertStringContainsString('ez.extension.blocks', $content);
        self::assertStringContainsString('ez.extension.inlines', $content);

        /** @var array<string> $updatedResources */
        $updatedResources = $container->getParameter('ibexa.field_type.richtext.validator.docbook.resources');
        self::assertSame($combinedSchemaPath, $updatedResources[0]);
        self::assertSame($otherSchemaPath, $updatedResources[1]);
    }

    public function testGeneratedSchemaIsValidXml(): void
    {
        $projectDir = $this->cacheDir;
        $this->createBaseSchema($projectDir);

        $container = $this->createContainer($projectDir, ['/some/fragment.rng'], []);

        $pass = new RichTextDocbookSchemaPass();
        $pass->process($container);

        $combinedSchemaPath = $this->cacheDir . '/richtext/docbook_combined.rng';

        $xml = new \DOMDocument();
        $result = $xml->load($combinedSchemaPath);
        self::assertTrue($result, 'Generated schema should be valid XML');
    }

    public function testProcessResolvesProjectDirPlaceholderInFragments(): void
    {
        $projectDir = $this->cacheDir;
        $this->createBaseSchema($projectDir);

        $container = $this->createContainer(
            $projectDir,
            ['%kernel.project_dir%/custom/fragment.rng'],
            [],
        );

        $pass = new RichTextDocbookSchemaPass();
        $pass->process($container);

        $content = file_get_contents($this->cacheDir . '/richtext/docbook_combined.rng');
        self::assertStringContainsString(
            '<include href="' . $projectDir . '/custom/fragment.rng"/>',
            $content,
        );
        self::assertStringNotContainsString('%kernel.project_dir%', $content);
    }

    /**
     * @param array<string> $fragments
     * @param array<string> $validatorResources
     */
    private function createContainer(
        string $projectDir,
        array $fragments,
        array $validatorResources,
    ): ContainerBuilder {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', $this->cacheDir);
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter(RichTextDocbookSchemaPass::SCHEMA_FRAGMENTS_PARAM, $fragments);
        $container->setParameter('ibexa.field_type.richtext.validator.docbook.resources', $validatorResources);

        return $container;
    }

    private function createBaseSchema(string $projectDir): void
    {
        $baseSchemaDir = $projectDir . '/vendor/ibexa/fieldtype-richtext/src/bundle/Resources/richtext/schemas/docbook';
        mkdir($baseSchemaDir, 0700, true);
        file_put_contents($baseSchemaDir . '/ezpublish.rng', '<grammar/>');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}