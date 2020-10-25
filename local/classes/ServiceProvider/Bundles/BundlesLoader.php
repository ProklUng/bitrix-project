<?php

namespace Fedy\SymfonyDI\Bundles;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BundlesLoader
 * @package Fedy\SymfonyDI\Bundles
 * Загрузчик бандлов.
 *
 * @since 24.10.2020
 * @since 25.10.2020 Доработка.
 */
class BundlesLoader
{
    private const PATH_BUNDLES_CONFIG = '/config/standalone_bundles.php';

    /**
     * @var ContainerBuilder $container Контейнер.
     */
    private $container;

    /**
     * @var array Конфигурация бандлов.
     */
    private $bundles = [];

    /**
     * BundlesLoader constructor.
     *
     * @param ContainerBuilder $container  Контейнер в стадии формирования.
     * @param string|null      $configPath Путь к bundles.php (конфигурация бандлов).
     */
    public function __construct(
        ContainerBuilder $container,
        string $configPath = null
    ) {
        $configPath = $configPath ?? self::PATH_BUNDLES_CONFIG;

        if (@file_exists($_SERVER['DOCUMENT_ROOT'] . $configPath)) {
            $this->bundles = require $_SERVER['DOCUMENT_ROOT'] . $configPath;
        }

        $this->container = $container;
    }

    /**
     * Инициализация бандлов.
     *
     * @return void
     *
     * @throws InvalidArgumentException Не найден класс бандла.
     */
    public function load() : void
    {
        foreach ($this->bundles as $bundleClass => $data) {
            if (!class_exists($bundleClass)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Bundle class %s not exist.',
                        $bundleClass
                    )
                );
            }

            /**
             * @var Bundle $bundle Бандл.
             */
            $bundle = new $bundleClass;

            $extension = $bundle->getContainerExtension();
            if ($extension !== null) {
                $bundle->boot();
                $config = $this->loadYmlConfig($extension->getAlias());
                $extension->load($config, $this->container);
                $bundle->build($this->container);
            }
        }
    }

    /**
     * Загрузить Yaml конфиг бандла.
     *
     * @param string $sectionConfig Alias extension.
     *
     * @return array
     *
     */
    private function loadYmlConfig(string $sectionConfig) : array
    {
        try {
            return Yaml::parseFile($_SERVER['DOCUMENT_ROOT'] . '/local/configs/packages/' . $sectionConfig . '.yaml');
        } catch (ParseException $e) {
            return [];
        }
    }
}
