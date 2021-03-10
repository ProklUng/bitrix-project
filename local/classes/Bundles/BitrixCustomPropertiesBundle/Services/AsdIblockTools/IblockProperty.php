<?php

namespace Local\Bundles\BitrixCustomPropertiesBundle\Services\AsdIblockTools;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CUserFieldEnum;
use RuntimeException;

/**
 * Class IblockProperty
 * @package Local\Bundles\BitrixCustomPropertiesBundle\Services\AsdIblockTools
 *
 * @since 10.03.2021
 */
class IblockProperty
{
    /**
     * @const string UF_IBLOCK
     */
    public const UF_IBLOCK = 'ASD_IBLOCK';

    /**
     * @param integer $iblockId     ID инфоблока.
     * @param string  $codeProperty Свойство.
     *
     * @return mixed
     * @throws LoaderException
     */
    public function get(int $iblockId, string $codeProperty = '')
    {
        global $USER_FIELD_MANAGER;

        if (!Loader::includeModule('asd.iblock')) {
            throw new RuntimeException(
                'Модуль asd.iblock не установлен. Подключите его в админке.'
            );
        }

        $arReturn = [];
        $arUserFields = $USER_FIELD_MANAGER->GetUserFields(self::UF_IBLOCK, $iblockId, LANGUAGE_ID);

        foreach ($arUserFields as $FIELD_NAME => $arUserField) {
            if ($arUserField['USER_TYPE_ID'] === 'enumeration') {
                $arValue = [];
                $enum = new CUserFieldEnum();

                $rsSecEnum = $enum->GetList(
                    ['SORT' => 'ASC', 'ID' => 'ASC'],
                    ['USER_FIELD_ID' => $arUserField['ID'], 'ID' => $arUserField['VALUE']]
                );

                while ($arSecEnum = $rsSecEnum->Fetch()) {
                    $arValue[$arSecEnum['ID']] = $arSecEnum['VALUE'];
                }

                $arReturn[$FIELD_NAME] = $arValue;
            } else {
                $arReturn[$FIELD_NAME] = $arUserField['VALUE'];
            }
        }

        return $codeProperty === '' ? $arReturn : $arReturn[$codeProperty];
    }
}
