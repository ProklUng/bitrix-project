<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Subscribers;

use Local\SymfonyTools\Framework\Exceptions\WrongCsrfException;
use Local\SymfonyTools\Events\OnKernelRequest\Interfaces\OnKernelRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractSubscriberKernelRequestTrait;
use Local\SymfonyTools\Framework\Utils\CsrfRequestHandler;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class ValidatorRequestCsrfToken
 * @package Local\SymfonyTools\Events\OnKernelRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class ValidatorRequestCsrfToken implements OnKernelRequestHandlerInterface
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
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $csrfRequestHandler = new CsrfRequestHandler(
            $request,
            container()
        );

        $csrfRequestHandler->validateCsrfToken();
    }
}
