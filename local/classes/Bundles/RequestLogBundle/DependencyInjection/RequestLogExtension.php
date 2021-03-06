<?php

namespace Local\Bundles\RequestLogBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class RequestLogExtension
 * @package Local\Bundles\RequestLog\DependencyInjection
 *
 * @since 06.03.2021
 */
final class RequestLogExtension extends Extension
{
    private const DIR_CONFIG = '/../Resources/config';

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('mroca_request_log.mocks_dir', $config['mocks_dir']);
        $container->setParameter('mroca_request_log.mocks_dir_commands', $config['mocks_dir_commands']);
        $container->setParameter('mroca_request_log.hash_query_params', $config['hash_query_params']);
        $container->setParameter('mroca_request_log.use_indexed_associative_array', $config['use_indexed_associative_array']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . self::DIR_CONFIG)
        );

        $loader->load('services.yaml');
    }

    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return 'request_log';
    }
}
