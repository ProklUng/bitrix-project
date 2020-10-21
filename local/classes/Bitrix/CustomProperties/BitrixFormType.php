<?php

namespace Local\Bitrix\CustomProperties;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CForm;
use Local\Util\IblockPropertyType\Abstraction\IblockPropertyTypeBase;

/**
 * Class BitrixFormType
 * Кастомное поле привящка к форме.
 * @package Local\Bitrix\CustomProperties
 *
 * @since 17.10.2020
 */
class BitrixFormType extends IblockPropertyTypeBase
{
    /**
     * @var array $formsCache
     */
    private static $formsCache;

    /**
     * @inheritdoc
     */
    public function getPropertyType()
    {
        return self::PROPERTY_TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'Привязка к форме';
    }

    /**
     * @inheritdoc
     */
    public function getCallbacksMapping()
    {
        return [
            'GetAdminListViewHTML' => [$this, 'getAdminListViewHTML'],
            'GetPropertyFieldHtml' => [$this, 'getPropertyFieldHtml'],
            'GetAdminFilterHTML' => [$this, 'getAdminFilterHTML'],
            "GetUIFilterProperty" => [$this, "getUIFilterProperty"],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAdminListViewHTML(array $property, array $value, array $control)
    {
        return self::getFormName($value['VALUE'], $value['VALUE']);
    }

    /**
     * @inheritdoc
     */
    public function getPropertyFieldHtml(array $property, array $value, array $control)
    {
        return self::getFormFieldHtml($control['VALUE'], $value['VALUE']);
    }

    /**
     * Отображение фильтра в виде списка для новых гридов
     *
     * @param $arProperty
     * @param $strHTMLControlName
     * @param $fields
     */
    public function getUIFilterProperty($arProperty, $strHTMLControlName, &$fields)
    {
        $items = array_column($this->getFormList(), 'NAME', 'SID');

        $fields["type"] = "list";
        $fields["items"] = $items;
    }

    /**
     * @inheritdoc
     * @throws LoaderException
     */
    public function getAdminFilterHTML(array $property, array $control)
    {
        $curValue = '';
        if (isset($_REQUEST[$control['VALUE']])) {
            $curValue = $_REQUEST[$control['VALUE']];
        } elseif (isset($GLOBALS[$control['VALUE']])) {
            $curValue = $GLOBALS[$control['VALUE']];
        }

        return $this->getFormFieldHtml($control['VALUE'], $curValue);
    }

    /**
     * @param mixed $inputName
     * @param string $selectedValue
     * @param boolean $addEmpty
     *
     * @return string
     * @throws LoaderException
     */
    protected function getFormFieldHtml($inputName, $selectedValue = '', $addEmpty = true)
    {
        $items = self::getFormList();
        $input = '<select style="max-width:250px;" name="' . $inputName . '">';

        $input .= ($addEmpty) ? '<option value="">нет</option>' : '';

        foreach ($items as $item) {
            $selected = ($item['SID'] == $selectedValue) ? 'selected="selected"' : '';
            $input .= '<option ' . $selected . ' value="' . $item['SID'] . '">' . $item['NAME'] . '</option>';
        }

        $input .= '</select>';

        return $input;
    }

    /**
     * Название формы.
     *
     * @param $sid
     * @param string $default
     *
     * @return mixed|string
     *
     * @throws LoaderException
     */
    protected function getFormName($sid, $default = '')
    {
        if (!empty($sid)) {
            $forms = self::getFormList();
            return isset($forms[$sid]) ? $forms[$sid]['NAME'] : $default;
        }
        return $default;
    }

    /**
     * Список форм.
     *
     * @return array
     *
     * @throws LoaderException
     */
    protected function getFormList()
    {
        if (is_array(self::$formsCache)) {
            return self::$formsCache;
        }

        self::$formsCache = [];
        if (Loader::includeModule('form')) {
            $by = 's_name';
            $order = 'asc';
            $isFiltered = null;

            $dbres = CForm::GetList($by, $order, [], $isFiltered);
            while ($item = $dbres->Fetch()) {
                if (!empty($item['SID'])) {
                    self::$formsCache[$item['SID']] = $item;
                }
            }
        }

        return self::$formsCache;
    }
}