<?php

namespace Local\SymfonyTools\Events\OnControllerRequest\Subscribers;

use Local\Controllers\Traits\ValidatorTraits\BitrixSecurityTokenTrait;
use Local\SymfonyTools\Events\Exceptions\WrongSecurityTokenException;
use Local\SymfonyTools\Events\OnControllerRequest\Interfaces\OnControllerRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractListenerTrait;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\UseTraitChecker;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class CheckBitrixToken
 * @package Local\SymfonyTools\Events\OnControllerRequest\Subscribers
 *
 * @since 11.09.2020
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class CheckBitrixToken implements OnControllerRequestHandlerInterface
{
    use AbstractListenerTrait;
    use UseTraitChecker;

    /**
     * Обработчик события kernel.controller.
     *
     * Валидация токена Битрикс при наличии трэйта BitrixSecurityTokenTrait в контроллере.
     * Предполагается, что токен прилетит в POST запросе, поле - sessid.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @throws WrongSecurityTokenException Невалидный токен.
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller) || !$event->isMasterRequest()) {
            return;
        }

        /**
         * needCheckToken() -> BitrixSecurityTokenTrait.
         */
        if (!$this->useTrait($event, BitrixSecurityTokenTrait::class)
            ||
            (is_object($controller[0]) && !$controller[0]->needCheckToken())
        ) {
            return;
        }

        $token = $event->getRequest()->request->get('sessid');

        if (empty($token)
            ||
            !check_bitrix_sessid()
        ) {
            throw new WrongSecurityTokenException('Secirity error: invalid Bitrix token.');
        }
    }
}
