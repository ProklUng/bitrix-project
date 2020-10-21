<?php

namespace Local\Util\IblockPropertyType\Exception;

use Exception;
use Throwable;

class UnsupportedControlModeException extends Exception
{
    /**
     * UnsupportedControlModeException constructor.
     *
     * @param string         $controlMode
     * @param integer        $code
     * @param Throwable|null $previous
     */
    public function __construct($controlMode, $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            'Unsupported control mode `%s` or name-key pair could not be recognized.',
            $controlMode
        );
        parent::__construct($message, $code, $previous);
    }
}
