<?php

namespace Local\Bundles\BitrixCustomPropertiesBundle\Services\CustomProperties;

use CGroup;
use Local\Bundles\BitrixCustomPropertiesBundle\Services\IblockPropertyType\Abstraction\IblockPropertyTypeNativeInterface;

/**
 * Class GroupUser
 * Группа пользователей
 *
 * @package Local\Bundles\BitrixCustomPropertiesBundle\Services\CustomProperties
 *
 * @since 10.02.2021
 */
class GroupUser implements IblockPropertyTypeNativeInterface
{
    public function init() : void
    {
        AddEventHandler(
            "iblock",
            "OnIBlockPropertyBuildList",
            [__CLASS__, "GetUserTypeDescription"]
        );
    }

    public static function GetUserTypeDescription()
    {
        return [
            "PROPERTY_TYPE" => "N",
            "USER_TYPE" => "USER_GROUP",
            "DESCRIPTION" => "Привязка к группе пользователей",
            "CheckFields" => [__CLASS__, "CheckFields"],
            "GetLength" => [__CLASS__, "GetLength"],
            "GetPropertyFieldHtml" => [__CLASS__, "GetPropertyFieldHtml"],
            "GetAdminListViewHTML" => [__CLASS__, "GetAdminListViewHTML"],
            "GetPublicViewHTML" => [__CLASS__, "GetPublicViewHTML"],
            "GetSearchContent" => [__CLASS__, "GetSearchContent"],
        ];
    }

    public static function CheckFields(array $arProperty, array $value)
    {
        $arResult = [];
        if ((int)$value['VALUE']) {
            $by = "c_sort";
            $order = "asc";
            $groups = CGroup::GetList($by, $order, ["ACTIVE" => "Y"]);
            $bFound = false;
            while ($arGroup = $groups->Fetch()) {
                if ($arGroup['ID'] == $value['VALUE']) {
                    $bFound = true;
                }
            }
            if (!$bFound) {
                $arResult[] = "Группа пользователей не найдена";
            }
        }

        return $arResult;
    }

    public static function GetLength($arProperty, $value)
    {
        if (is_array($value) && array_key_exists('VALUE', $value)) {
            return strLen(trim($value['VALUE']));
        }

        return 0;
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $rsGroups = CGroup::GetList($by, $order, ["ACTIVE" => "Y"]);
        ob_start();
        ?>
        <select name="<?= $strHTMLControlName['VALUE'] ?>">
            <option value="">Выбрать</option>
            <? while ($arGroup = $rsGroups->Fetch()):?>
                <option
                    value="<?= $arGroup['ID'] ?>"<?= ($value['VALUE'] == $arGroup['ID'] ? " selected=\"selected\"" : "") ?>>
                    [<?= $arGroup['ID'] ?>] <?= $arGroup["NAME"] ?></option>
            <?endwhile; ?>
            ?>
        </select>
        <?
        $result = ob_get_clean();

        return $result;
    }

    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        $group_id = intval($value['VALUE']);
        if ($group_id) {
            $arGroup = CGroup::GetByID($value['VALUE'])->Fetch();

            return "[{$arGroup['ID']}] ".htmlspecialcharsex($arGroup["NAME"]);
        }

        return "&nbsp;";
    }

    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        $group_id = (int)$value['VALUE'];
        if ($group_id) {
            $arGroup = CGroup::GetByID($value['VALUE'])->Fetch();

            return "[{$arGroup['ID']}] ".htmlspecialcharsex($arGroup["NAME"]);
        }

        return "&nbsp;";
    }

    public static function GetSearchContent($arProperty, $value, $strHTMLControlName)
    {
        if (strlen($value['VALUE']) > 0) {
            return $value['VALUE'];
        }

        return '';
    }
}
