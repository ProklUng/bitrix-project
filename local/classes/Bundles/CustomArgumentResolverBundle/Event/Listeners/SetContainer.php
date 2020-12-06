<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Event\Listeners;

use Local\Bundles\CustomArgumentResolverBundle\Event\Interfaces\OnControllerRequestHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class SetContainer
 * @package Local\Bundles\CustomArgumentResolverBundle\Event\Listeners
 *
 * @since 10.09.2020
 * @since 11.09.2020 Упрощение.
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class SetContainer implements OnControllerRequestHandlerInterface
{
    use ContainerAwareTrait;

    /**
     * Загнать сервис-контейнер в контроллер.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @since 05.12.2020 Борьба с повторными запусками. Трэйт SupportCheckerCallResolverTrait.
     */
    public function handle(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!$event->isMasterRequest() || !is_array($controller)) {
            return;
        }

        // Только для контроллеров.
        if ($controller[0] instanceof AbstractController) {
            // Установить сервис-контейнер.
            $controller[0]->setContainer($this->container);
        }
    }
}
