<?php

namespace Local\SymfonyTools\Events\Exceptions;

use Local\SymfonyTools\Framework\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * Class WrongCsrfException
 * Исключения классов пространства имен Events.
 * @package Local\SymfonyTools\Events\Exceptions
 *
 * @sinсe 05.09.2020
 * @since 10.09.2020 Implement RequestExceptionInterface.
 */
class WrongCsrfException extends BaseException implements RequestExceptionInterface
{

}
