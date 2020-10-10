<?php

namespace Local\Util\Dictionaries;

/**
 * Class DefaultValidationMessages
 * @package Local\Util\Dictionaries
 */
class DefaultValidationMessages extends AbstractDictionary
{
    /**
     * @return string[]
     */
    public static function getItems(): array
    {
        return  [
            'required' => 'Не указано поле :attribute',
            'numeric' => 'Поле :attribute должно содержать числовое значение',
        ];
    }
}
