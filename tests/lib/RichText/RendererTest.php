<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\FieldTypeRichText\RichText;

use Exception;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\FieldTypeRichText\RichText\Renderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class RendererTest extends TestCase
{
    public function setUp(): void
    {
        $this->repositoryMock = $this->getRepositoryMock();
        $this->permissionResolverMock = $this->getPermissionResolverMock();
        $this->configResolverMock = $this->getConfigResolverMock();
        $this->templateEngineMock = $this->getTemplateEngineMock();
        $this->loggerMock = $this->getLoggerMock();
        $this->loaderMock = $this->getLoaderMock();
        parent::setUp();
    }

    public function testRenderTag(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $result = 'result';

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects(self::once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            $result,
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $parameters = ['parameters'];
        $isInline = true;

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn(null);

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render template tag '{$name}': no template configured");

        self::assertNull(
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderTagNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'getTagTemplateName']);
        $name = 'tag';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getTagTemplateName')
            ->with($name, $isInline)
            ->willReturn('templateName');

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render template tag '{$name}': template '{$templateName}' does not exist");

        self::assertNull(
            $renderer->renderTemplate($name, 'tag', $parameters, $isInline)
        );
    }

    public function providerForTestRenderTagWithTemplate(): array
    {
        return [
            [
                $tagName = 'tag1',
                [
                    [
                        [[$namespace = "test.name.space.tag.{$tagName}"]],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                false,
                'result',
            ],
            [
                $tagName = 'tag2',
                [
                    [
                        [[$namespace = "test.name.space.tag.{$tagName}"]],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                true,
                'result',
            ],
            [
                $tagName = 'tag3',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], [$namespace = 'test.name.space.tag.default']],
                        [false, true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                    ],
                    [],
                ],
                $templateName,
                $templateName,
                false,
                'result',
            ],
            [
                $tagName = 'tag4',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], [$namespace = 'test.name.space.tag.default_inline']],
                        [false, true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                    ],
                    [],
                ],
                $templateName,
                $templateName,
                true,
                'result',
            ],
            [
                $tagName = 'tag5',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], ['test.name.space.tag.default']],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                        ["Template tag '{$tagName}' default configuration was not found"],
                    ],
                    [
                        ["Could not render template tag '{$tagName}': no template configured"],
                    ],
                ],
                null,
                null,
                false,
                null,
            ],
            [
                $tagName = 'tag6',
                [
                    [
                        [["test.name.space.tag.{$tagName}"], ['test.name.space.tag.default_inline']],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Template tag '{$tagName}' configuration was not found"],
                        ["Template tag '{$tagName}' default configuration was not found"],
                    ],
                    [
                        ["Could not render template tag '{$tagName}': no template configured"],
                    ],
                ],
                null,
                null,
                true,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderTagWithTemplate
     */
    public function testRenderTagWithTemplate(
        string $tagName,
        array $configResolverParams,
        array $loggerParams,
        ?string $templateEngineTemplate,
        ?string $renderTemplate,
        bool $isInline,
        ?string $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render']);
        $parameters = ['parameters'];

        if (!isset($renderTemplate)) {
            $renderer
                ->expects(self::never())
                ->method('render');
        } else {
            $renderer
                ->expects(self::once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects(self::once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
            }
        }

        self::assertEquals(
            $renderResult,
            $renderer->renderTemplate($tagName, 'tag', $parameters, $isInline)
        );
    }

    public function testRenderContentEmbed(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $result = 'result';
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = null;
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->willReturn($contentMock);

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        self::assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock);

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        self::assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedAccessDenied(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->will(self::throwException(new AccessDeniedException()));

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_CONTENT, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Content #{$contentId}");

        self::assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedTrashed(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects(self::once())
            ->method('__get')
            ->with('mainLocationId')
            ->willReturn(null);

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->expects(self::once())
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is trashed.");

        self::assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedHidden(): void
    {
        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;

        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects(self::exactly(2))
            ->method('__get')
            ->withConsecutive(
                ['mainLocationId'],
                ['isHidden'],
            )->willReturnOnConsecutiveCalls(
                2,
                true
            );

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} is hidden.");

        self::assertNull(
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function providerForTestRenderContentEmbedNotFound(): array
    {
        return [
            [new NotFoundException('Content', 42)],
            [new NotFoundHttpException()],
        ];
    }

    /**
     * @dataProvider providerForTestRenderContentEmbedNotFound
     */
    public function testRenderContentEmbedNotFound(Exception $exception): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions', 'getEmbedTemplateName']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->will(self::throwException($exception));

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: Content #{$contentId} not found");

        self::assertEquals(
            $result,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderContentEmbedThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something threw up');

        $renderer = $this->getMockedRenderer(['checkContentPermissions']);
        $contentId = 42;
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        $renderer
            ->expects(self::once())
            ->method('checkContentPermissions')
            ->with($contentMock)
            ->will(self::throwException(new Exception('Something threw up')));

        $renderer->renderContentEmbed($contentId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderContentWithTemplate(): array
    {
        $contentId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.content_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
                ],
                [
                    [],
                    [
                        ["Could not render embedded resource: access denied to embed Content #{$contentId}"],
                    ],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.content_inline_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
                ],
                [
                    [],
                    [
                        ["Could not render embedded resource: access denied to embed Content #{$contentId}"],
                    ],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.content']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.content_inline']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName5']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName6']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.content_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderContentWithTemplate
     */
    public function testRenderContentWithTemplate(
        bool $isInline,
        ?AccessDeniedException $deniedException,
        array $configResolverParams,
        array $loggerParams,
        ?string $templateEngineTemplate,
        ?string $renderTemplate,
        ?string $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render', 'checkContentPermissions']);
        $contentId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mainLocationId = 2;

        $contentMock = $this->getContentMock($mainLocationId);

        $this->repositoryMock
            ->expects(self::once())
            ->method('sudo')
            ->willReturn($contentMock);

        if (isset($deniedException)) {
            $renderer
                ->expects(self::once())
                ->method('checkContentPermissions')
                ->with($contentMock)
                ->will(self::throwException($deniedException));
        } else {
            $renderer
                ->expects(self::once())
                ->method('checkContentPermissions')
                ->with($contentMock)
                ->willReturn($contentMock);
        }

        if (!isset($renderTemplate)) {
            $renderer
                ->expects(self::never())
                ->method('render');
        } else {
            $renderer
                ->expects(self::once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects(self::once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
            }
        }

        self::assertEquals(
            $renderResult,
            $renderer->renderContentEmbed($contentId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbed(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $result = 'result';
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects(self::once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateConfigured(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = null;
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects(self::once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not render embedded resource: no template configured');

        self::assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedNoTemplateFound(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = false;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects(self::once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(false);

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(false);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: template '{$templateName}' does not exists");

        self::assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedAccessDenied(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $templateName = 'templateName';
        $parameters = ['parameters'];
        $isInline = true;
        $isDenied = true;
        $result = 'result';

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->will(self::throwException(new AccessDeniedException()));

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with($templateName, $parameters)
            ->willReturn($result);

        $renderer
            ->expects(self::once())
            ->method('getEmbedTemplateName')
            ->with(Renderer::RESOURCE_TYPE_LOCATION, $isInline, $isDenied)
            ->willReturn($templateName);

        $this->loaderMock
            ->method('exists')
            ->with($templateName)
            ->willReturn(true);

        $this->templateEngineMock
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($this->loaderMock);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: access denied to embed Location #{$locationId}");

        self::assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedInvisible(): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $mockLocation = $this->createMock(Location::class);

        $mockLocation
            ->expects(self::once())
            ->method('__get')
            ->with('invisible')
            ->willReturn(true);

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->willReturn($mockLocation);

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} is not visible");

        self::assertNull(
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function providerForTestRenderLocationEmbedNotFound(): array
    {
        return [
            [new NotFoundException('Location', 42)],
            [new NotFoundHttpException()],
        ];
    }

    /**
     * @dataProvider providerForTestRenderLocationEmbedNotFound
     */
    public function testRenderLocationEmbedNotFound(Exception $exception): void
    {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation', 'getEmbedTemplateName']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $isInline = true;
        $result = null;

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->will(self::throwException($exception));

        $renderer
            ->expects(self::never())
            ->method('render');

        $renderer
            ->expects(self::never())
            ->method('getEmbedTemplateName');

        $this->templateEngineMock
            ->expects(self::never())
            ->method('getLoader');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with("Could not render embedded resource: Location #{$locationId} not found");

        self::assertEquals(
            $result,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    public function testRenderLocationEmbedThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something threw up');

        $renderer = $this->getMockedRenderer(['checkLocation']);
        $locationId = 42;

        $renderer
            ->expects(self::once())
            ->method('checkLocation')
            ->with($locationId)
            ->will(self::throwException(new Exception('Something threw up')));

        $renderer->renderLocationEmbed($locationId, 'embedTest', ['parameters'], true);
    }

    public function providerForTestRenderLocationWithTemplate(): array
    {
        $locationId = 42;

        return [
            [
                false,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.location_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName1']],
                    ],
                ],
                [
                    [],
                    [["Could not render embedded resource: access denied to embed Location #{$locationId}"]],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                new AccessDeniedException(),
                [
                    [
                        [[$namespace = 'test.name.space.embed.location_inline_denied']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName2']],
                    ],
                ],
                [
                    [],
                    [["Could not render embedded resource: access denied to embed Location #{$locationId}"]],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.location']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName3']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [[$namespace = 'test.name.space.embed.location_inline']],
                        [true],
                    ],
                    [
                        [[$namespace]],
                        [['template' => $templateName = 'templateName4']],
                    ],
                ],
                [],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName5']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, true],
                    ],
                    [
                        [[$namespace2]],
                        [['template' => $templateName = 'templateName6']],
                    ],
                ],
                [
                    [["Embed tag configuration '{$namespace}' was not found"]],
                    [],
                ],
                $templateName,
                $templateName,
                'result',
            ],
            [
                false,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location'],
                            [$namespace2 = 'test.name.space.embed.default'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
            [
                true,
                null,
                [
                    [
                        [
                            [$namespace = 'test.name.space.embed.location_inline'],
                            [$namespace2 = 'test.name.space.embed.default_inline'],
                        ],
                        [false, false],
                    ],
                    [
                        [],
                        [],
                    ],
                ],
                [
                    [
                        ["Embed tag configuration '{$namespace}' was not found"],
                        ["Embed tag default configuration '{$namespace2}' was not found"],
                    ],
                    [],
                ],
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestRenderLocationWithTemplate
     */
    public function testRenderLocationWithTemplate(
        bool $isInline,
        ?AccessDeniedException $deniedException,
        array $configResolverParams,
        array $loggerParams,
        ?string $templateEngineTemplate,
        ?string $renderTemplate,
        ?string $renderResult
    ): void {
        $renderer = $this->getMockedRenderer(['render', 'checkLocation']);
        $locationId = 42;
        $viewType = 'embedTest';
        $parameters = ['parameters'];
        $mockLocation = $this->createMock(Location::class);

        if (isset($deniedException)) {
            $renderer
                ->expects(self::once())
                ->method('checkLocation')
                ->with($locationId)
                ->will(self::throwException($deniedException));
        } else {
            $mockLocation
                ->expects(self::once())
                ->method('__get')
                ->with('invisible')
                ->willReturn(false);

            $renderer
                ->expects(self::once())
                ->method('checkLocation')
                ->with($locationId)
                ->willReturn($mockLocation);
        }

        if (!isset($renderTemplate)) {
            $renderer
                ->expects(self::never())
                ->method('render');
        } else {
            $renderer
                ->expects(self::once())
                ->method('render')
                ->with($renderTemplate, $parameters)
                ->willReturn($renderResult);
        }

        if (!isset($templateEngineTemplate)) {
            $this->templateEngineMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            $this->loaderMock
                ->method('exists')
                ->with($templateEngineTemplate)
                ->willReturn(true);

            $this->templateEngineMock
                ->expects(self::once())
                ->method('getLoader')
                ->willReturn($this->loaderMock);
        }

        if (empty($configResolverParams)) {
            $this->configResolverMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$hasParameterValues, $getParameterValues] = $configResolverParams;
            [$hasParameterArguments, $hasParameterReturnValues] = $hasParameterValues;
            [$getParameterArguments, $getParameterReturnValues] = $getParameterValues;

            if (!empty($hasParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($hasParameterArguments)))
                    ->method('hasParameter')
                    ->withConsecutive($hasParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$hasParameterReturnValues);
            }

            if (!empty($getParameterArguments)) {
                $this->configResolverMock
                    ->expects(self::exactly(count($getParameterArguments)))
                    ->method('getParameter')
                    ->withConsecutive($getParameterArguments[0])
                    ->willReturnOnConsecutiveCalls(...$getParameterReturnValues);
            }
        }

        if (empty($loggerParams)) {
            $this->loggerMock
                ->expects(self::never())
                ->method(self::anything());
        } else {
            [$warningArguments, $errorArguments] = $loggerParams;

            if (!empty($warningArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($warningArguments)))
                    ->method('warning')
                    ->withConsecutive(...$warningArguments);
            }

            if (!empty($errorArguments)) {
                $this->loggerMock
                    ->expects(self::exactly(count($errorArguments)))
                    ->method('error')
                    ->withConsecutive(...$errorArguments);
            }
        }

        self::assertEquals(
            $renderResult,
            $renderer->renderLocationEmbed($locationId, $viewType, $parameters, $isInline)
        );
    }

    /**
     * @param array $methods
     *
     * @return \Ibexa\FieldTypeRichText\RichText\Renderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockedRenderer(array $methods = []): MockObject
    {
        return $this->getMockBuilder(Renderer::class)
            ->setConstructorArgs(
                [
                    $this->repositoryMock,
                    $this->configResolverMock,
                    $this->templateEngineMock,
                    $this->permissionResolverMock,
                    'test.name.space.tag',
                    'test.name.space.style',
                    'test.name.space.embed',
                    $this->loggerMock,
                ]
            )
            ->setMethods($methods)
            ->getMock();
    }

    /** @var \Ibexa\Contracts\Core\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject */
    protected Repository $repositoryMock;

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock(): Repository
    {
        return $this->createMock(Repository::class);
    }

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject */
    protected ConfigResolverInterface $configResolverMock;

    /**
     * @return \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigResolverMock(): ConfigResolverInterface
    {
        return $this->createMock(ConfigResolverInterface::class);
    }

    /** @var \Twig\Environment&\PHPUnit\Framework\MockObject\MockObject */
    protected Environment $templateEngineMock;

    /**
     * @return \Twig\Environment&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTemplateEngineMock(): Environment
    {
        return $this->createMock(Environment::class);
    }

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver&\PHPUnit\Framework\MockObject\MockObject */
    protected PermissionResolver $permissionResolverMock;

    /**
     * @return \Ibexa\Contracts\Core\Repository\PermissionResolver&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPermissionResolverMock(): PermissionResolver
    {
        return $this->createMock(PermissionResolver::class);
    }

    /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    protected LoggerInterface $loggerMock;

    /**
     * @return \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @var \Twig\Loader\LoaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $loaderMock;

    /**
     * @return \Twig\Loader\LoaderInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoaderMock(): LoaderInterface
    {
        return $this->createMock(LoaderInterface::class);
    }

    protected function getContentMock($mainLocationId): MockObject
    {
        $contentInfoMock = $this->createMock(ContentInfo::class);
        $contentInfoMock
            ->expects(self::exactly(2))
            ->method('__get')
            ->withConsecutive(
                ['mainLocationId'],
                ['isHidden'],
            )->willReturnOnConsecutiveCalls(
                $mainLocationId,
                false
            );

        $contentMock = $this->createMock(Content::class);
        $contentMock
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfoMock);

        return $contentMock;
    }
}
