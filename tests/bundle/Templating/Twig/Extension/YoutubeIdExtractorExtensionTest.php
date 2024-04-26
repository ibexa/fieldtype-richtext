<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\FieldTypeRichText\Templating\Twig\Extension;

use Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension\YoutubeIdExtractorExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class YoutubeIdExtractorExtensionTest extends TestCase
{
    public function getYouTubeUrls(): array
    {
        return [
            ['http://www.youtube.com/watch?v=Z1xNWm6dHp4', 'Z1xNWm6dHp4'],
            ['http://youtu.be/Z1xNWm6dHp4', 'Z1xNWm6dHp4'],
            ['https://youtu.be/Z1xNWm6dHp4', 'Z1xNWm6dHp4'],
            ['https://youtu.be/Z1xNWm6dHp4?t=4s', 'Z1xNWm6dHp4'],
            ['http://www.youtube.com/v/Z1xNWm6dHp4?fs=1&hl=en_US', 'Z1xNWm6dHp4'],
            ['http://www.youtube.com/watch?v=Z1xNWm6dHp4', 'Z1xNWm6dHp4'],
            ['http://www.youtube.com/ytscreeningroom?v=NRHVzbJVx8I', 'NRHVzbJVx8I'],
            ['http://www.youtube.com/watch?v=MsRua-Tdhf8&feature=g-vrec', 'MsRua-Tdhf8'],
            ['http://www.youtube.com/watch?v=MsRua-Tdhf8&feature=youtu.be', 'MsRua-Tdhf8'],
            ['http://www.youtube-nocookie.com/watch?v=WNlN4U4Qbwc', 'WNlN4U4Qbwc'],
            ['http://www.youtube.com/embed/WNlN4U4Qbwc', 'WNlN4U4Qbwc'],
            ['https://www.youtube.com/embed/WNlN4U4Qbwc', 'WNlN4U4Qbwc'],
            ['https://www.youtube.com/watch?v=WNlN4U4Qbwc&feature=youtu.be', 'WNlN4U4Qbwc'],
            ['https://www.youtube.com/watch?v=WNlN4U4Qbwc', 'WNlN4U4Qbwc'],
            ['m.youtube.com/watch?v=bfs_6KeqTzU', 'bfs_6KeqTzU'],
            ['youtube.com/watch?v=Z1xNWm6dHp4', 'Z1xNWm6dHp4'],
            ['badstring', null],
            ['http://www.youtube.com/', null],
            ['//something/', null],
        ];
    }

    /**
     * @covers \Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension\YoutubeIdExtractorExtension::extractId
     *
     * @dataProvider getYouTubeUrls
     *
     * @param string $input
     * @param string|null $expected
     */
    public function testExtractId(string $input, ?string $expected): void
    {
        $subject = new YoutubeIdExtractorExtension();
        $result = $subject->extractId($input);
        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Ibexa\Bundle\FieldTypeRichText\Templating\Twig\Extension\YoutubeIdExtractorExtension::getFunctions
     */
    public function testGetFunctions(): void
    {
        $subject = new YoutubeIdExtractorExtension();
        /** @var \Twig\TwigFunction[] $result */
        $result = $subject->getFunctions();
        self::assertIsArray($result);
        self::assertInstanceOf(TwigFunction::class, $result[0]);
        self::assertEquals('ibexa_richtext_youtube_extract_id', $result[0]->getName());
    }
}

class_alias(YoutubeIdExtractorExtensionTest::class, 'EzSystems\Tests\EzPlatformRichTextBundle\Templating\Twig\Extension\YoutubeIdExtractorExtensionTest');
