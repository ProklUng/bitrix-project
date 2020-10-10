<?php

namespace Local\Guta\Facades;

use Local\Guta\ServiceProvider\AbstractClasses\AbstractFacade;

class CUser extends AbstractFacade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return 'CUser';
    }
}
