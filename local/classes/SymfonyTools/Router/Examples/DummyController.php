<?php

namespace Local\SymfonyTools\Router\Examples;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DummyController
 * @package Local\Router\Examples
 *
 * @since 07.09.2020
 */
class DummyController
{
    public function action(
        Request $request
    )
    {
        return new Response('OK');
    }

    public function loadAction(
        Request $request
    )
    {
        return new Response('OK');
    }
}
