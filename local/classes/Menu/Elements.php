<?php

namespace Local\Menu;

use Exception;
use Local\Models\Fabric\ModelElementFabric;

/**
 * Class Elements
 * Получение данных
 * меню из элементов инфоблока
 * @package Local\Menu
 */
class Elements
{
    /**
     * Получить свойства элемента.
     * @param integer $iblockId ID инфоблока.
     *
     * @return array Совместим со стандартным форматом *_menu.ext Битрикса.
     */
    public static function getMenuItems(int $iblockId): array
    {
        /**
         * Результрующий массив с элементами меню раздела.
         */
        $arMenuLinksByElement = [];

        try {
            /**
             * Получаем модель по номеру инфоблока.
             */
            $obFabricModels = new ModelElementFabric();
            $obModel = $obFabricModels->model($iblockId);
        } catch (Exception $exception) {
            /**
             * Если не вышло (пришел запрос на класс, не описанный
             * в фабрике, то не шумим и возвращаем пустой массив)
             */
            return [];
        }


        /**
         * Выборка из инфоблока
         */
        $obData = $obModel::query()
            ->filter(['ACTIVE' => 'Y'])
            ->sort(['SORT' => 'ASC'])
            ->select(['NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'])
            ->fetchUsing('GetNext')// Т.к. Fetch не отдает готовые URL элементов
            ->getList();

        /**
         * Формируем структуру выходного массива,
         * совместимую со стандартом *_menu.ext
         * Битрикса
         */
        $obData->each(
            function ($item) use (&$arMenuLinksByElement) {
                $arMenuLinksByElement[] = [
                    $item['NAME'],
                    $item['DETAIL_PAGE_URL'],
                    [],
                    [
                        'FROM_IBLOCK' => true,
                        'IS_PARENT' => false,
                        'DEPTH_LEVEL' => 1,
                    ],
                    ''
                ];
            }
        );

        return $arMenuLinksByElement;
    }
}
