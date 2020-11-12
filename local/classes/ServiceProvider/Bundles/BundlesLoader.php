<?php

namespace Local\ServiceProvider\Bundles;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\MergeExtensionConfigurationPass;

/**
 * Class BundlesLoader
 * @package Local\ServiceProvider\Bundles
 * Загрузчик бандлов.
 *
 * @since 24.10.2020
 * @since 08.11.2020 Устранение ошибки, связанной с многократной загрузкой конфигурации бандлов.
 */
class BundlesLoader
{
    /** @const string PATH_BUNDLES_CONFIG Путь к конфигурационному файлу. */
    private const PATH_BUNDLES_CONFIG = '/local/configs/standalone_bundles.php';

    /**
     * @var ContainerBuilder $container Контейнер.
     */
    private $container;

    /**
     * @var array Конфигурация бандлов.
     */
    private $bundles = [];

    /**
     * @var array $bundlesMap Инициализированные классы бандлов.
     */
    private static $bundlesMap = [];

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
        foreach ($this->bundles as $bundleClass => $envs) {
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
                $this->container->registerExtension($extension);
                $bundle->build($this->container);

                // Сохраняю инстанцированный бандл в статику.
                self::$bundlesMap[$bundle->getName()] = $bundle;
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Bundle %s dont have implemented getContainerExtension method.',
                        $bundle->getName()
                    )
                );
            }
        }
    }

    /**
     * Регистрация extensions.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    public function registerExtensions(ContainerBuilder $container) : void
    {
        // Extensions in container.
        $extensions = [];
        foreach ($container->getExtensions() as $extension) {
            $extensions[] = $extension->getAlias();
        }

        // ensure these extensions are implicitly loaded
        $container->getCompilerPassConfig()
            ->setMergePass(
                new MergeExtensionConfigurationPass($extensions)
            );
    }

    /**
     * Boot bundles.
     *
     * @param ContainerInterface $container Контейнер.
     *
     * @return void
     *
     * @since 11.11.2020
     */
    public function boot(ContainerInterface $container) : void
    {
        /**
         * @var Bundle $bundle
         */
        foreach (self::$bundlesMap as $bundle) {
            $bundle->setContainer($container);
            $bundle->boot();
        }
    }

    /**
     * Бандлы.
     *
     * @return array
     */
    public function bundles() : array
    {
        return self::$bundlesMap;
    }

    /**
     * Инстанцы бандлов.
     *
     * @return array
     */
    public static function getBundlesMap() : array
    {
        return self::$bundlesMap;
    }
}
