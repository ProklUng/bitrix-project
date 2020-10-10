<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Subscribers;

use Local\SymfonyTools\Events\OnKernelRequest\Interfaces\OnKernelRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractSubscriberKernelRequestTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SetSession
 * @package Local\SymfonyTools\Events\OnKernelRequest\Subscribers
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 */
class SetSession implements EventSubscriberInterface, OnKernelRequestHandlerInterface
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
        $request = $event->getRequest();

        $request->setSession(
            $this->container->get('session.instance')
        );
    }
}
