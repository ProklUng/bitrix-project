<?php

namespace Local\Bundles\CustomRequestResponserBundle\Event\Listeners;

use Local\Bundles\CustomRequestResponserBundle\Event\Interfaces\OnKernelResponseHandlerInterface;
use Local\Bundles\CustomRequestResponserBundle\Event\Traits\AbstractSubscriberKernelResponseTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class SetHeaders
 *
 * @package Local\Bundles\CustomRequestResponserBundle\Event\Listeners
 *
 * @since 20.10.2020
 */
class SetHeaders implements EventSubscriberInterface, OnKernelResponseHandlerInterface
{
    use AbstractSubscriberKernelResponseTrait;

    /** @var ExpressionLanguage $expressionLanguage */
    private $expressionLanguage;

    /** @var array $headers */
    private $headers;

    /**
     * SetHeaders constructor.
     *
     * @param ExpressionLanguage $expressionLanguage
     * @param array $headers
     */
    public function __construct(ExpressionLanguage $expressionLanguage, array $headers)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->headers = $headers;
    }

    /**
     * Событие kernel.response.
     *
     * Установка заголовков Response по условию.
     *
     * @param ResponseEvent $event Объект события.
     *
     * @return void
     *
     */
    public function handle(ResponseEvent $event): void
    {
        // Фильтрация Wordpress обычных маршрутов.
        if (!$event->isMasterRequest()
            ||
            $event->getResponse()->getStatusCode() === 404
        ) {
            return;
        }

        $response = $event->getResponse();

        $evaluationValues = [
            'request' => $event->getRequest(),
            'response' => $event->getResponse(),
        ];

        foreach ($this->headers['headers'] as $header) {
            if (isset($header['condition'])
                &&
                (bool)$this->expressionLanguage->evaluate($header['condition'], $evaluationValues) !== true) {
                continue;
            }

            $response->headers->set($header['name'], $header['value']);
        }
    }
}
