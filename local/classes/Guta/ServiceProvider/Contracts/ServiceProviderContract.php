<?php

namespace Local\Guta\ServiceProvider\Contracts;

interface ServiceProviderContract
{
    /**
     * Registers the service dependencies in the application
     *
     * @access  public
     * @return  void
     */
    public function register(): void;
}
