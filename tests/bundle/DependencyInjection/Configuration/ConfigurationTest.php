<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\DependencyInjection\Configuration;

use Ibexa\Bundle\FieldTypeRichText\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    private const string INPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/input/';
    private const string OUTPUT_FIXTURES_DIR = __DIR__ . '/../Fixtures/custom_tags/output/';

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * Custom tags configuration data provider for testProcessingConfiguration.
     *
     * Fetches configs from the filesystem.
     *
     * @return iterable<string, mixed>
     *
     * @see testProcessingConfiguration
     */
    public function providerForTestProcessingCustomTagsConfiguration(): iterable
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in(self::INPUT_FIXTURES_DIR)
            ->name('*.yaml')
            ->sortByName()
        ;

        foreach ($finder as $file) {
            $outputFilePath = self::OUTPUT_FIXTURES_DIR . $file->getFilename();
            if (!file_exists($outputFilePath)) {
                self::markTestIncomplete("Missing output fixture: {$outputFilePath}");
            }

            $configs = [Yaml::parseFile($file->getPathname())];
            $expectedProcessedConfiguration = Yaml::parseFile($outputFilePath);

            yield 'Custom tags: ' . $file->getBasename() => [$configs, $expectedProcessedConfiguration];
        }
    }

    /**
     * Simple data provider for testProcessingConfiguration.
     *
     * @return array<string, mixed>
     *
     * @see testProcessingConfiguration
     */
    public function providerForTestProcessingConfiguration(): array
    {
        return [
            'Empty configuration' => [
                [],
                [
                    'custom_tags' => [],
                    'custom_styles' => [],
                    'enabled_attribute_types' => ['number', 'string', 'boolean', 'choice', 'link'],
                ],
            ],
            'Alloy editor configs from multiple sources' => [
                // input configs
                [
                    [
                        'alloy_editor' => [
                            'extra_plugins' => ['plugin1'],
                            'extra_buttons' => [
                                'paragraph' => ['button1', 'button2'],
                                'embed' => ['button1'],
                            ],
                            'native_attributes' => ['element1' => ['attribute11']],
                        ],
                    ],
                    [
                        'alloy_editor' => [
                            'extra_plugins' => ['plugin2'],
                            'extra_buttons' => [
                                'paragraph' => ['button3', 'button4'],
                                'embed' => ['button1'],
                            ],
                            'native_attributes' => ['element1' => ['attribute12'], 'element2' => ['attribute21']],
                        ],
                    ],
                ],
                // expected merged config
                [
                    'alloy_editor' => [
                        'extra_buttons' => [
                            'paragraph' => ['button1', 'button2', 'button3', 'button4'],
                            'embed' => ['button1', 'button1'],
                        ],
                        'extra_plugins' => ['plugin1', 'plugin2'],
                        'native_attributes' => ['element1' => ['attribute11', 'attribute12'], 'element2' => ['attribute21']],
                    ],
                    'custom_tags' => [],
                    'custom_styles' => [],
                    'enabled_attribute_types' => ['number', 'string', 'boolean', 'choice', 'link'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestProcessingConfiguration
     * @dataProvider providerForTestProcessingCustomTagsConfiguration
     *
     * @param array<string, mixed> $configurationValues
     * @param array<string, mixed> $expectedProcessedConfiguration
     */
    public function testProcessingConfiguration(
        array $configurationValues,
        array $expectedProcessedConfiguration
    ): void {
        $this->assertProcessedConfigurationEquals($configurationValues, $expectedProcessedConfiguration);
    }
}
