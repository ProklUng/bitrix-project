<?php
/**
 * Created by PhpStorm.
 * User: zakusilo.dv
 * Date: 18.10.2018
 * Time: 13:10
 */

namespace Local\Guta;

use CPHPCache;

/**
 * Class CacheManager
 * @package Local\Guta
 * @deprecated
 */
class CacheManager
{
    /**
     * Метод возвращает из кэша результат выполнения callback функции
     * @param $timeSeconds - время кэширования
     * @param $cacheId - ключ кэша
     * @param $callback - callback функция
     * @param array $arCallbackParams - параметры callback функции
     * @return mixed
     */
    public static function returnResultCache($cacheId, $callback, $arCallbackParams = [], $timeSeconds = 86400)
    {
        $result = null;

        $obCache = new CPHPCache();
        $cachePath = '/' . SITE_ID . '/' . $cacheId;
        if ($obCache->InitCache($timeSeconds, $cacheId, $cachePath)) {
            $vars = $obCache->GetVars();
            $result = $vars['result'];
        } elseif ($obCache->StartDataCache()) {
            $result = $callback($arCallbackParams);
            $obCache->EndDataCache(['result' => $result]);
        }
        return $result;
    }
}
