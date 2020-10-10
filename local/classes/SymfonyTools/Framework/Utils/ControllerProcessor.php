<?php

namespace Local\SymfonyTools\Framework\Utils;

use Closure;
use Psr\Container\ContainerInterface;
use Local\SymfonyTools\Framework\Exceptions\ArgumentsControllersException;
use Local\SymfonyTools\Framework\Utils\ResolverDependency\ResolveDependencyMaker;
use Local\SymfonyTools\Framework\Interfaces\InjectorControllerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;

/**
 * Class ControllerProcessor
 * Процессор контроллеров.
 * @package Local\SymfonyTools\Framework\Utils
 *
 * @since 05.09.2020
 * @since 10.09.2020 PSR-2 форматирование.
 */
class ControllerProcessor implements InjectorControllerInterface
{
    /**
     * @var ResolveDependencyMaker $resolveDependencyMaker Разрешитель зависимостей.
     */
    private $resolveDependencyMaker;

    /**
     * @var ContainerInterface $container Сервис-контейнер.
     */
    private $container;

    /**
     * ControllerProcessor constructor.
     *
     * @param ContainerInterface     $container       Сервис-контейнер.
     * @param ResolveDependencyMaker $dependencyMaker Разрешитель зависимостей.
     */
    public function __construct(
        ContainerInterface $container,
        ResolveDependencyMaker $dependencyMaker
    ) {
        $this->container = $container;
        $this->resolveDependencyMaker = $dependencyMaker;
    }

    /**
     * Инжекция зависимостей в контроллер.
     *
     * @param ControllerEvent $event Событие.
     *
     * @return ControllerEvent
     * @throws ArgumentsControllersException Ошибки инжекции.
     *
     * @since 06.09.2020 Рефакторинг в сторону упрощения.
     */
    public function inject(ControllerEvent $event): ControllerEvent
    {
        /** @var array $arArguments Аргументы контроллера. */
        try {
            $arArguments = $this->getArguments(
                $event->getRequest(),
                $event->getController()
            );
        } catch (ReflectionException $e) {
            throw new ArgumentsControllersException(
                'Ошибка в инжекции данных в конструктор контроллера ' . static::class
            );
        }

        try {
            $arTypesArguments = $this->getTypesArguments($event->getController());
        } catch (ReflectionException $e) {
            $arTypesArguments = [];
        }

        // Аргументы, не указанные в конфиге, но полученные рефлексией.
        $arAutowiredServices = $this->compareArrayByKeys($arTypesArguments, $arArguments);

        // Подмешать в результат.
        $arArguments = array_merge($arArguments, $arAutowiredServices);

        // Загнать аргументы в контроллер.
        foreach ($arArguments as $param => $argItem) {
            // Ресолвинг переменных из контейнера.
            if (strpos($argItem, '%') === 0) {
                $containerVar = str_replace('%', '', $argItem);

                /** @var Reference|string $resolvedVarValue Референс сервиса. */
                $resolvedVarValue = $this->container->getParameter($containerVar);

                // Референс сервиса: ID отдается через __toString.
                // Просто строка - не повредит привести к строке.
                if ($this->container->has((string)$resolvedVarValue)) {
                    $resolvedVarValue = '@' . (string)$resolvedVarValue;
                }

                $event->getRequest()->attributes->set($param, $resolvedVarValue);

                $argItem = $resolvedVarValue;
                // Продолжаем дальше, потому что в переменной может быть алиас сервиса.
            }

            // Всегда в начале пытаться достать из контейнера.
            // Но только, если не передали параметр снаружи!
            if (empty($event->getRequest()->attributes->get($param))
                &&
                $this->container->has($argItem)
            ) {
                $resolvedService = $this->container->get($argItem);

                $event->getRequest()->attributes->set($param, $resolvedService);
                continue;
            }

            // Если использован алиас сервиса, то попробовать получить его из контейнера.
            if (strpos($argItem, '@') === 0) {
                $serviceName = ltrim($argItem, '@');

                try {
                    $resolvedService = $this->container->get($serviceName);
                } catch (ServiceNotFoundException $e) {
                    throw new ArgumentsControllersException(
                        'Сервис '.$serviceName.' не найден'
                    );
                }

                $event->getRequest()->attributes->set($param, $resolvedService);
                continue;
            }

            if (class_exists($argItem)) {
                // Разрешить зависимости во всю рекурсивную глубину.
                $resolved = $this->resolveDependencyMaker->resolveDependencies($argItem);
                $event->getRequest()->attributes->set($param, $resolved);
            }
        }

        return $event;
    }

    /**
     * Вычленить аргументы, отсутствующие в конфиге. Request исключаем.
     *
     * @param array $arTypesArguments Типы всех аргументов контроллера.
     * @param array $arArguments      Аргументы, переданные через конфиг.
     *
     * @return array
     */
    protected function compareArrayByKeys(
        array $arTypesArguments,
        array $arArguments
    ): array {
        $arResult = [];

        foreach ($arTypesArguments as $key => $item) {
            // Есть ли такой сервис в сервис-контейнере?
            // Но только, если не передали параметр снаружи.
            if (empty($arArguments[$key])
                &&
                $this->container->has($item)
            ) {
                $arResult[$key] = $item;
                continue;
            }

            // Request нужно исключить!
            if (empty($arArguments[$key])
                &&
                $item !== 'Symfony\Component\HttpFoundation\Request'
            ) {
                $arResult[$key] = $item;
            }
        }

        return $arResult;
    }

    /**
     * Получить аргументы контроллера.
     *
     * @param Request $request    Request.
     * @param mixed   $controller Контроллер.
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getArguments(Request $request, $controller): array
    {
        $reflection = $this->reflectionController($controller);

        return $this->doGetArguments($request, $reflection->getParameters());
    }

    /**
     * Собрать типы аргументов. Для классов: параметр контроллера - название класса.
     *
     * @param mixed $controller Контроллер.
     *
     * @return array
     * @throws ReflectionException Ошибки рефлексии.
     */
    protected function getTypesArguments($controller): array
    {
        $arResult = [];

        $reflection = $this->reflectionController($controller);
        foreach ($reflection->getParameters() as $param) {
            $class = $param->getClass();

            // Не дать проскочить абстрактным классам.
            if (!$class
                ||
                (!$class->isInterface() && $class->isAbstract())
            ) {
                continue;
            }

            $arResult[$param->name] = $class->name;
        }

        return $arResult;
    }

    /**
     * Рефлексия контроллера.
     *
     * @param mixed $controller Контроллер.
     *
     * @return ReflectionFunction|ReflectionMethod
     * @throws ReflectionException Ошибки рефлексии.
     */
    protected function reflectionController($controller)
    {
        if (is_array($controller)) {
            $reflection = new ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && !$controller instanceof Closure) {
            $reflection = new ReflectionObject($controller);
            $reflection = $reflection->getMethod('__invoke');
        } else {
            $reflection = new ReflectionFunction($controller);
        }

        return $reflection;
    }

    /**
     * Сама механика получения аргументов.
     *
     * @param Request $request    Запрос.
     * @param array   $parameters Параметры.
     *
     * @return array
     */
    protected function doGetArguments(Request $request, array $parameters): array
    {
        $attributes = $request->attributes->all();
        $arguments = [];

        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                $arguments[$param->name] = $attributes[$param->name];
            }
        }

        return $arguments;
    }
}
