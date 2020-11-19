<?php

namespace Local\ServiceProvider\CompilePasses;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MakePrivateEventsPublic
 * Сделать все приватные подписчики событий публичными.
 * @package Local\ServiceProvider\CompilePasses
 *
 * @since 19.11.2020
 */
class MakePrivateEventsPublic implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(
            'kernel.event_subscriber'
        );

        foreach ($taggedServices as $id => $service) {
            $def = $container->getDefinition($id);
            $def->setPublic(true);
        }
    }
}
