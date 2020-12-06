<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Tests\Cases\Listeners;

use Exception;
use Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions\InvalidAjaxCallException;
use Local\Bundles\CustomArgumentResolverBundle\Event\Listeners\AjaxCall;
use Local\Bundles\CustomArgumentResolverBundle\Tests\Samples\SampleControllerAjaxTrait;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tests\PhpUnitExtensions\FedyTestCase;

/**
 * Class AjaxCallTest
 * @package Local\Bundles\CustomArgumentResolverBundle\Tests\Listeners
 * @coversDefaultClass AjaxCall
 *
 * @since 10.09.2020
 * @since 28.10.2020 Рефакторинг.
 * @since 05.12.2020 Актуализация.
 */
class AjaxCallTest extends FedyTestCase
{
    /**
     * @var AjaxCall $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obTestObject = new AjaxCall();
    }

    /**
     * handle(). Нормальный ход вещей.
     *
     * @return void
     * @throws Exception
     */
    public function testHandle() : void
    {
        $this->obTestObject->handle($this->getMockControllerEvent());

        $this->assertTrue(
            true
        );
    }

    /**
     * handle(). Не AJAX.
     *
     * @return void
     * @throws Exception
     */
    public function testHandleNoAjax() : void
    {
        $event = $this->getMockControllerEvent(false);
        $event->getRequest()->headers->replace([]);

        $this->expectException(InvalidAjaxCallException::class);
        $this->obTestObject->handle($event);
    }

    /**
     * Мок ControllerEvent.
     *
     * @param bool $ajax
     *
     * @return mixed
     * @throws Exception
     */
    private function getMockControllerEvent(bool $ajax = true)
    {
        $controllerResolver = new ControllerResolver();

        $request = $this->getFakeRequest($ajax);
        $controller = $controllerResolver->getController($request);

        return new ControllerEvent(
            $this->containerSymfony->get('kernel'),
            $controller,
            $this->getFakeRequest(),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    /**
     * Создать фэйковый Request.
     *
     * @param boolean $ajax
     *
     * @return Request
     */
    private function getFakeRequest(bool $ajax = true): Request
    {
        $fakeRequest = Request::create(
            '/api/fake/',
            'GET',
            []
        );

        $fakeRequest->attributes->set('_controller',
            'Local\Bundles\CustomArgumentResolverBundle\Tests\Samples\SampleControllerAjaxTrait::action'
        );

        $fakeRequest->attributes->set('obj', SampleControllerAjaxTrait::class);
        if ($ajax) {
            $fakeRequest->headers = new HeaderBag(['x-requested-with' => 'XMLHttpRequest']);
        }

        return $fakeRequest;
    }
}
