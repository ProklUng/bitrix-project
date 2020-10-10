<?php

namespace Local\SymfonyTools\Router;

use Exception;
use Local\SymfonyTools\Framework\Controllers\ErrorControllerInterface;
use Local\SymfonyTools\Framework\Listeners\StringResponseListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class InitRouter
 * @package Local\Router
 *
 * @since 07.09.2020
 * @since 09.09.2020 Проброс Error Controller снаружи.
 * @since 11.09.20202 Переработка.
 * @since 16.09.20202 Доработка. RequestContext.
 */
class InitRouter
{
    /**
     * @var Request $request Request.
     */
    private $request;

    /**
     * @var ErrorControllerInterface $errorController Error Controller.
     */
    private $errorController;

    /**
     * @var EventDispatcherInterface $dispatcher Диспетчер событий.
     */
    private $dispatcher;

    /**
     * @var ControllerResolverInterface $controllerResolver Разрешитель контроллеров.
     */
    private $controllerResolver;

    /** @var array $defaultSubscribers Подписчики на события по умолчанию. */
    private $defaultSubscribers;

    /**
     * InitRouter constructor.
     *
     * @param RouteCollection          $routeCollection Коллекция роутов.
     * @param ErrorControllerInterface $errorController Error controller.
     * @param EventDispatcher          $dispatcher      Event dispatcher.
     * @param Request|null             $request         Request.
     *
     * @since 16.09.2020 Инициализация RequestContext.
     */
    public function __construct(
        RouteCollection $routeCollection,
        ErrorControllerInterface $errorController,
        EventDispatcher $dispatcher,
        Request $request = null
    ) {
        $this->request = $request ?? Request::createFromGlobals();
        $this->errorController = $errorController;
        $this->dispatcher = $dispatcher;
        $this->controllerResolver = new ControllerResolver();

        // RequestContext init.
        $requestContext = new RequestContext();
        $requestContext->fromRequest($this->request);

        $matcher = new UrlMatcher($routeCollection, $requestContext);
        // Подписчики на события по умолчанию.
        $this->defaultSubscribers = [
            new RouterListener($matcher, new RequestStack()),
            new StringResponseListener(),
            new ErrorListener(
                [$this->errorController, 'exceptionAction']
            ),
            new ResponseListener('UTF-8')
        ];

        $this->addSubscribers($this->defaultSubscribers);
    }

    /**
     * Процесс обработки роутов.
     *
     * @return void
     * @throws Exception Ошибки роутера.
     */
    public function handle(): void
    {
        // Setup framework kernel
        $framework = new HttpKernel($this->dispatcher, $this->controllerResolver);

        try {
            $response = $framework->handle($this->request);
        } catch (Exception $e) {
            return;
        }

        // Handle if no route match found
        if ($response->getStatusCode() === 404) {
            // If no route found do noting and let continue.
            return;
        }

        // Send the response to the browser and exit app.
        $response->send();

        exit;
    }

    /**
     * Кучно добавить слушателей событий.
     *
     * @param array $subscribers Подписчики.
     *
     * @return void
     */
    private function addSubscribers(array $subscribers = []) : void
    {
        foreach ($subscribers as $subscriber) {
            if (!is_object($subscriber)) {
                continue;
            }
            $this->dispatcher->addSubscriber($subscriber);
        }
    }

    /**
     * Задать Request.
     *
     * @param Request $request Request.
     *
     * @return InitRouter
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }
}
