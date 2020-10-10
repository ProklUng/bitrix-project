<?php

namespace Local\SymfonyTools\Router;

use InvalidArgumentException;
use Local\SymfonyTools\Router\Annotations\LoadAnnotatedRoutes;
use Local\SymfonyTools\Router\Exceptions\ArgumentsLoaderRoutesException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouterConfig
 * @package Local\Router
 *
 * @since 07.09.2020
 * @since 09.10.2020 Загрузка аннотированных роутов.
 */
class RouterConfig
{
    /**
     * @var RouteCollection $routeCollection Коллекция роутов.
     */
    private $routeCollection;

    /**
     * @var LoadAnnotatedRoutes $loadAnnotatedRoutes Загрузчик аннотированных роутов.
     */
    private $loadAnnotatedRoutes;

    /** @var string $documentRoot DOCUMENT_ROOT. */
    private $documentRoot;
    /** @var string $filename Yaml config. */
    private $filename;

    /**
     * RouterConfig constructor.
     *
     * @param string              $documentRoot        DOCUMENT_ROOT.
     * @param string              $filename            Yaml config.
     * @param LoadAnnotatedRoutes $loadAnnotatedRoutes Загрузчик аннотированных роутов.
     */
    public function __construct(
        string $documentRoot,
        string $filename,
        LoadAnnotatedRoutes $loadAnnotatedRoutes
    ) {
        $this->loadAnnotatedRoutes = $loadAnnotatedRoutes;
        $this->documentRoot = $documentRoot;
        $this->filename = $filename;

        $this->routeCollection = new RouteCollection();
    }

    /**
     * Загрузить маршруты.
     *
     * @return RouteCollection
     * @throws ArgumentsLoaderRoutesException
     */
    public function routes() : RouteCollection
    {
        $this->routeCollection = $this->load($this->documentRoot . $this->filename);

        return $this->routeCollection;
    }

    /**
     * Загрузить Yaml конфиг роутов.
     *
     * @param string $filename Yaml config.
     *
     * @return RouteCollection
     * @throws ArgumentsLoaderRoutesException
     *
     * @since 09.10.2020 Анотированные роуты.
     */
    private function load(string $filename): RouteCollection
    {
        $fileLocator = new FileLocator([$this->documentRoot]);
        $loader = new YamlFileLoader($fileLocator);

        try {
            $this->routeCollection =  $loader->load($filename);
        } catch (InvalidArgumentException $e) {
            throw new ArgumentsLoaderRoutesException('Symfony router error: ' . $e->getMessage());
        }

        // Анотированные роуты.
        $annotatedRoutes = $this->loadAnnotatedRoutes->load();

        // Если есть анотированные роуты, то добавить их в коллекцию.
        if ($annotatedRoutes->count() > 0) {
            $this->routeCollection->addCollection($annotatedRoutes);
        }

        return $this->routeCollection;
    }
}
