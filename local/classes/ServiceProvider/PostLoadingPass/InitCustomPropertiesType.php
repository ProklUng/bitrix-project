<?php

namespace Local\ServiceProvider\PostLoadingPass;

use Exception;
use InvalidArgumentException;
use Local\ServiceProvider\Interfaces\PostLoadingPassInterface;
use Local\ServiceProvider\PostLoadingPass\Exceptions\RuntimePostLoadingPassException;
use Local\Util\IblockPropertyType\Abstraction\IblockPropertyTypeInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class InitCustomPropertiesType
 *
 * Инициализация кастомных типов полей Битрикс.
 *
 * @package Local\ServiceProvider\PostLoadingPass
 *
 * @since 17.10.2020.
 *
 * @example В Yaml файле:
 *
 * tags:
 *   - { name: bitrix.property.type }
 */
class InitCustomPropertiesType implements PostLoadingPassInterface
{
    /** @const string VARIABLE_PARAM_BAG Переменная в ParameterBag. */
    private const VARIABLE_PARAM_BAG = '_custom_bitrix_property';

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function action(Container $containerBuilder): bool
    {
        $result = false;

        try {
            $customPropertyServices = $containerBuilder->getParameter(self::VARIABLE_PARAM_BAG);
        } catch (InvalidArgumentException $e) {
            return $result;
        }

        if (empty($customPropertyServices)) {
            return $result;
        }

        foreach ($customPropertyServices as $service => $value) {
            /**
             * @var IblockPropertyTypeInterface $serviceInstance Обработчик.
             */
            $serviceInstance = $containerBuilder->get($service);

            $interfaces = class_implements($serviceInstance);
            if (!in_array(IblockPropertyTypeInterface::class, $interfaces, true)) {
                throw new RuntimePostLoadingPassException(
                    sprintf(
                        'Custom property type error. Class %s not implement IblockPropertyTypeInterface',
                        get_class($serviceInstance)
                    )
                );
            }
            $serviceInstance->init();
        }

        return $result;
    }
}
