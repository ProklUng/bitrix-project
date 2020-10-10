<?php

namespace Local\Guta\Facades;

use Local\Guta\ServiceProvider\AbstractClasses\AbstractFacade;

class CMain extends AbstractFacade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return 'CMain';
    }
}
