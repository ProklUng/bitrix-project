<?php

namespace Local\Util;

use CHTTP;

/**
 * Class Handler404
 * @package Local\Util
 *
 * @since 15.09.2020
 */
class Handler404
{
    /**
     * Чтобы Битрикс не рубил вызовы к API через Symfony Router.
     *
     * @return void
     */
    public function apiHandler()
    {
        $baseApiUrl = container()->getParameter('base.api.url');

        if (defined("ERROR_404")
            ||
            CHTTP::GetLastStatus() == "404 Not Found"
            &&
            strpos($_SERVER['REQUEST_URI'], $baseApiUrl) !== false) {
            CHTTP::SetStatus("The HTTP 200 OK");
            @define('ERROR_404', "N");
        }
    }
}
