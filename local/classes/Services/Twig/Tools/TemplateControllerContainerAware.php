<?php

namespace Local\Services\Twig\Tools;

use Local\SymfonyTools\ArgumentsResolvers\Supply\ResolveParamsFromContainer;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class TemplateControllerContainerAware
 * @package Fedy\Services\Twig\Tools
 *
 * @since 03.11.2020
 *
 * @see https://github.com/symfony/symfony/blob/5.x/src/Symfony/Bundle/FrameworkBundle/Controller/TemplateController.php
 * Из-за ограничений версии Symfony 4.4 приходится выносить класс локально.
 */
class TemplateControllerContainerAware extends TemplateController
{
    /**
     * @var ResolveParamsFromContainer $resolveParamsFromContainer Ресолвер параметров из контейнера.
     */
    private $resolveParamsFromContainer;

    /**
     * TemplateControllerContainerAware constructor.
     *
     * @param ResolveParamsFromContainer $resolveParamsFromContainer Ресолвер параметров из контейнера.
     * @param Environment|null           $twig                       Твиг.
     */
    public function __construct(
        ResolveParamsFromContainer $resolveParamsFromContainer,
        Environment $twig = null
    ) {
        $this->resolveParamsFromContainer = $resolveParamsFromContainer;
        parent::__construct($twig);
    }

    /**
     * Renders a template.
     *
     * @param string       $template  The template name.
     * @param integer|null $maxAge    Max age for client caching.
     * @param integer|null $sharedAge Max age for shared (proxy) caching.
     * @param boolean|null $private   Whether or not caching should apply for client caches only.
     * @param array        $context   The context (arguments) of the template.
     *
     * @return Response
     *
     * @throws LoaderError  Ошибки Твига.
     * @throws RuntimeError Ошибки Твига.
     * @throws SyntaxError  Ошибки Твига.
     */
    public function templateAction(
        string $template,
        int $maxAge = null,
        int $sharedAge = null,
        bool $private = null,
        array $context = []
    ): Response {

        $context = $this->resolveServices($context);

        return parent::templateAction(
            $template,
            $maxAge,
            $sharedAge,
            $private,
            $context
        );
    }

    /**
     * Разрешить сервисы из контейнера.
     *
     * @param array $context Контекст.
     *
     * @return array
     */
    private function resolveServices(array $context): array
    {
        $result = $context;

        foreach ($context as $key => $item) {
            if (is_array($item)) {
                $result[$key] = $this->resolveServices($item);
                continue;
            }

            $resolvedService = $this->resolveParamsFromContainer->resolve($item);

            if ($resolvedService !== null) {
                $result[$key] = $resolvedService;
            }
        }

        return $result;
    }
}
