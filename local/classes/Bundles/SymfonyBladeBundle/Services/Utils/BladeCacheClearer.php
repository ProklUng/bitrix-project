<?php

namespace Local\Bundles\SymfonyBladeBundle\Services\Utils;

use Bitrix\Main\Application;

/**
 * Class BladeCacheClearer
 * @package Local\Bundles\SymfonyBladeBundle\Services\Utils
 *
 * @since 09.03.2021
 */
class BladeCacheClearer
{
    /**
     * @var string $cachePath Путь к кэшу.
     */
    private $cachePath;

    /**
     * BladeCacheClearer constructor.
     *
     * @param string $cachePath Путь к кэшу.
     */
    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    /**
     * Движуха.
     * @return void
     */
    public function clear() : void
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $clearCache = htmlspecialchars($request->getQuery('clear_cache'));
        if ($clearCache === 'Y') {
            $this->rmdirRecursive($this->cachePath);
        }
    }

    /**
     * Рекурсивно удалить директорию.
     *
     * @param string $dir Директория.
     *
     * @return void
     */
    private function rmdirRecursive(string $dir) : void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$object)) {
                        $this->rmdirRecursive($dir.DIRECTORY_SEPARATOR.$object);
                    } else {
                        unlink($dir.DIRECTORY_SEPARATOR.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
