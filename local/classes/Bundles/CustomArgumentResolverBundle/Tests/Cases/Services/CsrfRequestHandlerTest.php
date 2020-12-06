<?php

namespace Local\Bundles\CustomArgumentResolverBundle\Tests\Cases\Services;

use Local\Bundles\CustomArgumentResolverBundle\Event\Exceptions\WrongCsrfException;
use Local\Bundles\CustomArgumentResolverBundle\Service\Utils\CsrfRequestHandler;
use Local\Bundles\CustomArgumentResolverBundle\Tests\Tools\BaseTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Class CsrfRequestHandlerTest
 * @package Local\Bundles\CustomArgumentResolverBundle\Tests\Cases\Services
 * @coversDefaultClass CsrfRequestHandler
 *
 * @since 05.12.2020 Актуализация.
 */
class CsrfRequestHandlerTest extends BaseTestCase
{
    /**
     * @var CsrfRequestHandler $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new CsrfRequestHandler(
            $this->getRequest($this->getValidToken()),
            static::$testContainer,
            static::$testContainer->getParameterBag()
        );
    }

    /**
    * validateCsrfToken().
    *
    */
    public function testValidateCsrfToken() : void
    {
        $result = $this->obTestObject->validateCsrfToken();

        $this->assertTrue(
            $result,
            'Не прошла валидация csrf токена.'
        );
    }

    /**
     * testValidateCsrfTokenInvalid().
     */
    public function testValidateCsrfTokenInvalid() : void
    {
        $this->obTestObject = new CsrfRequestHandler(
            $this->getRequest(''),
            static::$testContainer,
            new ParameterBag(['csrf_protection' => true])
        );

        $this->expectException(WrongCsrfException::class);

        $this->obTestObject->validateCsrfToken();
    }

    /**
     * Request.
     *
     * @param string $token
     *
     * @return Request
     */
    private function getRequest(string $token) : Request
    {
        return new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_X_CSRF' => $token]
        );
    }

    /**
     * Априори валидный токен.
     *
     * @return string
     */
    private function getValidToken() : string
    {
        $csrf = new CsrfTokenManager();
        return $csrf->getToken('app')->getValue();
    }
}
