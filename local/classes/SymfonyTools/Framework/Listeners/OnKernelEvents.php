<?php

namespace Local\SymfonyTools\Framework\Listeners;

use Exception;
use Local\Controllers\Traits\ValidatorTraits\BitrixSecurityTokenTrait;
use Local\Controllers\Traits\ValidatorTraits\SecurityAjaxCallTrait;
use Local\Controllers\Traits\ValidatorTraits\SecurityTokenTrait;
use Local\SymfonyTools\Events\Exceptions\InvalidAjaxCallException;
use Local\SymfonyTools\Events\Exceptions\WrongSecurityTokenException;
use Local\SymfonyTools\Framework\Exceptions\ArgumentsControllersException;
use Local\SymfonyTools\Framework\Exceptions\WrongCsrfException;
use Local\SymfonyTools\Framework\Interfaces\InjectorControllerInterface;
use Local\SymfonyTools\Framework\Utils\ControllerProcessor;
use Local\SymfonyTools\Framework\Utils\ResolverDependency\ResolveDependencyMaker;
use Local\SymfonyTools\Framework\Utils\CsrfRequestHandler;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class OnKernel
 * Подписка на события Symfony Kernel.
 * @package Local\SymfonyTools\Framework\Listeners
 *
 * @since 05.09.2020
 * @since 06.09.2020 Контейнер пробрасывается снаружи. Инициализация зависимостей.
 * @since 08.09.2020 Работа над ошибками.
 * @since 10.09.2020 Дополнен функционал.
 */
final class OnKernelEvents implements EventSubscriberInterface
{
    /**
     * @var ContainerBuilder $container Сервис-контейнер.
     */
    private $container;

    /**
     * @var InjectorControllerInterface $controllerProcessor Обработчик контроллеров.
     */
    private $controllerProcessor;

    /**
     * OnKernelEvents constructor.
     *
     * @param ContainerInterface $container Сервис-контейнер.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->controllerProcessor = new ControllerProcessor(
            $this->container,
            new ResolveDependencyMaker()
        );
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Загнать сервис-контейнер в контроллер.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @since 10.09.2020
     */
    public function setContainer(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        // Только для контроллеров инжектить контейнер.
        if ($controller[0] instanceof AbstractController) {
            // Установить сервис-контейнер.
            $controller[0]->setContainer($this->container);
        }
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Если контроллер зарегистрирован как сервис - использовать его.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws Exception Ошибки сервис-контейнера.
     *
     * @since 10.09.2020
     */
    public function injectServiceToController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        $action = '';
        if (is_array($controller)) {
            $controller = $controller[0];

            // Получение метода контроллера.
            $controllerParams = $event->getRequest()->attributes->get('_controller');

            // Если строка, то расщепить и получить action так.
            if (is_string($controllerParams)) {
                $params = explode('::', $controllerParams);
                $action = $params[1];
            }

            // Если массив, то воспользоваться уже готовым.
            // Иной способ инициализации роутов.
            if (is_array($controllerParams)) {
                $action = !empty($controllerParams[1]) ? $controllerParams[1] : '';
            }
        }

        // Если контроллер зарегистрирован как сервис - использовать его.
        $classController = get_class($controller);

        if ($this->container->has($classController)) {
            $controller = $this->container->get($classController);
            $event->setController([$controller, $action]);
        }
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Инжекция аргументов в контроллер.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws ArgumentsControllersException Ошибки инжекции.
     *
     * @since 10.09.2020
     */
    public function injectArgumentsToController(ControllerEvent $event): void
    {
        $this->controllerProcessor->inject($event);
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Проверка на вызов AJAX.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws InvalidAjaxCallException Это не AJAX вызов.
     *
     * @since 10.09.2020
     */
    public function isAjaxRequest(ControllerEvent $event): void
    {
        if (!$this->useTrait($event, SecurityAjaxCallTrait::class)) {
            return;
        }

        if (!$event->getRequest()->isXmlHttpRequest()) {
            throw new InvalidAjaxCallException('Invalid type call.');
        }
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Валидация токена при наличии трэйта SecurityTokenTrait в контроллере.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws WrongSecurityTokenException Невалидный CSRF токен.
     *
     * @since 10.09.2020
     */
    public function checkCsrfToken(ControllerEvent $event): void
    {
        if (!$this->useTrait($event, SecurityTokenTrait::class)) {
            return;
        }

        if (!$this->container->get('security.csrf.token_manager')) {
            throw new WrongSecurityTokenException('security.csrf.token_manager not installed.');
        }

        if (empty($token = $event->getRequest()->request->get('security.token'))) {
            throw new WrongSecurityTokenException('Secirity error: empty token.');
        }

        // Валидировать токен, для примера, так.
        $bValidToken = $this->container->get('security.csrf.token_manager')->isTokenValid(
            new CsrfToken('app', $token)
        );

        if (!$bValidToken) {
            throw new WrongSecurityTokenException('Security error: Invalid security token.');
        }
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Валидация токена Битрикс при наличии трэйта BitrixSecurityTokenTrait в контроллере.
     * Предполагается, что токен прилетит в POST запросе, поле - sessid.
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     * @throws WrongSecurityTokenException Невалидный битриксовый токен.
     *
     * @since 10.09.2020
     */
    public function checkBitrixSecurityToken(ControllerEvent $event) : void
    {
        $controller = $event->getController();

        /**
         * needCheckToken() -> BitrixSecurityTokenTrait.
         */
        if (!$this->useTrait($event, BitrixSecurityTokenTrait::class)
            ||
            (is_object($controller[0]) && !$controller[0]->needCheckToken())
        ) {
            return;
        }

        $token = $event->getRequest()->request->get('sessid');

        if (empty($token)
            ||
            !check_bitrix_sessid()
        ) {
            throw new WrongSecurityTokenException('Secirity error: invalid Bitrix token.');
        }
    }

    /**
     * Обработчик события kernel.controller.
     *
     * Инициализация трэйтов контроллера. Вызов метода boot + название трэйта, если таковой существует.
     * (из Laravel)
     *
     * @param ControllerEvent $event Объект события.
     *
     * @return void
     *
     * @since 10.09.2020
     */
    public function bootTraits(ControllerEvent $event) : void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $booted = [];

        foreach (class_uses_recursive($controller[0]) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($controller[0], $method) && !in_array($method, $booted)) {
                forward_static_call([$controller[0], $method]);

                $booted[] = $method;
            }
        }
    }

    /**
     * Событие kernel.request.
     *
     * Проверка - при необходимости Csrf токена.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     * @throws WrongCsrfException Невалидный токен.
     */
    public function validateRequestCsrfToken(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $csrfRequestHandler = new CsrfRequestHandler(
            $request,
            $this->container
        );

        $csrfRequestHandler->validateCsrfToken();
    }

    /**
     * Событие kernel.request.
     *
     * Установить сессию Symfony для всех запросов к контроллерам.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     * @throws Exception Ошибки сервис-контейнера.
     *
     * @since 10.09.2020
     */
    public function setSessionRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $request->setSession(
            $this->container->get('session.instance')
        );
    }

    /**
     * Resolve arguments.
     *
     * @param ControllerArgumentsEvent $event Объект события.
     *
     * @return void
     */
    public function onControllerArgumentRequest(ControllerArgumentsEvent $event)
    {
        // Заглушка.
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['setContainer', 50],
                ['bootTraits', 50],
                ['injectServiceToController', 45],
                ['injectArgumentsToController', 40],
                ['checkBitrixSecurityToken', 35],
                ['checkCsrfToken', 30],
                ['isAjaxRequest', 25],
            ],
            KernelEvents::REQUEST => [
                ['validateRequestCsrfToken', 10],
                ['setSessionRequest', 5]
            ],
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArgumentRequest',
        ];
    }

    /**
     * Использует ли этот контроллер такой-то трэйт.
     *
     * @param ControllerEvent $event Объект события.
     * @param string          $trait Название трэйта.
     *
     * @return boolean
     *
     * @since 10.09.2020
     */
    private function useTrait(
        ControllerEvent $event,
        string $trait
    ): bool {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return false;
        }

        // class_uses_recursive - Laravel helper.
        $traits = class_uses($controller[0]);
        if (!in_array($trait, $traits, true)) {
            return false;
        }

        return true;
    }
}
