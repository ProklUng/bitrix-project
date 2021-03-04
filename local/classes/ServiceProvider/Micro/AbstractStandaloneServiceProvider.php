<?php

namespace Local\ServiceProvider\Micro;

use Local\ServiceProvider\ServiceProvider;

/**
 * Class AbstractStandaloneServiceProvider
 *
 * Абстракция для наследования отдельных микро-сервиспровайдеров.
 *
 * @package Local\ServiceProvider\Micro
 *
 * @since 04.03.2021
 */
class AbstractStandaloneServiceProvider extends ServiceProvider
{
    protected function registerFrameworkExtensions() : void
    {
    }

    protected function standartSymfonyPasses(): void
    {
    }
}
