<?php

namespace Local\ServiceProvider\Micro;

use Local\ServiceProvider\ServiceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ControllerArgumentValueResolverPass;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass;
use Symfony\Component\PropertyInfo\DependencyInjection\PropertyInfoPass;
use Symfony\Component\Routing\DependencyInjection\RoutingResolverPass;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;

/**
 * Class AbstractStandaloneServiceProvider
 * @package Local\ServiceProvider\Micro
 *
 * @since 04.03.2021
 */
class AbstractStandaloneServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    protected function registerFrameworkExtensions() : void
    {
    }

    /**
     * @inheritDoc
     */
    protected function standartSymfonyPasses(): void
    {
        // Пассы Symfony.
        $standartCompilerPasses = [
            [
                'pass' => ControllerArgumentValueResolverPass::class,
            ],
            [
                'pass' => RegisterControllerArgumentLocatorsPass::class,
            ],
            [
                'pass' => RoutingResolverPass::class,
            ],
            [
                'pass' => SerializerPass::class,
            ],
            [
                'pass' => PropertyInfoPass::class,
            ],
        ];

        static::$containerBuilder->registerForAutoconfiguration(AbstractController::class)
            ->addTag('controller.service_arguments');

        static::$containerBuilder->registerForAutoconfiguration(ArgumentValueResolverInterface::class)
            ->addTag('controller.argument_value_resolver');

        static::$containerBuilder->registerForAutoconfiguration(ServiceLocator::class)
            ->addTag('container.service_locator');

        static::$containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('kernel.event_subscriber');

        // Применяем compiler passes.
        foreach ($standartCompilerPasses as $pass) {
            static::$containerBuilder->addCompilerPass(
                new $pass['pass'],
                $pass['phase'] ?? PassConfig::TYPE_BEFORE_OPTIMIZATION
            );
        }
    }
}
