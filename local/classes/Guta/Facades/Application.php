<?php

namespace Local\Guta\Facades;

use Local\Guta\ServiceProvider\AbstractClasses\AbstractFacade;

class Application extends AbstractFacade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return '\Bitrix\Main\Application';
    }
}
