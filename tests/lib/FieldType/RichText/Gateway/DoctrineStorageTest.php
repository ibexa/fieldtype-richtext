<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType\RichText\Gateway;

use Ibexa\Core\FieldType\Url\UrlStorage\Gateway\DoctrineStorage as UrlStorageDoctrineGateway;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway\DoctrineStorage;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * Tests the RichText DoctrineStorage.
 */
class DoctrineStorageTest extends TestCase
{
    /**
     * @var \Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway\DoctrineStorage
     */
    protected $storageGateway;

    public function testGetContentIds(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/contentobjects.php');

        $gateway = $this->getStorageGateway();

        self::assertEquals(
            [
                'f5c88a2209584891056f987fd965b0ba' => 4,
                'faaeb9be3bd98ed09f606fc16d144eca' => 10,
            ],
            $gateway->getContentIds(
                [
                    'f5c88a2209584891056f987fd965b0ba',
                    'faaeb9be3bd98ed09f606fc16d144eca',
                    'fake',
                ]
            )
        );
    }

    /**
     * Return a ready to test DoctrineStorage gateway.
     *
     * @return \Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway\DoctrineStorage
     */
    protected function getStorageGateway()
    {
        if (!isset($this->storageGateway)) {
            $connection = $this->getDatabaseConnection();
            $urlGateway = new UrlStorageDoctrineGateway($connection);
            $this->storageGateway = new DoctrineStorage($urlGateway, $connection);
        }

        return $this->storageGateway;
    }
}
