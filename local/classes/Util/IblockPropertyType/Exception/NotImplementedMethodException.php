<?php

namespace Local\Util\IblockPropertyType\Exception;

use Exception;
use Throwable;

class NotImplementedMethodException extends Exception
{
    /**
     * NotImplementedMethodException constructor.
     *
     * @param string $methodName
     * @param string $propertyTypeClassName
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($methodName, $propertyTypeClassName, $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            'Method %s::%s() not implemented! Implement it or remove from getCallbacksMapping()',
            $propertyTypeClassName,
            $methodName
        );
        parent::__construct($message, $code, $previous);
    }

}
