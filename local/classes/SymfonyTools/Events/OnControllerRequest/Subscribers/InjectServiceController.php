<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Exception;
use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractListenerTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class InjectServiceController
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class InjectServiceController implements OnControllerRequestHandlerInterface
{
    use AbstractListenerTrait;

    /**
     * InjectServiceController constructor.
     *
     * @param ContainerInterface $container Сервис-контейнер.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Если контроллер зарегистрирован как сервис - использовать его.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $controller = $event->getController();

        $action = '';
        if (is_array($controller)) {
            $controller = $controller[0];

            // Получение метода контроллера.
            $controllerParams = $event->getRequest()->attributes->get('_controller');

            // Если строка, то расщепить и получить action так.
            if (is_string($controllerParams)) {
                $params = explode('::', $controllerParams);
                $action = $params[1];
            }

            // Если массив, то воспользоваться уже готовым.
            // Иной способ инициализации роутов.
            if (is_array($controllerParams)) {
                $action = !empty($controllerParams[1]) ? $controllerParams[1] : '';
            }
        }

        // Если контроллер зарегистрирован как сервис - использовать его.
        $classController = get_class($controller);

        if ($this->container->has($classController)) {
            $controller = $this->container->get($classController);
            $event->setController([$controller, $action]);
        }
    }
}
