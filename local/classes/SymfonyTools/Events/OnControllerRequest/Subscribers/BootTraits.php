<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnControllerRequest\Subscribers\Traits\AbstractSubscriberTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class BootTraits
 * Bootable Traits.
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
class BootTraits implements EventSubscriberInterface, OnControllerRequestHandlerInterface
{
    use AbstractSubscriberTrait;

    /**
     * Обработчик события kernel.controller.
     *
     * Инициализация трэйтов контроллера. Вызов метода boot + название трэйта, если таковой существует.
     * (из Laravel)
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @since 10.09.2020
     * @since 19.09.2020 Добавлена инициализация трэйтов.
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $booted = [];

        foreach (class_uses_recursive($controller[0]) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($controller[0], $method)
                && !in_array($method, $booted, true)
            ) {
                forward_static_call([$controller[0], $method]);

                $booted[] = $method;
            }

            // Иницализация.
            // В трэйте должен существовать метод вида initialize<имя трэйта>
            if (method_exists($trait, $method = 'initialize' . class_basename($trait))) {
                $this->{$method}();
            }
        }
    }
}
