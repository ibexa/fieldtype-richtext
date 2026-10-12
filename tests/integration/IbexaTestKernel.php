<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use GuzzleHttp\Psr7\HttpFactory;
use Hautelook\TemplatedUriBundle\HautelookTemplatedUriBundle;
use Ibexa\Bundle\AdminUi\IbexaAdminUiBundle;
use Ibexa\Bundle\ContentForms\IbexaContentFormsBundle;
use Ibexa\Bundle\DesignEngine\IbexaDesignEngineBundle;
use Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle;
use Ibexa\Bundle\Mcp\IbexaMcpBundle;
use Ibexa\Bundle\Notifications\IbexaNotificationsBundle;
use Ibexa\Bundle\Rest\IbexaRestBundle;
use Ibexa\Bundle\Search\IbexaSearchBundle;
use Ibexa\Bundle\Test\Core\IbexaTestCoreBundle;
use Ibexa\Bundle\TwigComponents\IbexaTwigComponentsBundle;
use Ibexa\Bundle\User\IbexaUserBundle;
use Ibexa\Contracts\FieldTypeRichText\Persistence\Legacy\MigrateRichTextNamespaces\GatewayInterface;
use Ibexa\Contracts\Seo\Resolver\FieldReferenceResolverInterface;
use Ibexa\Contracts\Test\Core\IbexaTestKernel as BaseIbexaTestKernel;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Ibexa\Mcp\Server\McpServerRegistryInterface;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

final class IbexaTestKernel extends BaseIbexaTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new IbexaTestCoreBundle();

        yield new LexikJWTAuthenticationBundle();
        yield new HautelookTemplatedUriBundle();
        yield new WebpackEncoreBundle();
        yield new KnpMenuBundle();

        yield new IbexaRestBundle();
        yield new IbexaContentFormsBundle();
        yield new IbexaSearchBundle();
        yield new IbexaUserBundle();
        yield new IbexaDesignEngineBundle();
        yield new IbexaAdminUiBundle();
        yield new IbexaNotificationsBundle();
        yield new TwigComponentBundle();
        yield new IbexaTwigComponentsBundle();

        yield new IbexaFieldTypeRichTextBundle();
        yield new IbexaMcpBundle();
        yield new DAMADoctrineTestBundle();
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield from parent::getExposedServicesByClass();

        yield GatewayInterface::class;

        yield CacheIdentifierGeneratorInterface::class;

        yield McpServerRegistryInterface::class;
    }

    protected static function getExposedServicesById(): iterable
    {
        yield from parent::getExposedServicesById();

        yield 'ibexa.cache_pool' => TagAwareAdapterInterface::class;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/Resources/override.yaml');
        $loader->load(__DIR__ . '/Resources/config.yaml');
        $loader->load(__DIR__ . '/Resources/ibexa_mcp.yaml');
        $loader->load(static function (ContainerBuilder $container): void {
            $container->setDefinition(
                'Symfony\Component\Notifier\NotifierInterface',
                new Definition(NotifierInterface::class)
            );
            $resource = new FileResource(__DIR__ . '/Resources/routing.yaml');
            $container->addResource($resource);
            $container->setParameter('form.type_extension.csrf.enabled', false);
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => $resource->getResource(),
                ],
            ]);

            self::prepareMcpServer($container);
        });
    }

    /**
     * Services ibexa/mcp depends on without the RichText tools needing them: the PSR-17 factories of its HTTP
     * controller (tests run the MCP server directly, without HTTP) and the ibexa/seo resolver of its SEO tools.
     */
    private static function prepareMcpServer(ContainerBuilder $container): void
    {
        self::addSyntheticService($container, FieldReferenceResolverInterface::class);

        $httpFactory = new Definition(HttpFactory::class);
        $container->setDefinition(ResponseFactoryInterface::class, $httpFactory);
        $container->setDefinition(StreamFactoryInterface::class, $httpFactory);
    }
}
