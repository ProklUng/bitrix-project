<?php

namespace Local\ServiceProvider\CompilePasses;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AggregatedTaggedServicesPass.
 * Compile pass для обработки сервисов с тэгом service.bootstrap.
 * @package Local\ServiceProvider\CompilePasses
 *
 * @since 21.09.2020 ID сервисов пробрасываются в параметры, чтобы их можно было
 * запускать в случае компилированного контейнера.
 */
class AggregatedTaggedServicesPass implements CompilerPassInterface
{
    /** @const string TAG_BOOTSTRAP_SERVICES Тэг сервисов запускающихся при загрузке. */
    protected const TAG_BOOTSTRAP_SERVICES = 'service.bootstrap';

    /**
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(
            self::TAG_BOOTSTRAP_SERVICES
        );

        // Сервисы автозапуска.
        $container->setParameter(
            '_bootstrap',
            $taggedServices
        );

        foreach ($taggedServices as $id => $tags) {
            $container->get($id);
        }
    }
}
