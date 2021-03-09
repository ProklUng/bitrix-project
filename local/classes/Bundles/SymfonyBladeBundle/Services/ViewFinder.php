<?php

namespace Local\Bundles\SymfonyBladeBundle\Services;

use Illuminate\View\FileViewFinder;

/**
 * Class ViewFinder
 * @package Local\Bundles\SymfonyBladeBundle\Services
 */
class ViewFinder extends FileViewFinder
{
    /**
     * Setter for paths.
     *
     * @param array $paths Пути.
     *
     * @return void
     */
    public function setPaths($paths) : void
    {
        $this->paths = $paths;
    }
}
