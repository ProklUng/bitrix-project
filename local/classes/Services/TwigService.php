<?php

namespace Local\Services;

use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Class TwigService
 * @package Local\Services
 *
 * @since 07.09.2020
 */
class TwigService
{
    /**
     * @var Twig_Environment
     */
    private $twigEnvironment;

    /**
     * TwigService constructor.
     *
     * @param Twig_Loader_Filesystem $loader    Загрузчик.
     * @param string                 $debug     Среда.
     * @param string                 $cachePath Путь к кэшу (серверный).
     */
    public function __construct(
        Twig_Loader_Filesystem $loader,
        string $debug,
        string $cachePath
    ) {
        $this->twigEnvironment = new Twig_Environment(
            $loader,
            [
                'debug' => (bool)$debug,
                'cache' => $cachePath,
            ]
        );
    }

    /**
     * Инстанс Твига.
     *
     * @return Twig_Environment
     */
    public function instance() : Twig_Environment
    {
        return $this->twigEnvironment;
    }
}
