<?php

namespace Local\Bundles\ApiExceptionBundle\Exception\Interfaces;

/**
 * Interface FlattenErrorExceptionInterface
 */
interface FlattenErrorExceptionInterface
{
    /**
     * Flatten errors
     */
    public function getFlattenErrors();
}
