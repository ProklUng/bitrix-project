<?php

namespace Local\Bundles\BitrixOgGraphBundle\Services;

/**
 * Class AbstractProcessor
 * @package Local\Bundles\BitrixOgGraphBundle\Services
 *
 * @since 19.02.2021
 */
class AbstractProcessor
{
    /**
     * Проверка - HTTP или HTTPS.
     *
     * @return boolean
     */
    protected function isSecureConnection(): bool
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] === 443;
    }

    /**
     * Получить полный (включая https, домен) путь к канонической странице.
     *
     * @param string $url Укороченный URL (без домена).
     *
     * @return string
     */
    protected function getFullUrl(string $url = ''): string
    {
        $typeHttp = $this->isSecureConnection() ? 'https://' : 'http://';

        return $typeHttp . $_SERVER['HTTP_HOST'] . $url;
    }
}
