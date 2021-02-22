<?php

namespace Local\Bundles\CustomRequestResponserBundle\Services\BitrixEventBridge;

use Local\Bundles\CustomRequestResponserBundle\Event\Listeners\PageSpeedMiddlewares;

/**
 * Class Bridge
 * Способ запускать PageSpeed middlewares на нативных маршрутах.
 * @package Local\Bundles\CustomRequestResponserBundle\Services\BitrixEventBridge
 *
 * @since 21.02.2021
 */
class Bridge extends PageSpeedMiddlewares
{
    /**
     * Обработчик события OnEndBufferContent.
     *
     * @param string $content Контент.
     *
     * @return void
     */
    public function handleEvent(string &$content) : void
    {
        foreach ($this->middlewaresBag as $middleware) {
            $content = $middleware->apply($content);
        }
    }
}