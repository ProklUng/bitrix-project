<?php
declare(strict_types=1);

namespace Local\Bundles\SymfonyMiddlewareBundle\Route;

use Illuminate\Support\Facades\Route;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteFetcher
 * @package Local\Bundles\SymfonyMiddlewareBundle\Route
 */
class RouteFetcher
{
    /**
     * @var RequestStack $requestStack RequestStack.
     */
    private $requestStack;

    /**
     * RouteFetcher constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    /**
     * @param Router|RouteCollection $routeCollection Коллекция роутов.
     *
     * @return RouteWrapper
     */
    public function fetchCurrentRoute($routeCollection): RouteWrapper
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return new RouteWrapper(null, null);
        }

        $routeName = $request->get('_route');

        if (!is_string($routeName)) {
            return new RouteWrapper(null, null);
        }

        if ($routeCollection instanceof Router) {
            $routeCollection = $routeCollection->getRouteCollection();
        }

        $route = $routeCollection->get($routeName);

        return new RouteWrapper($route, $routeName);
    }
}
