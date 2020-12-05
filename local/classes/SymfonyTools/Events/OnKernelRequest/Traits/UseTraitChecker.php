<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Traits;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Trait UseTraitChecker
 * @package Local\SymfonyTools\Events\OnKernelRequest\Traits
 *
 * @since 05.12.2020
 */
trait UseTraitChecker
{
    /**
     * Использует ли этот контроллер такой-то трэйт.
     *
     * @param ControllerEvent $event Объект события.
     * @param string          $trait Название трэйта.
     *
     * @return boolean
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