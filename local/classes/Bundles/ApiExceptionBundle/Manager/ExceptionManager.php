<?php

namespace Local\Bundles\ApiExceptionBundle\Manager;

use Local\Bundles\ApiExceptionBundle\Exception\Interfaces\HttpExceptionInterface;
use Throwable;
use Local\Bundles\ApiExceptionBundle\Exception\Interfaces\ExceptionInterface;

/**
 * Manage exceptions to define public data returned
 */
class ExceptionManager
{
    /**
     * @var array $defaultConfig
     */
    protected $defaultConfig;

    /**
     * @var array $exceptions
     */
    protected $exceptions;

    /**
     * Constructor.
     *
     * @param array $defaultConfig
     * @param array $exceptions
     */
    public function __construct(array $defaultConfig, array $exceptions)
    {
        $this->defaultConfig    = $defaultConfig;
        $this->exceptions       = $exceptions;
    }

    /**
     * Configure Exception
     *
     * @param Throwable $exception
     *
     * @return Throwable
     */
    public function configure(Throwable $exception): Throwable
    {
        $exceptionName = get_class($exception);

        $configException = $this->getConfigException($exceptionName);

        $exception->setCode($configException['code']);
        $exception->setMessage($configException['message']);

        if ($exception instanceof HttpExceptionInterface) {
            $exception->setStatusCode($configException['status']);
            $exception->setHeaders($configException['headers']);
        }

        return $exception;
    }

    /**
     * Get config to exception
     *
     * @param string $exceptionName
     *
     * @return array
     */
    protected function getConfigException($exceptionName): array
    {
        $exceptionParentName = get_parent_class($exceptionName);

        if (in_array(ExceptionInterface::class,
            class_implements($exceptionParentName), true)) {
            $parentConfig = $this->exceptions[$exceptionName] ?? $this->defaultConfig;
        } else {
            $parentConfig = $this->defaultConfig;
        }

        if (isset($this->exceptions[$exceptionName])) {
            return array_merge($parentConfig, $this->exceptions[$exceptionName]);
        }

        return $parentConfig;
    }
}
