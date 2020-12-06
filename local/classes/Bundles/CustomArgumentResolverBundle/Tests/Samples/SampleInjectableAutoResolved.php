<?php
/** @noinspection PhpUnusedParameterInspection */

namespace Local\Bundles\CustomArgumentResolverBundle\Tests\Samples;

use Fedy\AutoWiring\Basis;

/**
 * Class SampleInjectable
 * @package Local\Bundles\CustomArgumentResolverBundle\Tests\Samples
 *
 */
class SampleInjectableAutoResolved extends Basis
{
    protected const TEST_CONSTANT = 333;

    protected $id;
    protected $logger;
    protected $testing;
    protected $array;
    protected $function;
    protected $callable;

    /**
     * @var NonValidClass $exampleNonValidClass Невалидный класс.
     */
    protected $exampleNonValidClass;

    /**
     * SampleInjectable constructor.
     *
     * @function  ["\Tests\AutoWiring\Samples\SampleCallable::actionInteger"]
     *
     * @param integer $id
     * @param SampleClassForTesting|null $testing
     * @param string $callable
     * @param array $array
     * @param callable $function
     *
     * Рекурсивная автоинициализация параметров конструктора.
     *
     * @autowiring
     */
    public function __construct(
        int $id = 2,
        SampleClassForTesting $testing = null,
        string $callable = null,
        array $array = ['test' => 'test'],
        callable $function = null
    ) {

        $this->id = $id; // Если так и $id не равно null, то инжекция отменяется.

        parent::__construct();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return SampleClassForTesting|null
     */
    public function getExampleAutowiredClass(): ?SampleClassForTesting
    {
        return $this->testing;
    }

    /**
     * @return mixed.
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return mixed
     */
    public function getExampleNonValidClass()
    {
        return $this->exampleNonValidClass;
    }
}
