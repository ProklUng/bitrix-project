<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnControllerRequest\Subscribers\Traits\AbstractSubscriberTrait;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class SetContainer
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
class SetContainer implements EventSubscriberInterface, OnControllerRequestHandlerInterface
{
    use AbstractSubscriberTrait;

    /**
     * @var ContainerInterface $container Сервис-контейнер.
     */
    private $container;

    /**
     * SetContainer constructor.
     *
     * @param ContainerInterface $container Сервис-контейнер.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Загнать сервис-контейнер в контроллер.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        // Только для контроллеров.
        if ($controller[0] instanceof AbstractController) {
            $controller[0]->setContainer($this->container);
        }
    }
}
