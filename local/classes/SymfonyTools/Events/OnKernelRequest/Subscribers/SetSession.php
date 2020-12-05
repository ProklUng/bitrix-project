<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Subscribers;

use Local\SymfonyTools\Events\OnKernelRequest\Interfaces\OnKernelRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractSubscriberKernelRequestTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SetSession
 * @package Local\SymfonyTools\Events\OnKernelRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class SetSession implements OnKernelRequestHandlerInterface
{
    use AbstractSubscriberKernelRequestTrait;

    /**
     * @var ContainerInterface $container Сервис-контейнер.
     */
    private $container;

    /**
     * SetContainer constructor.
     *
     * @param ContainerInterface $container Сервис-контейнер.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Событие kernel.request.
     *
     * Установить сессию Symfony для всех запросов к контроллерам.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     *
     * @since 10.09.2020
     */
    public function handle(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $request->setSession(
            $this->container->get('session.instance')
        );
    }
}
