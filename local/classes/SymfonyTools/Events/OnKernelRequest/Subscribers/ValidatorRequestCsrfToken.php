<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Subscribers;

use Local\SymfonyTools\Framework\Exceptions\WrongCsrfException;
use Local\SymfonyTools\Events\OnKernelRequest\Interfaces\OnKernelRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractSubscriberKernelRequestTrait;
use Local\SymfonyTools\Framework\Utils\CsrfRequestHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class ValidatorRequestCsrfToken
 * @package Local\SymfonyTools\Events\OnKernelRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
class ValidatorRequestCsrfToken implements EventSubscriberInterface, OnKernelRequestHandlerInterface
{
    use AbstractSubscriberKernelRequestTrait;

    /**
     * Событие kernel.request.
     *
     * Проверка - при необходимости Csrf токена.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     * @throws WrongCsrfException
     *
     * @since 10.09.2020
     */
    public function handle(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $csrfRequestHandler = new CsrfRequestHandler(
            $request,
            container()
        );

        $csrfRequestHandler->validateCsrfToken();
    }
}
