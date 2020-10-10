<?php

namespace Local\SymfonyTools\Events\Exceptions;

use Local\SymfonyTools\Framework\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * Class InvalidAjaxCallException
 * Исключения классов пространства имен Events.
 * @package Local\SymfonyTools\Events\Exceptions
 *
 * @sine 10.09.2020
 */
class InvalidAjaxCallException extends BaseException implements RequestExceptionInterface
{

}
