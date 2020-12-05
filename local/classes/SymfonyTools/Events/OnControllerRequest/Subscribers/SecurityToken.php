<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Exception;
use Local\Controllers\Traits\ValidatorTraits\SecurityTokenTrait;
use Local\SymfonyTools\Events\Exceptions\WrongSecurityTokenException;
use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnControllerRequest\Subscribers\Traits\AbstractSubscriberTrait;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class SecurityToken
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class SecurityToken implements OnControllerRequestHandlerInterface
{
    use AbstractSubscriberTrait;

    /**
     * Обработчик события kernel.controller.
     *
     * Валидация токена при наличии трэйта SecurityTokenTrait в контроллере.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @throws WrongSecurityTokenException Ошибки по токену.
     * @throws Exception                   Ошибки сервис-контейнера.
     */
    public function handle(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->useTrait($event, SecurityTokenTrait::class)) {
            return;
        }

        if (!$this->container->get('security.csrf.token_manager')) {
            throw new WrongSecurityTokenException('security.csrf.token_manager not installed.');
        }

        if (empty($token = $event->getRequest()->request->get('security.token'))) {
            throw new WrongSecurityTokenException('Secirity error: empty token.');
        }

        // Валидировать токен, для примера, так.
        $bValidToken = $this->container->get('security.csrf.token_manager')->isTokenValid(
            new CsrfToken('app', $token)
        );

        if (!$bValidToken) {
            throw new WrongSecurityTokenException('Security error: Invalid security token.');
        }
    }
}
