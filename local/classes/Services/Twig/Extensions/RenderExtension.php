<?php

namespace Local\Services\Twig\Extensions;

use InvalidArgumentException;
use Local\SymfonyTools\Framework\DispatchController;
use Local\SymfonyTools\Framework\Utils\ResolverDependency\ResolveDependencyMakerContainerAware;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig_ExtensionInterface;

/**
 * Class RenderExtension
 * Расширение Twig - команда render().
 * @package Local\Services\Twig\Extensions
 *
 * @since 21.10.2020
 *
 * Пример использования в твиговском шаблоне:
 *
 * <div id="sidebar">
 * {{ render(controller(
 * 'App\\Controller\\ArticleController::recentArticles',
 * { 'max': 3 }
 * )) }}
 * </div>
 *
 * Контроллер может обозначаться по разному:
 *
 * 1. 'App\\Controller\\ArticleController'. Проверяется наличие __invoke. Потом метода action.
 * 2. 'App\\Controller\\ArticleController::recentArticles'. Класс App\Controller\ArticleController,
 * метод recentArticles.
 */
class RenderExtension extends AbstractExtension implements Twig_ExtensionInterface
{
    use ContainerAwareTrait;

    /**
     * @var ResolveDependencyMakerContainerAware $resolveDependencyMakerContainerAware Разрешитель зависимостей.
     */
    private $resolveDependencyMakerContainerAware;

    /**
     * @var DispatchController $dispatchController Исполнитель контроллеров.
     */
    private $dispatchController;

    /**
     * RenderExtension constructor.
     *
     * @param ResolveDependencyMakerContainerAware $resolveDependencyMakerContainerAware Разрешитель зависимостей.
     * @param DispatchController                   $dispatchController                   Исполнитель контроллеров.
     */
    public function __construct(
        ResolveDependencyMakerContainerAware $resolveDependencyMakerContainerAware,
        DispatchController $dispatchController
    ) {
        $this->resolveDependencyMakerContainerAware = $resolveDependencyMakerContainerAware;
        $this->dispatchController = $dispatchController;
    }

    /**
     * Return extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'render_extension';
    }

    /**
     * Twig functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('render', [$this, 'render']),
        ];
    }

    /**
     * Twig команда render().
     *
     * @param ControllerReference $controller Референс контроллера.
     *
     * @throws RuntimeException Ошибка рендеринга контроллера.
     */
    public function render(ControllerReference $controller)
    {
        $controllerClass = $controller->controller;
        $attributes = $controller->attributes;
        $query = $controller->query;

        $resolvedController = $this->parseControllerString($controllerClass);

        $this->dispatchController->setParams($attributes)
            ->setQuery($query);

        if ($this->dispatchController->dispatch($resolvedController)) {
            $response = $this->dispatchController->getResponse();
            echo $response->getContent();

            return;
        }

        throw new RuntimeException(
            sprintf(
                sprintf(
                    'Twig function render: error rendering controller %s.',
                    $controllerClass
                )
            )
        );
    }

    /**
     * Распарсить строку с контроллером и методом.
     *
     * @param string $controller Строка с контроллером.
     *
     * @return array
     *
     * @throws InvalidArgumentException Что-то не то с аргументами.
     */
    private function parseControllerString(string $controller)
    {
        if (strpos($controller, '::') !== false) {
            $parsedClass = explode('::', $controller);
            if (($resolvedControllerClass = $this->getFromContainer($parsedClass[0])) === null) {
                $resolvedControllerClass = $this->resolveDependencyMakerContainerAware->resolveDependencies(
                    $parsedClass[0]
                );
            }

            $this->checkClassAndMethod($resolvedControllerClass, $parsedClass[0], $parsedClass[1]);

            return [$resolvedControllerClass, $parsedClass[1]];
        }

        if (($resolvedControllerClass = $this->getFromContainer($controller)) === null) {
            $resolvedControllerClass = $this->resolveDependencyMakerContainerAware->resolveDependencies($controller);
        }

        $methodDefault = 'action';
        if (method_exists($resolvedControllerClass, '__invoke')) {
            $methodDefault = '__invoke';
        }

        $this->checkClassAndMethod($resolvedControllerClass, $controller, $methodDefault);

        return [$resolvedControllerClass, $methodDefault];
    }

    /**
     * Проверка на существование класса и метода контроллера.
     *
     * @param mixed  $resolvedControllerClass Уже отресолвленный класс или строка.
     * @param string $parsedClassName         Класс.
     * @param string $method                  Метод.
     *
     * @return void
     *
     * @throws InvalidArgumentException Что-то не то с аргументами.
     */
    private function checkClassAndMethod($resolvedControllerClass, string $parsedClassName, string $method): void
    {
        if (!$resolvedControllerClass) {
            throw new InvalidArgumentException(
                sprintf(
                    'class %s not resolved.',
                    $parsedClassName
                )
            );
        }

        if (!method_exists($resolvedControllerClass, $method)) {
            throw new InvalidArgumentException(
                sprintf(
                    'method %s not exist in class %s.',
                    $method,
                    $parsedClassName
                )
            );
        }
    }

    /**
     * Получить сервис из контейнера.
     *
     * @param string $class Класс, предполагаемый сервисом.
     *
     * @return object|null
     */
    private function getFromContainer(string $class)
    {
        if ($this->container->has($class)) {
            return $this->container->get($class);
        }

        return null;
    }
}
