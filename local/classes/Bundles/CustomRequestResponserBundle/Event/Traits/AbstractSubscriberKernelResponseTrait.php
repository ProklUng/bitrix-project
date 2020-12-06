<?php

namespace Local\Bundles\CustomRequestResponserBundle\Event\Traits;

use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AbstractSubscriberKernelResponseTrait
 * Общие методы для подписчиков на события kernel.response.
 * @package Local\Bundles\CustomRequestResponserBundle\Event\Traits
 *
 * @since 20.10.2020
 */
trait AbstractSubscriberKernelResponseTrait
{
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
            KernelEvents::RESPONSE => 'handle'
        ];
    }
}
