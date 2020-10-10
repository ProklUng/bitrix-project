<?php

namespace Local\Seo;

use Bitrix\Main\Application;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;
use Kir\StringUtils\Matching\Wildcards\Pattern;

/**
 * Class KostylRedirector
 * @package Local\Seo
 */
class KostylRedirector
{
    /**
     * @var Uri $uriManager;
     */
    private $uriManager;

    /**
     * @var string[] Паттерны редиректов.
     */
    private $patterns = [
        '*index.php' => 'index.php',
        '*index.html' => 'index.html',
        '*index.htm' => 'index.htm',
    ];

    private $request;

    /**
     * @var string $uri URL.
     */
    private $uri;

    /**
     * KostylRedirector constructor.
     *
     * @throws SystemException
     */
    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest();
        // Текущий URL.
        $this->uri = $this->request->getRequestUri();

        $this->uriManager = new Uri($this->uri);
    }

    /**
     * Обработка редиректов.
     *
     * @return boolean
     */
    public function treatment(): bool
    {
        if (getenv('DEBUG', false)) {
            return true;
        }

        if (empty($this->uri)) {
            return false;
        }

        // www not www.
        $this->wwwToNonWww();

        $this->uppercaseToLower($this->uri);

        foreach ($this->patterns as $redirectWildCard => $redirectItem) {
            if (Pattern::create($redirectWildCard)->match($this->uri)) {
                $redirect = str_replace($redirectItem, '', $this->uri);
                $this->redirect301($redirect);

                // Для целей тестирования верну финальный url.
                return $redirect;
            }
        }

        return true;
    }

    /**
     * Uppercase to lowercase.
     *
     * @param string $url Исходный URL.
     *
     * @return void
     */
    private function uppercaseToLower(string $url): void
    {
        // If URL contains a period, halt (likely contains a filename and filenames are case specific)
        if (preg_match('/[\.]/', $url)) {
            return;
        }

        // If URL contains a question mark, halt (likely contains a query variable)
        if (preg_match('/[\?]/', $url)) {
            return;
        }

        if (preg_match('/[A-Z]/', $url)) {
            $this->redirect301(strtolower($url));
        }
    }

    /**
     * www to non-www redirect.
     *
     * @return void
     */
    private function wwwToNonWww(): void
    {
        // www not www.
        if (substr($this->request->getHttpHost(), 0, 4) === 'www.') {
            $url = $this->uriManager->getScheme() .'://'.substr($this->request->getHttpHost(), 4) . $this->uri;

            $this->redirect301($url);

            exit;
        }
    }

    /**
     * Редирект.
     *
     * @param string $sUrl Текущий URL.
     *
     * @return void
     */
    private function redirect301(string $sUrl): void
    {
        LocalRedirect($sUrl, 301, '301 Moved permanently');
    }
}
