<?php

namespace Local\Bundles\BitrixUtilsBundle\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use RuntimeException;

/**
 * Class ModuleInitializer
 * @package Local\Bundles\BitrixUtilsBundle\Services
 *
 * @since 11.03.2021
 */
class ModuleInitializer
{
    /**
     * @var array $modules Модули к инициализации.
     */
    private $modules;

    /**
     * ModuleInitializer constructor.
     *
     * @param array $modules Модули к инициализации.
     */
    public function __construct(array $modules = [])
    {
        $this->modules = $modules;
    }

    /**
     * Инициализация запрошенных модулей.
     *
     * @return void
     * @throws LoaderException
     */
    public function init() : void
    {
        foreach ($this->modules as $module) {
            $this->install($module);
        }
    }

    /**
     * Инсталляция модуля.
     *
     * @param string $moduleId ID модуля.
     *
     * @return boolean
     * @throws LoaderException
     */
    public function install(string $moduleId): bool
    {
        if (ModuleManager::isModuleInstalled($moduleId)) {
            return true;
        }

        ModuleManager::registerModule($moduleId);

        if (!Loader::includeModule($moduleId)) {
            throw new RuntimeException(
                'Инициализация модуля .' . $moduleId . ' не задалась. Подключите его в админке.'
            );
        }

        return true;
    }
}
