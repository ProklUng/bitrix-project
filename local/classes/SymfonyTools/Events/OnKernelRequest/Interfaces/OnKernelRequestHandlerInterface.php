<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Interfaces;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Interface OnControllerRequestHandlerInterface
 * @package Local\SymfonyTools\Events\OnControllerRequest\Interfaces
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
interface OnKernelRequestHandlerInterface
{
    /**
     * Обработчик события kernel.request.
     *
     * @param RequestEvent $event Объект события.
     */
    public function handle(RequestEvent $event): void;
}
