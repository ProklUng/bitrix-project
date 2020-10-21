<?php

namespace Local\Services\Twig\Tools;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TemplateController
 * @package Fedy\Services\Twig\Tools
 *
 * @since 21.10.2020
 *
 * @see https://github.com/symfony/symfony/blob/5.x/src/Symfony/Bundle/FrameworkBundle/Controller/TemplateController.php
 * Из-за ограничений версии Symfony 4.4 приходится выносить класс локально.
 */
class TemplateController
{
    /**
     * @var Environment|null $twig
     */
    private $twig;

    public function __construct(Environment $twig = null)
    {
        $this->twig = $twig;
    }

    /**
     * Renders a template.
     *
     * @param string $template The template name
     * @param integer|null $maxAge Max age for client caching
     * @param integer|null $sharedAge Max age for shared (proxy) caching
     * @param integer|null $private Whether or not caching should apply for client caches only
     * @param array $context The context (arguments) of the template
     *
     * @return Response
     */
    public function templateAction(
        string $template,
        int $maxAge = null,
        int $sharedAge = null,
        bool $private = null,
        array $context = []
    ): Response {
        if (null === $this->twig) {
            throw new \LogicException('You can not use the TemplateController if the Twig Bundle is not available.');
        }

        $response = new Response($this->twig->render($template, $context));

        if ($maxAge) {
            $response->setMaxAge($maxAge);
        }

        if (null !== $sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif (false === $private || (null === $private && (null !== $maxAge || null !== $sharedAge))) {
            $response->setPublic();
        }

        return $response;
    }

    /**
     * @param string $template The template name
     * @param int|null $maxAge Max age for client caching
     * @param int|null $sharedAge Max age for shared (proxy) caching
     * @param bool|null $private Whether or not caching should apply for client caches only
     * @param array $context The context (arguments) of the template
     *
     * @return Response
     */
    public function __invoke(
        string $template,
        int $maxAge = null,
        int $sharedAge = null,
        bool $private = null,
        array $context = []
    ): Response {
        return $this->templateAction($template, $maxAge, $sharedAge, $private, $context);
    }
}