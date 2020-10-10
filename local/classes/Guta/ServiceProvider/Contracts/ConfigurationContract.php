<?php

namespace Local\Guta\ServiceProvider\Contracts;

interface ConfigurationContract
{
    /**
     * get config value
     *
     * @access  public
     * @param   string  $key
     * @return  mixed
     */
    public function get(string $key);
}
