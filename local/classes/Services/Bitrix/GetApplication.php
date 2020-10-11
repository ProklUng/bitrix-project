<?php

namespace Local\Services\Bitrix;

use CMain;

/**
 * Class GetApplication
 * @package Local\Services\Bitrix
 */
class GetApplication
{
    public function instance() : CMain
    {
        global $APPLICATION;

        return $APPLICATION;
    }
}