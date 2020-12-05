<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Traits;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class AbstractListenerTrait
 * Общие методы для слушателей событий.
 * @package Local\Bundles\CustomArgumentResolverBundle\Event\Traits
 *
 * @since 05.12.2020
 */
trait AbstractListenerTrait
{
    /**
     * @var ContainerBuilder $container Сервис-контейнер.
     */
    private $container;

    /**
     * Инициализировать параметры. В данном случае контейнер.
     *
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function init(ContainerInterface $container) : self
    {
        $this->container = $container;

        return $this;
    }
}
