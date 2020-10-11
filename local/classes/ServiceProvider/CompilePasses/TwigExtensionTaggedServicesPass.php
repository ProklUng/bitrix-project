<?php

namespace Local\ServiceProvider\CompilePasses;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class TwigExtensionTaggedServicesPass
 * Обработка сервисов с тэгом twig.extension.
 * @package Local\ServiceProvider\CompilePasses
 *
 * @since 11.10.2020
 */
class TwigExtensionTaggedServicesPass implements CompilerPassInterface
{
    /** @const string TAG_TWIG_EXTENSION Тэг сервисов, расширяющих Twig. */
    protected const TAG_TWIG_EXTENSION = 'twig.extension';

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     * @throws Exception
     */
    public function process(ContainerBuilder $container) : void
    {
        $taggedServices = $container->findTaggedServiceIds(
            self::TAG_TWIG_EXTENSION
        );

        // Сервисы автозапуска.
        $container->setParameter(
            '_twig_extension',
            $taggedServices
        );
    }
}
