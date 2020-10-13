<?php

namespace Local\Services\Bitrix\WebForm;

use Bitrix\Main\Loader;

/**
 * Class FormResult
 * @package Local\Services\Bitrix\WebForm
 *
 * @since 13.10.2020
 */
class FormResult
{
    /**
     * Добавить в модуль веб-формы в форму данные
     *
     * @param int $WEB_FORM_ID id формы, для которой пришел ответ
     * @param array $arrVALUES = <pre>array (
     * [WEB_FORM_ID] => 3
     * [web_form_submit] => Отправить
     *
     * [form_text_18] => aafafsfasdf
     * [form_text_19] => q1241431342
     * [form_text_21] => afsafasdfdsaf
     * [form_textarea_20] =>
     * [form_text_22] => fasfdfasdf
     * [form_text_23] => 31243123412впывапвыапывпыв аывпывпыв
     *
     * 18, 19, 21 - ID ответов у вопросов https://yadi.sk/i/_9fwfZMvO2kblA
     * )</pre>
     *
     * @return bool | UpdateResult
     * @throws \Bitrix\Main\LoaderException
     */
    public static function formResultAddSimple($WEB_FORM_ID, $arrVALUES = [])
    {
        global $strError;

        if (!Loader::includeModule('form')) {
            return false;
        }

        // add result like bitrix:form.result.new
        $arrVALUES['WEB_FORM_ID'] = (int)$WEB_FORM_ID;
        if ($arrVALUES['WEB_FORM_ID'] <= 0) {
            return false;
        }

        $arrVALUES["web_form_submit"] = "Отправить";

        if ($RESULT_ID = \CFormResult::Add($WEB_FORM_ID, $arrVALUES)) {
            if ($RESULT_ID) {
                // send email notifications
                \CFormCRM::onResultAdded($WEB_FORM_ID, $RESULT_ID);
                \CFormResult::SetEvent($RESULT_ID);
                \CFormResult::Mail($RESULT_ID);

                return new UpdateResult(['RESULT' => $RESULT_ID, 'STATUS' => UpdateResult::STATUS_OK]);
            }

           return new UpdateResult(['RESULT' => $strError, 'STATUS' => UpdateResult::STATUS_ERROR]);
        }

        return false;
    }
}