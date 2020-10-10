<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Exception;
use Local\Controllers\Traits\ValidatorTraits\SecurityAjaxCallTrait;
use Local\SymfonyTools\Events\Exceptions\InvalidAjaxCallException;
use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnControllerRequest\Subscribers\Traits\AbstractSubscriberTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class AjaxCall
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
class AjaxCall implements EventSubscriberInterface, OnControllerRequestHandlerInterface
{
    use AbstractSubscriberTrait;

    /**
     * Обработчик события kernel.controller.
     *
     * Проверка на вызов AJAX.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(ControllerEvent $event): void
    {
        if (!$this->useTrait($event, SecurityAjaxCallTrait::class)) {
            return;
        }

        if (!$event->getRequest()->isXmlHttpRequest()) {
            throw new InvalidAjaxCallException('Invalid type call.');
        }
    }
}
