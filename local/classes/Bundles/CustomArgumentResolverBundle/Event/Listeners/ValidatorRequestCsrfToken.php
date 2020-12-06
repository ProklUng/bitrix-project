<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Event\Listeners;

use Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions\WrongCsrfException;
use Local\Bundles\CustomArgumentResolverBundle\Event\Interfaces\OnKernelRequestHandlerInterface;
use Local\Bundles\CustomArgumentResolverBundle\Service\Utils\CsrfRequestHandler;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class ValidatorRequestCsrfToken
 * @package Local\Bundles\CustomArgumentResolverBundle\Event\Listeners
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 24.09.2020 Рефакторинг.
 * @since 04.12.2020 Параметры контейнера пробрасываются снаружи.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class ValidatorRequestCsrfToken implements OnKernelRequestHandlerInterface
{
    use ContainerAwareTrait;

    /**
     * @var ParameterBagInterface $parameterBag Параметры контейнера.
     */
    private $parameterBag;

    /**
     * ValidatorRequestCsrfToken constructor.
     *
     * @param ParameterBagInterface $parameterBag Параметры контейнера.
     */
    public function __construct(
        ParameterBagInterface $parameterBag
    ) {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Событие kernel.request.
     *
     * Проверка - при необходимости Csrf токена.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     * @throws WrongCsrfException Ошибки проверки CSRF токена.
     *
     * @since 10.09.2020
     */
    public function handle(RequestEvent $event): void
    {
        $event->getRequest()->attributes->set('security.token.validated', false);

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $csrfRequestHandler = new CsrfRequestHandler(
            $request,
            $this->container,
            $this->parameterBag
        );

        $csrfRequestHandler->validateCsrfToken();

        $event->getRequest()->attributes->set('security.token.validated', true);
    }
}
