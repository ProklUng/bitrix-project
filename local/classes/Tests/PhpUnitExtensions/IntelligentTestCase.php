<?php
namespace Local\Tests\PhpUnitExtensions;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use \PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

abstract class IntelligentTestCase extends TestCase
{
    private const GET_TESTED_CLASS_METHOD_NAME = 'getTestedClass';
    /** @var null|PropertyBag */
    private $propertyBag;
    /** @var null|string[] */
    private $methodsMap;

    /**
     * @param string $name
     * @throws Exception
     * @return ConstDependencyInjectionParameter|MockObject
     */
    public function __get(string $name)
    {

        $property = $this->getPropertyBag()->get($name);

        if ($property === false) {
            throw new Exception("Invalid property name: ${name}");
        }

        return $property;
    }

    /**
     * @param string $method
     * @param array $params
     * @throws Exception
     * @return object
     */
    public function __call(string $method, array $params)
    {

        if ($method !== self::GET_TESTED_CLASS_METHOD_NAME) {
            throw new Exception("Invalid method name: ${method}");
        }

        if (!array_key_exists(self::GET_TESTED_CLASS_METHOD_NAME, $this->getMethodsMap())) {
            throw new Exception('Method ' . self::GET_TESTED_CLASS_METHOD_NAME . ' not annotated');
        }

        $params = $this->getPropertyBag()->getConstructorParams();

        $paramValues = [];
        foreach ($params as $param) {
            $property = $this->__get($param);
            $paramValues[] = ($property instanceof ConstDependencyInjectionParameter)
                ? $property->get()
                : $property
            ;
        }

        $reflection = new ReflectionClass($this->getMethodsMap()[self::GET_TESTED_CLASS_METHOD_NAME]);

        return $reflection->newInstanceArgs($paramValues);
    }

    protected function tearDown() : void
    {
        $this->propertyBag = null;
        $this->methodMap = null;

        parent::tearDown();
    }

    /**
     * @throws Exception
     * @return PropertyBag
     */
    private function getPropertyBag(): PropertyBag
    {
        if ($this->propertyBag === null) {
            $this->propertyBag = $this->loadMockObjectsFromPropertyAnnotations();

            $size = $this->propertyBag->size();
            if ($size >= 18) {
                error_log(
                    "\n\rHoly sheet. Fuck!!! Unmaintained:( I wanna to automatically (by design) fail this test" .
                    "\n\rGreater or equal ${size} dependencies in test class " . get_class($this) . '. ' .
                    "\n\rThis is FAIL. You MUST refactor tested class."
                );
            } elseif ($size >= 15) {
                error_log(
                    "\n\rToo many dependencies (greater or equal ${size}) in test class " . get_class($this) . '.' .
                    "\n\rBro, this is not good. You should refactor it."
                );
            } elseif ($size >= 12) {
                error_log(
                    "\n\rBad design. You use greater or equal ${size} dependencies in test class " . get_class($this) .
                    "\n\rYou will make world better if you refactor it."
                );
            } elseif ($size >= 9) {
                error_log(
                    "You are near good practices. You use greater or equal ${size} dependencies in test class " . get_class($this) .
                    "\n\rIf you can use less than 9 dependencies, you will complete mission and hide this annoyed message=)"
                );
            }
        }

        return $this->propertyBag;
    }

    /**
     * @throws Exception
     * @return PropertyBag
     */
    private function loadMockObjectsFromPropertyAnnotations(): PropertyBag
    {

        return (new DocCommentParser())
            ->parse(
                get_class($this),
                function (string $className):MockObject {
                    return $this->createDefaultObjectMock($className);
                }
            );
    }

    /**
     * @throws ReflectionException
     * @return string[]
     */
    private function getMethodsMap(): array
    {
        if ($this->methodsMap === null) {
            $this->methodsMap = $this->loadGetTestedClassMethod();
        }

        return $this->methodsMap;
    }

    /**
     * @throws ReflectionException
     * @return string[]
     */
    private function loadGetTestedClassMethod(): array
    {
        /** @var string[] $res */
        $r = new ReflectionClass(get_class($this));
        $docComment = $r->getDocComment();
        if ($docComment === false) {
            return [];
        }
        $res = [];
        preg_match_all('/@method\s*(\S*)\s*(getTestedClass)\(\s*\)/', $r->getDocComment(), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $res[$match[2]] = $match[1];
        }

        return $res;
    }

    private function createDefaultObjectMock(string $sourceClass): MockObject
    {
        return $this
            ->getMockBuilder($sourceClass)
            ->disableOriginalConstructor()
            ->getMock()
            ;
    }
}
