<?php

namespace Local\Bundles\BitrixCustomPropertiesBundle\Services\CustomProperties;

use CFileMan;
use CUserTypeManager;
use Local\Bundles\BitrixCustomPropertiesBundle\Services\CustomProperties\Abstracts\AbstractUserTypeProperty;

/**
 * Class CCustomTypeHtml
 * @package Local\Bundles\BitrixCustomPropertiesBundle\Services\CustomProperties
 */
class CCustomTypeHtml extends AbstractUserTypeProperty
{
    /**
     * @inheritDoc
     */
    public function GetUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID' => 'customhtml',
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => 'HTML множественное поле',
            "BASE_TYPE" => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    /**
     * @inheritDoc
     */
    public function GetEditFormHTML(array $arUserField, array $arHtmlControl) : string
    {
        if ($arUserField["ENTITY_VALUE_ID"] < 1 && $arUserField["SETTINGS"]["DEFAULT_VALUE"] !== '') {
            $arHtmlControl["VALUE"] = htmlspecialcharsbx($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
        }
        if ($arUserField["SETTINGS"]["ROWS"] < 8) {
            $arUserField["SETTINGS"]["ROWS"] = 8;
        }

        if ($arUserField['MULTIPLE'] === 'Y') {
            $name = preg_replace("/[\[\]]/i", "_", $arHtmlControl["NAME"]);
        } else {
            $name = $arHtmlControl["NAME"];
        }

        ob_start();

        CFileMan::AddHTMLEditorFrame(
            $name,
            $arHtmlControl["VALUE"],
            $name."_TYPE",
            $arHtmlControl["VALUE"] !== '' ? "html" : "text",
            [
                'height' => $arUserField['SETTINGS']['ROWS'] * 10,
            ]
        );

        if ($arUserField['MULTIPLE'] === 'Y') {
            echo '<input type="hidden" name="'.$arHtmlControl["NAME"].'" >';
        }

        return ob_get_clean();
    }

    public function OnBeforeSave(array $arUserField, $value)
    {
        if ($arUserField['MULTIPLE'] === 'Y') {
            foreach ($_POST as $key => $val) {
                if (preg_match("/".$arUserField['FIELD_NAME']."_([0-9]+)_$/i", $key, $m)) {
                    $value = $val;
                    unset($_POST[$key]);
                    break;
                }
            }
        }

        return $value;
    }
}
