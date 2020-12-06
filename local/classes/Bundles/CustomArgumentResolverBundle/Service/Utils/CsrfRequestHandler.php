<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Service\Utils;

use Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions\WrongCsrfException;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class CsrfRequestHandler
 * @package Local\SymfonyTools\Framework\Utils
 *
 * @since 05.09.2020
 * @since 04.12.2020 Параметры контейнера пробрасываются снаружи.
 */
class CsrfRequestHandler
{
    /**
     * @var Request $request Запрос.
     */
    private $request;

    /**
     * @var ContainerInterface $container Контейнер.
     */
    private $container;

    /**
     * @var ParameterBagInterface $parameterBag Параметры контейнера.
     */
    private $parameterBag;

    /**
     * CsrfRequestHandler constructor.
     *
     * @param Request               $request      Запрос.
     * @param ContainerInterface    $container    Контейнер.
     * @param ParameterBagInterface $parameterBag Параметры контейнера.
     */
    public function __construct(
        Request $request,
        ContainerInterface $container,
        ParameterBagInterface $parameterBag
    ) {
        $this->request = $request;
        $this->container = $container;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Проверить токен из заголовков Request.
     *
     * @return bool
     *
     * @throws WrongCsrfException
     */
    public function validateCsrfToken() : bool
    {
        if ($this->parameterBag->get('csrf_protection')) {
            $token = $this->request->headers->get('x-csrf');

            if (!$this->container->has('security.csrf.token_manager')) {
                throw new WrongCsrfException('CSRF protection is not enabled in your application.');
            }

            $bValidToken = $this->container->get('security.csrf.token_manager')->isTokenValid(
                new CsrfToken('app', $token)
            );

            if (!$bValidToken) {
                throw new WrongCsrfException('Security error: Invalid CSRF token.');
            }
        }

        return true;
    }
}
