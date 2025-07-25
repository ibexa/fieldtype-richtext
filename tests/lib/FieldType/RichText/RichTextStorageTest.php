<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\FieldType\RichText;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage;
use Ibexa\FieldTypeRichText\FieldType\RichText\RichTextStorage\Gateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RichTextStorageTest extends TestCase
{
    /**
     * @phpstan-return list<array{string, string, int[], array<int, string>}>
     */
    public function providerForTestGetFieldData(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezurl://123#fragment1">Existing external link</link>
    </para>
    <para>
        <link xlink:href="ezurl://456#fragment2">Non-existing external link</link>
    </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="https://www.ibexa.co#fragment1">Existing external link</link>
    </para>
    <para>
        <link xlink:href="#">Non-existing external link</link>
    </para>
</section>
',
                [123, 456],
                [123 => 'https://www.ibexa.co'],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>Oh links, where art thou?</para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>Oh links, where art thou?</para>
</section>
',
                [],
                [],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestGetFieldData
     *
     * @param int[] $linkIds
     * @param array<int, string> $linkUrls
     */
    public function testGetFieldData(string $xmlString, string $updatedXmlString, array $linkIds, array $linkUrls): void
    {
        $gateway = $this->getGatewayMock();
        $gateway
            ->expects(self::once())
            ->method('getIdUrlMap')
            ->with(self::equalTo($linkIds))
            ->willReturn($linkUrls);

        $gateway->expects(self::never())->method('getUrlIdMap');
        $gateway->expects(self::never())->method('getContentIds');
        $gateway->expects(self::never())->method('insertUrl');

        $logger = $this->getLoggerMock();
        $missingIds = array_diff($linkIds, array_keys($linkUrls));
        $errorMessages = array_map(static function (int $missingId): string {
            return "URL with ID {$missingId} not found";
        }, $missingIds);

        $logger
            ->expects(self::exactly(count($missingIds)))
            ->method('error')
            ->withConsecutive($errorMessages);

        $versionInfo = new VersionInfo();
        $value = new FieldValue(['data' => $xmlString]);
        $field = new Field(['value' => $value]);

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->getFieldData(
            $versionInfo,
            $field
        );

        self::assertEquals(
            $updatedXmlString,
            $field->value->data
        );
    }

    /**
     * @return list<array{string, string, string[], array<string, int>, array<string, int>, string[], array<string, int>, bool}>
     */
    public function providerForTestStoreFieldData(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezremote://abcdef789#fragment1">Content link</link>
    </para>
    <para>
        <link xlink:href="https://www.ibexa.co#fragment2">Existing external link</link>
    </para>
    <para>
        <link xlink:href="https://www.ibexa.co#fragment2">Existing external link repeated</link>
    </para>
    <para>
        <link xlink:href="https://developers.ibexa.co#fragment3">New external link</link>
    </para>
    <para>
        <link xlink:href="https://developers.ibexa.co#fragment3">New external link repeated</link>
    </para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezcontent://7575#fragment1">Content link</link>
    </para>
    <para>
        <link xlink:href="ezurl://123#fragment2">Existing external link</link>
    </para>
    <para>
        <link xlink:href="ezurl://123#fragment2">Existing external link repeated</link>
    </para>
    <para>
        <link xlink:href="ezurl://456#fragment3">New external link</link>
    </para>
    <para>
        <link xlink:href="ezurl://456#fragment3">New external link repeated</link>
    </para>
</section>
',
                ['https://www.ibexa.co', 'https://developers.ibexa.co'],
                ['https://www.ibexa.co' => 123],
                ['https://developers.ibexa.co' => 456],
                ['abcdef789'],
                ['abcdef789' => 7575],
                true,
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>Oh links, where art thou?</para>
</section>',
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>Oh links, where art thou?</para>
</section>
',
                [],
                [],
                [],
                [],
                [],
                true,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestStoreFieldData
     *
     * @param string[] $linkUrls
     * @param array<string, int> $linkIds
     * @param array<string, int> $insertLinks
     * @param string[] $remoteIds
     * @param array<string, int> $contentIds
     */
    public function testStoreFieldData(
        string $xmlString,
        string $updatedXmlString,
        array $linkUrls,
        array $linkIds,
        array $insertLinks,
        array $remoteIds,
        array $contentIds,
        bool $isUpdated
    ): void {
        $versionInfo = new VersionInfo(['versionNo' => 24]);
        $value = new FieldValue(['data' => $xmlString]);
        $field = new Field(['id' => 42, 'value' => $value]);
        $gateway = $this->getGatewayMock();

        $gateway
            ->expects(self::once())
            ->method('getUrlIdMap')
            ->with(self::equalTo($linkUrls))
            ->willReturn($linkIds);

        $gateway
            ->expects(self::once())
            ->method('getContentIds')
            ->with(self::equalTo($remoteIds))
            ->willReturn($contentIds);

        $gateway
            ->expects(self::never())
            ->method('getIdUrlMap');

        if (empty($insertLinks)) {
            $gateway
                ->expects(self::never())
                ->method('insertUrl');
        }

        [$urlAssertions, $insertedIds, $idsToLink] = $this->groupLinksData($linkUrls, $insertLinks, $linkIds);

        $gateway
            ->expects(self::exactly(count($urlAssertions)))
            ->method('insertUrl')
            ->withConsecutive($urlAssertions)
            ->willReturnOnConsecutiveCalls(...$insertedIds);

        $linkUrlsArguments = array_map(static function (int $id): array {
            return [$id, 42, 24];
        }, $idsToLink);

        $gateway
            ->expects(self::exactly(count($idsToLink)))
            ->method('linkUrl')
            ->withConsecutive(...$linkUrlsArguments);

        $gateway
            ->expects(self::once())
            ->method('unlinkUrl')
            ->with(42, 24, $idsToLink);

        $storage = $this->getPartlyMockedStorage($gateway);
        $result = $storage->storeFieldData(
            $versionInfo,
            $field
        );

        self::assertEquals(
            $isUpdated,
            $result
        );
        self::assertEquals(
            $updatedXmlString,
            $field->value->data
        );
    }

    /**
     * @param string[] $linkUrls
     * @param array<string|int> $insertLinks
     * @param array<string|int> $linkIds
     */
    private function groupLinksData(array $linkUrls, array $insertLinks, array $linkIds): array
    {
        $urlAssertions = [];
        $insertedIds = [];
        $idsToLink = [];

        foreach ($linkUrls as $url) {
            if (isset($insertLinks[$url])) {
                $id = $insertLinks[$url];
                $urlAssertions[] = self::equalTo($url);
                $insertedIds[] = $id;
                $idsToLink[] = $id;
            } else {
                $idsToLink[] = $linkIds[$url];
            }
        }

        return [
            $urlAssertions,
            $insertedIds,
            $idsToLink,
        ];
    }

    /**
     * @return list<array{string, string[], array<string, int>, array<int, array{url: string, id: int}>, string[], array<string, int>}>
     */
    public function providerForTestStoreFieldDataThrowsNotFoundException(): array
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"?>
<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ibexa.co/xmlns/dxp/docbook/xhtml" xmlns:ezcustom="http://ibexa.co/xmlns/dxp/docbook/custom" version="5.0-variant ezpublish-1.0">
    <para>
        <link xlink:href="ezremote://abcdef789#fragment1">Content link</link>
    </para>
</section>
',
                [],
                [],
                [],
                ['abcdef789'],
                [],
            ],
        ];
    }

    /**
     * @dataProvider providerForTestStoreFieldDataThrowsNotFoundException
     *
     * @param string[] $linkUrls
     * @param array<string, int> $linkIds
     * @param array<int, array{url: string, id: int}> $insertLinks
     * @param string[] $remoteIds
     * @param array<string, int> $contentIds
     */
    public function testStoreFieldDataThrowsNotFoundException(
        string $xmlString,
        array $linkUrls,
        array $linkIds,
        array $insertLinks,
        array $remoteIds,
        array $contentIds
    ): void {
        $this->expectException(NotFoundException::class);

        $gateway = $this->getGatewayMock();
        $gateway
            ->expects(self::once())
            ->method('getUrlIdMap')
            ->with(self::equalTo($linkUrls))
            ->willReturn($linkIds);
        $gateway
            ->expects(self::once())
            ->method('getContentIds')
            ->with(self::equalTo($remoteIds))
            ->willReturn($contentIds);
        $gateway->expects(self::never())->method('getIdUrlMap');
        if (empty($insertLinks)) {
            $gateway->expects(self::never())->method('insertUrl');
        }

        foreach ($insertLinks as $index => $linkMap) {
            $gateway
                ->expects(self::at($index + 2))
                ->method('insertUrl')
                ->with(self::equalTo($linkMap['url']))
                ->willReturn($linkMap['id']);
        }

        $versionInfo = new VersionInfo();
        $value = new FieldValue(['data' => $xmlString]);
        $field = new Field(['value' => $value]);

        $storage = $this->getPartlyMockedStorage($gateway);
        $storage->storeFieldData(
            $versionInfo,
            $field
        );
    }

    public function testDeleteFieldData(): void
    {
        $versionInfo = new VersionInfo(['versionNo' => 42]);
        $fieldIds = [12, 23];
        $gateway = $this->getGatewayMock();
        $storage = $this->getPartlyMockedStorage($gateway);
        $gateway
            ->expects(self::exactly(2))
            ->method('unlinkUrl')
            ->withConsecutive(
                [12, 42],
                [23, 42],
            );

        $storage->deleteFieldData(
            $versionInfo,
            $fieldIds
        );
    }

    protected function getPartlyMockedStorage(StorageGateway $gateway): RichTextStorage
    {
        return $this->getMockBuilder(RichTextStorage::class)
            ->setConstructorArgs(
                [
                    $gateway,
                    $this->getLoggerMock(),
                ]
            )
            ->setMethods(null)
            ->getMock();
    }

    protected (LoggerInterface&MockObject)|null $loggerMock = null;

    protected function getLoggerMock(): LoggerInterface&MockObject
    {
        if (!isset($this->loggerMock)) {
            $this->loggerMock = $this->getMockForAbstractClass(
                LoggerInterface::class
            );
        }

        return $this->loggerMock;
    }

    protected (Gateway&MockObject)|null $gatewayMock = null;

    protected function getGatewayMock(): Gateway&MockObject
    {
        if (!isset($this->gatewayMock)) {
            $this->gatewayMock = $this->createMock(Gateway::class);
        }

        return $this->gatewayMock;
    }
}
