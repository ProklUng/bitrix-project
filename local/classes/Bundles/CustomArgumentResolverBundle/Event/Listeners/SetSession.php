<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Event\Listeners;

use Local\Bundles\CustomArgumentResolverBundle\Event\Interfaces\OnKernelRequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SetSession
 * @package Local\Bundles\CustomArgumentResolverBundle\Event\Listeners
 *
 * @since 10.09.2020
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class SetSession implements OnKernelRequestHandlerInterface
{
    use ContainerAwareTrait;

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

        $service = $this->container->get('symfony.session');
        if ($service !== null) {
            $request->setSession(
                $service->session()
            );
        }
    }
}
