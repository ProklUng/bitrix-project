<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnControllerRequest\Subscribers\Traits\AbstractSubscriberTrait;
use Local\SymfonyTools\Framework\Exceptions\ArgumentsControllersException;
use Local\SymfonyTools\Framework\Interfaces\InjectorControllerInterface;
use Local\SymfonyTools\Framework\Utils\ControllerProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class ResolverParamsController
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 11.09.2020
 */
class InjectArgumentsToController implements EventSubscriberInterface, OnControllerRequestHandlerInterface
{
    use AbstractSubscriberTrait;

    /**
     * @var InjectorControllerInterface $controllerProcessor Обработчик контроллеров.
     */
    private $controllerProcessor;

    /**
     * InjectArgumentsToController constructor.
     *
     * @param ControllerProcessor $controllerProcessor Обработчик параметров контроллера.
     */
    public function __construct(
        ControllerProcessor $controllerProcessor
    ) {
        $this->controllerProcessor = $controllerProcessor;
    }

    /**
     * Разрешить параметры контроллера.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws ArgumentsControllersException Ошибки аргументов контроллера.
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        // Только для контроллеров.
        if ($controller[0] instanceof AbstractController) {
            $this->controllerProcessor->inject($event);
        }
    }
}
