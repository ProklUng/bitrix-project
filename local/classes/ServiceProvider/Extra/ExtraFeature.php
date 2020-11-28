<?php

namespace Local\ServiceProvider\Extra;

use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\DependencyInjection\CachePoolClearerPass;
use Symfony\Component\Cache\DependencyInjection\CachePoolPass;
use Symfony\Component\Cache\DependencyInjection\CachePoolPrunerPass;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class ExtraFeature
 * @package Local\ServiceProvider\Extra
 *
 * @since 28.11.2020
 */
class ExtraFeature
{
    /** @var bool $annotationsConfigEnabled Использовать ли аннотации в роутере. */
    private $annotationsConfigEnabled = false;

    /**
     * Симфоническая инициализация роутера.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     * @param array            $enabledLocales
     *
     * @throws Exception
     *
     * @since 20.11.2020
     */
    public function registerRouterConfiguration(array $config, ContainerBuilder $container, array $enabledLocales = [])
    {
        $loader = new XmlFileLoader($container, new FileLocator(
            $_SERVER['DOCUMENT_ROOT'] . '/vendor/symfony/framework-bundle/Resources/config')
        );

        if (!$this->isConfigEnabled($container, $config)) {
            $container->removeDefinition('console.command.router_debug');
            $container->removeDefinition('console.command.router_match');
            return;
        }

        $loader->load('routing.xml');

        if (null === $config['utf8']) {
            trigger_deprecation(
                'symfony/framework-bundle',
                '5.1',
                'Not setting the "framework.router.utf8" configuration option is deprecated, it will default to "true" in version 6.0.');
        }

        if ($config['utf8']) {
            $container->getDefinition('routing.loader')->replaceArgument(1, ['utf8' => true]);
        }

        if ($enabledLocales) {
            $enabledLocales = implode('|', array_map('preg_quote', $enabledLocales));
            $container->getDefinition('routing.loader')->replaceArgument(2, ['_locale' => $enabledLocales]);
        }

        if (!class_exists(ExpressionLanguage::class)) {
            $container->removeDefinition('router.expression_language_provider');
        }

        $container->setParameter('router.resource', $config['resource']);
        $router = $container->findDefinition('router.default');
        $argument = $router->getArgument(2);
        $argument['strict_requirements'] = $config['strict_requirements'];
        if (isset($config['type'])) {
            $argument['resource_type'] = $config['type'];
        }
        $router->replaceArgument(2, $argument);

        $container->setParameter('request_listener.http_port', $config['http_port']);
        $container->setParameter('request_listener.https_port', $config['https_port']);

        if (null !== $config['default_uri']) {
            $container->getDefinition('router.request_context')
                ->replaceArgument(0, $config['default_uri']);
        }

        if ($this->annotationsConfigEnabled) {
            $container->register('routing.loader.annotation', AnnotatedRouteControllerLoader::class)
                ->setPublic(false)
                ->addTag('routing.loader', ['priority' => -10])
                ->addArgument(new Reference('annotation_reader'));

            $container->register('routing.loader.annotation.directory', AnnotationDirectoryLoader::class)
                ->setPublic(false)
                ->addTag('routing.loader', ['priority' => -10])
                ->setArguments([
                    new Reference('file_locator'),
                    new Reference('routing.loader.annotation'),
                ]);

            $container->register('routing.loader.annotation.file', AnnotationFileLoader::class)
                ->setPublic(false)
                ->addTag('routing.loader', ['priority' => -10])
                ->setArguments([
                    new Reference('file_locator'),
                    new Reference('routing.loader.annotation'),
                ]);
        }
    }

    /**
     * PropertyInfo.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @throws LogicException
     */
    public function registerPropertyInfoConfiguration(ContainerBuilder $container): void
    {
        if (!interface_exists(PropertyInfoExtractorInterface::class)) {
            throw new LogicException(
                'PropertyInfo support cannot be enabled as the PropertyInfo component is not installed. 
                Try running "composer require symfony/property-info".'
            );
        }

        if (interface_exists('phpDocumentor\Reflection\DocBlockFactoryInterface')) {
            $definition = $container->register('property_info.php_doc_extractor',
                'Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor');
            $definition->setPrivate(true);
            $definition->addTag('property_info.description_extractor', ['priority' => -1000]);
            $definition->addTag('property_info.type_extractor', ['priority' => -1001]);
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @since 27.11.2020
     */
    public function registerCacheConfiguration(array $config, ContainerBuilder $container)
    {
        $container->addCompilerPass(new CachePoolPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 32);
        $container->addCompilerPass(new CachePoolClearerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new CachePoolPrunerPass(), PassConfig::TYPE_AFTER_REMOVING);

        if (!class_exists(DefaultMarshaller::class)) {
            $container->removeDefinition('cache.default_marshaller');
        }

        $version = new Parameter('container.build_id');
        $container->getDefinition('cache.adapter.apcu')->replaceArgument(2, $version);
        $container->getDefinition('cache.adapter.system')->replaceArgument(2, $version);
        $container->getDefinition('cache.adapter.filesystem')->replaceArgument(2, $config['directory']);

        if (isset($config['prefix_seed'])) {
            $container->setParameter('cache.prefix.seed', $config['prefix_seed']);
        }
        if ($container->hasParameter('cache.prefix.seed')) {
            // Inline any env vars referenced in the parameter
            $container->setParameter('cache.prefix.seed', $container->resolveEnvPlaceholders($container->getParameter('cache.prefix.seed'), true));
        }
        foreach (['doctrine', 'psr6', 'redis', 'memcached', 'pdo'] as $name) {
            if (isset($config[$name = 'default_'.$name.'_provider'])) {
                $container->setAlias('cache.'.$name, new Alias(CachePoolPass::getServiceProvider($container, $config[$name]), false));
            }
        }
        foreach (['app', 'system'] as $name) {
            $config['pools']['cache.'.$name] = [
                'adapters' => [$config[$name]],
                'public' => true,
                'tags' => false,
            ];
        }

        foreach ($config['pools'] as $name => $pool) {
            $pool['adapters'] = $pool['adapters'] ?: ['cache.app'];

            foreach ($pool['adapters'] as $provider => $adapter) {
                if ($config['pools'][$adapter]['tags'] ?? false) {
                    $pool['adapters'][$provider] = $adapter = '.'.$adapter.'.inner';
                }
            }

            if (1 === \count($pool['adapters'])) {
                if (!isset($pool['provider']) && !\is_int($provider)) {
                    $pool['provider'] = $provider;
                }
                $definition = new ChildDefinition($adapter);
            } else {
                $definition = new Definition(ChainAdapter::class, [$pool['adapters'], 0]);
                $pool['reset'] = 'reset';
            }

            if ($pool['tags']) {
                if (true !== $pool['tags'] && ($config['pools'][$pool['tags']]['tags'] ?? false)) {
                    $pool['tags'] = '.'.$pool['tags'].'.inner';
                }
                $container->register($name, TagAwareAdapter::class)
                    ->addArgument(new Reference('.'.$name.'.inner'))
                    ->addArgument(true !== $pool['tags'] ? new Reference($pool['tags']) : null)
                    ->setPublic($pool['public'])
                ;

                $pool['name'] = $name;
                $pool['public'] = false;
                $name = '.'.$name.'.inner';

                if (!\in_array($pool['name'], ['cache.app', 'cache.system'], true)) {
                    $container->registerAliasForArgument($pool['name'], TagAwareCacheInterface::class);
                    $container->registerAliasForArgument($name, CacheInterface::class, $pool['name']);
                    $container->registerAliasForArgument($name, CacheItemPoolInterface::class, $pool['name']);
                }
            } elseif (!\in_array($name, ['cache.app', 'cache.system'], true)) {
                $container->register('.'.$name.'.taggable', TagAwareAdapter::class)
                    ->addArgument(new Reference($name))
                ;
                $container->registerAliasForArgument('.'.$name.'.taggable', TagAwareCacheInterface::class, $name);
                $container->registerAliasForArgument($name, CacheInterface::class);
                $container->registerAliasForArgument($name, CacheItemPoolInterface::class);
            }

            $definition->setPublic($pool['public']);
            unset($pool['adapters'], $pool['public'], $pool['tags']);

            $definition->addTag('cache.pool', $pool);
            $container->setDefinition($name, $definition);
        }

        if (method_exists(PropertyAccessor::class, 'createCache')) {
            $propertyAccessDefinition = $container->register('cache.property_access', AdapterInterface::class);
            $propertyAccessDefinition->setPublic(false);

            if (!$container->getParameter('kernel.debug')) {
                $propertyAccessDefinition->setFactory([PropertyAccessor::class, 'createCache']);
                $propertyAccessDefinition->setArguments([null, 0, $version, new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);
                $propertyAccessDefinition->addTag('cache.pool', ['clearer' => 'cache.system_clearer']);
                $propertyAccessDefinition->addTag('monolog.logger', ['channel' => 'cache']);
            } else {
                $propertyAccessDefinition->setClass(ArrayAdapter::class);
                $propertyAccessDefinition->setArguments([0, false]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     * @param array $config Конфиг.
     *
     * @return bool Whether the configuration is enabled
     *
     */
    private function isConfigEnabled(ContainerBuilder $container, array $config): bool
    {
        if (!array_key_exists('enabled', $config)) {
            throw new InvalidArgumentException("The config array has no 'enabled' key.");
        }

        return (bool)$container->getParameterBag()->resolveValue($config['enabled']);
    }
}
