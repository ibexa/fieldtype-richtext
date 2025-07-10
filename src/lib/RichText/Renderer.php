<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\FieldTypeRichText\RichText;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\FieldTypeRichText\RichText\RendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

/**
 * Symfony implementation of RichText field type embed renderer.
 */
class Renderer implements RendererInterface
{
    public const int RESOURCE_TYPE_CONTENT = 0;
    public const int RESOURCE_TYPE_LOCATION = 1;

    protected Repository $repository;

    private PermissionResolver $permissionResolver;

    protected string $tagConfigurationNamespace;

    protected string $styleConfigurationNamespace;

    protected string $embedConfigurationNamespace;

    protected ConfigResolverInterface $configResolver;

    protected Environment $templateEngine;

    protected LoggerInterface $logger;

    /**
     * @var array<string, mixed>
     */
    private array $customTagsConfiguration;

    /**
     * @var array<string, mixed>
     */
    private array $customStylesConfiguration;

    /**
     * @param array<string, mixed> $customTagsConfiguration
     * @param array<string, mixed> $customStylesConfiguration
     */
    public function __construct(
        Repository $repository,
        ConfigResolverInterface $configResolver,
        Environment $templateEngine,
        PermissionResolver $permissionResolver,
        string $tagConfigurationNamespace,
        string $styleConfigurationNamespace,
        string $embedConfigurationNamespace,
        LoggerInterface $logger = null,
        array $customTagsConfiguration = [],
        array $customStylesConfiguration = []
    ) {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->templateEngine = $templateEngine;
        $this->permissionResolver = $permissionResolver;
        $this->tagConfigurationNamespace = $tagConfigurationNamespace;
        $this->styleConfigurationNamespace = $styleConfigurationNamespace;
        $this->embedConfigurationNamespace = $embedConfigurationNamespace;
        $this->logger = $logger ?? new NullLogger();
        $this->customTagsConfiguration = $customTagsConfiguration;
        $this->customStylesConfiguration = $customStylesConfiguration;
    }

    public function renderContentEmbed(int $contentId, string $viewType, array $parameters, bool $isInline): ?string
    {
        $isDenied = false;

        try {
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
            $content = $this->repository->sudo(
                static function (Repository $repository) use ($contentId): Content {
                    return $repository->getContentService()->loadContent($contentId);
                }
            );

            if (!$content->contentInfo->mainLocationId) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} is trashed."
                );

                return null;
            }

            if ($content->contentInfo->isHidden) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} is hidden."
                );

                return null;
            }

            $this->checkContentPermissions($content);
        } catch (AccessDeniedException $e) {
            $this->logger->error(
                "Could not render embedded resource: access denied to embed Content #{$contentId}"
            );

            $isDenied = true;
        } catch (Exception $e) {
            if ($e instanceof NotFoundHttpException || $e instanceof NotFoundException) {
                $this->logger->error(
                    "Could not render embedded resource: Content #{$contentId} not found"
                );

                return null;
            } else {
                throw $e;
            }
        }

        $templateName = $this->getEmbedTemplateName(
            static::RESOURCE_TYPE_CONTENT,
            $isInline,
            $isDenied
        );

        if ($templateName === null) {
            $this->logger->error(
                'Could not render embedded resource: no template configured'
            );

            return null;
        }

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
            $this->logger->error(
                "Could not render embedded resource: template '{$templateName}' does not exists"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    public function renderLocationEmbed(int $locationId, string $viewType, array $parameters, bool $isInline): ?string
    {
        $isDenied = false;

        try {
            $location = $this->checkLocation($locationId);

            if ($location->invisible) {
                $this->logger->error(
                    "Could not render embedded resource: Location #{$locationId} is not visible"
                );

                return null;
            }
        } catch (AccessDeniedException $e) {
            $this->logger->error(
                "Could not render embedded resource: access denied to embed Location #{$locationId}"
            );

            $isDenied = true;
        } catch (Exception $e) {
            if ($e instanceof NotFoundHttpException || $e instanceof NotFoundException) {
                $this->logger->error(
                    "Could not render embedded resource: Location #{$locationId} not found"
                );

                return null;
            } else {
                throw $e;
            }
        }

        $templateName = $this->getEmbedTemplateName(
            static::RESOURCE_TYPE_LOCATION,
            $isInline,
            $isDenied
        );

        if ($templateName === null) {
            $this->logger->error(
                'Could not render embedded resource: no template configured'
            );

            return null;
        }

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
            $this->logger->error(
                "Could not render embedded resource: template '{$templateName}' does not exists"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    public function renderTemplate(string $name, string $type, array $parameters, bool $isInline): ?string
    {
        $templateName = match ($type) {
            'style' => $this->getStyleTemplateName($name, $isInline),
            default => $this->getTagTemplateName($name, $isInline),
        };

        if ($templateName === null) {
            $this->logger->error(
                "Could not render template {$type} '{$name}': no template configured"
            );

            return null;
        }

        if (!$this->templateEngine->getLoader()->exists($templateName)) {
            $this->logger->error(
                "Could not render template {$type} '{$name}': template '{$templateName}' does not exist"
            );

            return null;
        }

        return $this->render($templateName, $parameters);
    }

    /**
     * Renders template $templateReference with given $parameters.
     *
     * @param array<string, mixed> $parameters
     */
    protected function render(string $templateReference, array $parameters): string
    {
        return $this->templateEngine->render(
            $templateReference,
            $parameters
        );
    }

    /**
     * Returns a configured template name for the given Custom Style identifier.
     */
    protected function getStyleTemplateName(string $identifier, bool $isInline): ?string
    {
        if (!empty($this->customStylesConfiguration[$identifier]['template'])) {
            return $this->customStylesConfiguration[$identifier]['template'];
        }

        $this->logger->warning(
            "Template style '{$identifier}' configuration was not found"
        );

        if ($isInline) {
            $configurationReference = $this->styleConfigurationNamespace . '.default_inline';
        } else {
            $configurationReference = $this->styleConfigurationNamespace . '.default';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Template style '{$identifier}' default configuration was not found"
        );

        return null;
    }

    /**
     * Returns a configured template name for the given template tag identifier.
     */
    protected function getTagTemplateName(string $identifier, bool $isInline): ?string
    {
        if (isset($this->customTagsConfiguration[$identifier])) {
            return $this->customTagsConfiguration[$identifier]['template'];
        }

        // BC layer:
        $configurationReference = $this->tagConfigurationNamespace . '.' . $identifier;

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }
        // End of BC layer --/

        $this->logger->warning(
            "Template tag '{$identifier}' configuration was not found"
        );

        if ($isInline) {
            $configurationReference = $this->tagConfigurationNamespace . '.default_inline';
        } else {
            $configurationReference = $this->tagConfigurationNamespace . '.default';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Template tag '{$identifier}' default configuration was not found"
        );

        return null;
    }

    /**
     * Returns a configured template reference for the given embed parameters.
     */
    protected function getEmbedTemplateName(int $resourceType, bool $isInline, bool $isDenied): ?string
    {
        $configurationReference = $this->embedConfigurationNamespace;

        if ($resourceType === static::RESOURCE_TYPE_CONTENT) {
            $configurationReference .= '.content';
        } else {
            $configurationReference .= '.location';
        }

        if ($isInline) {
            $configurationReference .= '_inline';
        }

        if ($isDenied) {
            $configurationReference .= '_denied';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Embed tag configuration '{$configurationReference}' was not found"
        );

        $configurationReference = $this->embedConfigurationNamespace;

        $configurationReference .= '.default';

        if ($isInline) {
            $configurationReference .= '_inline';
        }

        if ($this->configResolver->hasParameter($configurationReference)) {
            $configuration = $this->configResolver->getParameter($configurationReference);

            return $configuration['template'];
        }

        $this->logger->warning(
            "Embed tag default configuration '{$configurationReference}' was not found"
        );

        return null;
    }

    /**
     * Check embed permissions for the given Content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    protected function checkContentPermissions(Content $content): void
    {
        // Check both 'content/read' and 'content/view_embed'.
        if (
            !$this->permissionResolver->canUser('content', 'read', $content)
            && !$this->permissionResolver->canUser('content', 'view_embed', $content)
        ) {
            throw new AccessDeniedException();
        }

        // Check that Content is published, since sudo allows loading unpublished content.
        if (
            !$content->getVersionInfo()->isPublished()
            && !$this->permissionResolver->canUser('content', 'versionread', $content)
        ) {
            throw new AccessDeniedException();
        }
    }

    /**
     * Checks embed permissions for the given Location $id and returns the Location.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function checkLocation(int|string $id): Location
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
        $location = $this->repository->sudo(
            static function (Repository $repository) use ($id): Location {
                return $repository->getLocationService()->loadLocation((int) $id);
            }
        );

        // Check both 'content/read' and 'content/view_embed'.
        if (
            !$this->permissionResolver->canUser(
                'content',
                'read',
                $location->contentInfo,
                [$location]
            )
            && !$this->permissionResolver->canUser(
                'content',
                'view_embed',
                $location->contentInfo,
                [$location]
            )
        ) {
            throw new AccessDeniedException();
        }

        return $location;
    }
}
