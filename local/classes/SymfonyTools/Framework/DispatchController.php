<?php

namespace Local\SymfonyTools\Framework;

use Exception;
use Local\SymfonyTools\Framework\Controllers\ErrorControllerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Local\SymfonyTools\Framework\Listeners\StringResponseListener;

/**
 * Class DispatchController
 * @package Local\SymfonyTools\Framework
 *
 * @since 05.09.2020
 * @since 07.09.2020 Light rewriting.
 * @since 11.09.2020 Упрощение.
 */
class DispatchController
{
    /**
     * @var Request $request Request.
     */
    private $request;

    /**
     * @var Response $response Response.
     */
    private $response;

    /**
     * @var EventDispatcherInterface $dispatcher Диспетчер событий.
     */
    private $dispatcher;

    /**
     * @var ControllerResolverInterface $controllerResolver Разрешитель контроллеров.
     */
    private $controllerResolver;

    /**
     * @var ErrorControllerInterface $errorController Error Controller.
     */
    private $errorController;

    /** @var array $defaultSubscribers Подписчики на события по умолчанию. */
    private $defaultSubscribers;

    /**
     * DispatchController constructor.
     *
     * @param EventDispatcherInterface    $dispatcher         Диспетчер событий.
     * @param ErrorControllerInterface    $errorController    Error controller.
     * @param ControllerResolverInterface $controllerResolver Разрешитель контроллеров.
     * @param Request|null                $request            Request.
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        ErrorControllerInterface $errorController,
        ControllerResolverInterface $controllerResolver,
        Request $request = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->controllerResolver = $controllerResolver;
        $this->errorController = $errorController;

        $this->request = $request ?? Request::createFromGlobals();

        // Подписчики на события по умолчанию.
        $this->defaultSubscribers = [
            new StringResponseListener(),
            new ErrorListener(
                [$this->errorController, 'exceptionAction']
            ),
            new ResponseListener('UTF-8')
        ];
    }

    /**
     * Исполнить контроллер.
     *
     * @param string|array $controllerAction Класс и метод контроллера.
     * Вида \Local\Handler::action. Или массив [класс, метод].
     *
     * @return boolean
     *
     * @since 06.09.2020 Small rewrite. Массив в качестве параметра.
     */
    public function dispatch(
        $controllerAction
    ): bool {
        // Задать контроллер
        $this->request->attributes->set(
            '_controller',
            $controllerAction
        );

        $this->addSubscribers($this->defaultSubscribers);

        $framework = new HttpKernel($this->dispatcher, $this->controllerResolver);

        try {
            $this->response = $framework->handle($this->request);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Заслать Response в браузер.
     *
     * @return boolean
     */
    public function send(): bool
    {
        if ($this->response) {
            $this->response->send();
            return false;
        }

        return true;
    }

    /**
     * Задать Request.
     *
     * @param Request $request Request.
     *
     * @return DispatchController
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Задать параметры Request.
     *
     * @param array $arParams Параметры (лягут в аттрибуты Request).
     *
     * @return DispatchController
     */
    public function setParams(array $arParams): self
    {
        $this->request->attributes->add($arParams);

        return $this;
    }

    /**
     * Задать дополнительного подписчика на события.
     *
     * @param mixed $listener
     *
     * @return $this
     *
     * @since 07.09.2020
     */
    public function addListener($listener) : self
    {
        if (is_object($listener)) {
            $this->defaultSubscribers[] = $listener;
        }

        return $this;
    }

    /**
     * Кучно добавить слушателей событий.
     *
     * @param array $subscribers
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
}
