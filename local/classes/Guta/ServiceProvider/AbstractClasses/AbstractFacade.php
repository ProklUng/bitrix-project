<?php

namespace Local\Guta\ServiceProvider\AbstractClasses;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * Class AbstractFacade
 * @package Local\Guta\ServiceProvider\AbstractClasses
 */
abstract class AbstractFacade extends BaseFacade
{
    /**
     * @inheritDoc
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        $class = static::getFacadeAccessor();

        return static::$resolvedInstance[$name] = containerLaravel()->make($class);
    }
}
