<?php

namespace Local\SymfonyTools\Events\Exceptions;

use Local\SymfonyTools\Framework\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * Class WrongSecurityTokenException
 * Исключения классов пространства имен Events.
 * @package Local\SymfonyTools\Events\Exceptions
 *
 * @sine 10.09.2020
 */
class WrongSecurityTokenException extends BaseException implements RequestExceptionInterface
{

}
