<?php

namespace Local\ServiceProvider;

use Local\ServiceProvider\CompilePasses\BaseAggregatedTaggedServicesPass;
use Local\ServiceProvider\CompilePasses\ContainerAwareCompilerPass;
use Local\ServiceProvider\CompilePasses\TwigExtensionTaggedServicesPass;
use Local\ServiceProvider\CompilePasses\ValidateServiceDefinitions;
use Local\ServiceProvider\PostLoadingPass\BootstrapServices;
use Local\ServiceProvider\PostLoadingPass\InitBitrixEvents;
use Local\ServiceProvider\PostLoadingPass\TwigExtensionApply;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Class CustomCompilePassBag
 * @package Local\ServiceProvider
 *
 * @since 28.09.2020
 */
class CustomCompilePassBag
{
    /**
     * @var array $compilePassesBag Набор Compiler Passes.
     */
    private $compilePassesBag = [
        // Автозагрузка сервисов.
        [
            'pass' => BaseAggregatedTaggedServicesPass::class,
            'params' => [
                'service.bootstrap',
                '_bootstrap'
            ]
        ],
        // Инициализация событий через сервисные тэги.
        [
            'pass' => BaseAggregatedTaggedServicesPass::class,
            'params' =>
                ['bitrix.events.init', '_events'],
        ],

        // Проверка классов сервисов на существование.
        [
            'pass' => ValidateServiceDefinitions::class,
            'phase' => PassConfig::TYPE_BEFORE_REMOVING
        ],

        // Автоматическая инжекция контейнера в сервисы, имплементирующие ContainerAwareInterface.
        [
            'pass' => ContainerAwareCompilerPass::class
        ],

        // Регистрация Twig extensions.
        [
            'pass' => TwigExtensionTaggedServicesPass::class
        ],
    ];

    /**
     * @var string[] $postLoadingPassesBag Пост-обработчики (PostLoadingPass) контейнера.
     */
    private $postLoadingPassesBag = [
        ['pass' => InitBitrixEvents::class, 'priority' => 10],
        ['pass' => BootstrapServices::class, 'priority' => 20],
        ['pass' => TwigExtensionApply::class, 'priority' => 20],
    ];

    /**
     * Compiler Passes.
     *
     * @return array|array[]
     */
    public function getCompilerPassesBag() : array
    {
        return $this->compilePassesBag;
    }

    /**
     * PostLoadingPasses.
     *
     * @return array[]|string[]
     */
    public function getPostLoadingPassesBag() : array
    {
        return $this->postLoadingPassesBag;
    }
}
