<?php

namespace Local\Services\Filesystem\Interfaces;

use League\Flysystem\AdapterInterface;

/**
 * Interface GutaFilesystemAdapterInterface
 * @package Local\Services\Interfaces
 */
interface GutaFilesystemAdapterInterface
{
    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface;
}
