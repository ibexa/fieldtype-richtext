<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\Configuration;

use Ibexa\Contracts\FieldTypeRichText\Configuration\Provider;
use Ibexa\FieldTypeRichText\Configuration\AggregateProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\FieldTypeRichText\Configuration\AggregateProvider
 */
class AggregateProviderTest extends TestCase
{
    /**
     * @covers \Ibexa\FieldTypeRichText\Configuration\AggregateProvider::getConfiguration
     *
     * @dataProvider getConfiguration
     *
     * @param array<string, array<string, mixed>> $configuration
     */
    public function testGetConfiguration(array $configuration): void
    {
        $providers = [];
        foreach ($configuration as $providerName => $providerConfiguration) {
            $providers[] = new class($providerName, $providerConfiguration) implements Provider {
                private string $name;

                /** @var array<string, mixed> */
                private array $configuration;

                /**
                 * @param array<string, mixed> $configuration
                 */
                public function __construct(string $name, array $configuration)
                {
                    $this->name = $name;
                    $this->configuration = $configuration;
                }

                public function getName(): string
                {
                    return $this->name;
                }

                /**
                 * @return array<string, mixed>
                 */
                public function getConfiguration(): array
                {
                    return $this->configuration;
                }
            };
        }

        $providerService = new AggregateProvider($providers);

        self::assertEquals($configuration, $providerService->getConfiguration());
    }

    /**
     * @return array<mixed>
     */
    public function getConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'noConfigProvider' => [],
                ],
            ],
            [
                [
                    'provider1' => [
                        'provider1_key1' => 'provider1_key1_value1',
                        'provider1_key2' => 'provider1_key2_value2',
                    ],
                    'provider2' => [
                        'provider2_key1' => 'provider2_key1_value1',
                        'provider2_key2' => 'provider2_key2_value2',
                    ],
                ],
            ],
            [
                [
                    'provider1' => [1, 2, 3],
                    'provider2' => [1, 2, 3],
                ],
            ],
        ];
    }
}
