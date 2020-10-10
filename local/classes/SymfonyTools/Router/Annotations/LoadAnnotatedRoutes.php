<?php

namespace Local\SymfonyTools\Router\Annotations;

use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class LoadAnnotatedRoutes
 * @package Local\SymfonyTools\Router\Annotations
 *
 * @since 09.10.2020
 */
class LoadAnnotatedRoutes
{
    /**
     * @var AnnotatedRouteControllerLoader $reader Читалка анотированных роутов.
     */
    private $reader;

    /**
     * @var RouteCollection $routeCollection Коллекция роутов.
     */
    private $routeCollection;

    /** @var array $classes Классы. */
    private $classes;

    /**
     * LoadAnnotatedRoutes constructor.
     *
     * @param SearchAnnotatedClasses         $searchAnnotatedClasses Поисковик классов в директориях.
     * @param AnnotatedRouteControllerLoader $reader                 Читалка анотированных роутов.
     */
    public function __construct(
        SearchAnnotatedClasses $searchAnnotatedClasses,
        AnnotatedRouteControllerLoader $reader
    ) {
        $this->reader = $reader;
        $this->routeCollection = new RouteCollection();
        $this->classes = $searchAnnotatedClasses->collect();
    }

    /**
     * Загрузить все анотированные классы в RouteCollection.
     *
     * @return RouteCollection
     */
    public function load() : RouteCollection
    {
        foreach ($this->classes as $class) {
            $this->routeCollection->addCollection(
                $this->reader->load($class)
            );
        }

        return $this->routeCollection;
    }
}
