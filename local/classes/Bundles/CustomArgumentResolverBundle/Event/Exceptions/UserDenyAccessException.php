<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions;

use Local\Bundles\CustomArgumentResolverBundle\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

/**
 * Class UserDenyAccessException
 * Исключения классов пространства имен Events.
 * @package Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions
 *
 * @sine 18.02.2021
 */
class UserDenyAccessException extends BaseException implements RequestExceptionInterface
{

}
