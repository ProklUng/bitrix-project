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
     * @since 11.10.2020 Переработка.
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $booted = [];

        foreach (class_uses_recursive($controller[0]) as $trait) {
            // Загрузка (статический метод).
            $method = 'boot' . class_basename($trait);

            if ($this->methodExist($controller[0], $method, $booted)) {
                forward_static_call([$controller[0], $method]);
                $booted[] = $method;
            }

            // Инициализация (динамический метод).
            $method = 'initialize' . class_basename($trait);

            if ($this->methodExist($controller[0], $method, $booted)) {
                $controller[0]->{$method}();

                $booted[] = $method;
            }
        }
    }

    /**
     * Существует ли метод и был ли он уже загружен.
     *
     * @param mixed  $class  Класс.
     * @param string $method Метод.
     * @param array  $booted Загруженные методы трэйтов.
     *
     * @since 11.10.2020
     *
     * @return boolean
     */
    private function methodExist($class, string $method, array $booted = []) : bool
    {
        return method_exists($class, $method)
            && !in_array($method, $booted, true);
    }
}
