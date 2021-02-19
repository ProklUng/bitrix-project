<?php

namespace Local\Bundles\BitrixOgGraphBundle\Services\Utils;

use CFile;

/**
 * Class CFileWrapper
 * @package Local\Bundles\BitrixOgGraphBundle\Services\Utils
 */
class CFileWrapper
{
    /**
     * @var CFile
     */
    protected $file;

    /**
     * CFileWrapper constructor.
     *
     * @param CFile $file
     */
    public function __construct(
        CFile $file
    ) {
        $this->file = $file;
    }

    /**
     * Путь к файлу.
     *
     * @param integer $imageId
     *
     * @return string
     */
    public function path(int $imageId) : string
    {
        $result =  $this->file::GetPath($imageId);

        return $result ?? '';
    }
}
