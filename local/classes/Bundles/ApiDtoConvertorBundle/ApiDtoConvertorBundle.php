<?php

namespace Local\Bundles\ApiDtoConvertorBundle;

use Local\Bundles\ApiDtoConvertorBundle\DependencyInjection\ApiExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApiDtoConvertorBundle
 * @package Local\Bundles\ApiDtoConvertorBundle
 *
 * @since 04.11.2020
 */
class ApiDtoConvertorBundle extends Bundle
{
    /**
     * @return mixed
     */
    public function getContainerExtension()
    {
        return new ApiExtension();
    }
}
