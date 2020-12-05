<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Exception;
use Local\Controllers\Traits\ValidatorTraits\SecurityAjaxCallTrait;
use Local\SymfonyTools\Events\Exceptions\InvalidAjaxCallException;
use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractListenerTrait;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\UseTraitChecker;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class AjaxCall
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class AjaxCall implements OnControllerRequestHandlerInterface
{
    use AbstractListenerTrait;
    use UseTraitChecker;

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
        if (!$event->isMasterRequest() || !$this->useTrait($event, SecurityAjaxCallTrait::class)) {
            return;
        }

        if (!$event->getRequest()->isXmlHttpRequest()) {
            throw new InvalidAjaxCallException('Invalid type call.');
        }
    }
}
