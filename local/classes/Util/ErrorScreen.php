<?php

namespace Local\Util;

use CMain;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ErrorScreen
 * @package Local\Util
 */
class ErrorScreen
{
    /** @const string ERROR_PAGE Страница для вывода ошибок. */
    private const ERROR_PAGE = '/errorScreen.php';
    /** @const string ERROR_PAGE Тэг под замену текстом сообщения об ошибке. */
    private const ERROR_MESSAGE_TAG = '%error_message%';

    /**
     * @var LoaderContent Загрузчик контента.
     */
    protected $loaderContent;

    /**
     * @var CMain Экземпляр $APPLICATION.
     */
    protected $application;

    /**
     * ErrorScreen constructor.
     *
     * @param LoaderContent|null $loaderContent Загрузчик контента.
     * @param CMain|null         $application   Экземпляр $APPLICATION.
     */
    public function __construct(
        LoaderContent $loaderContent = null,
        CMain $application = null
    ) {
        $this->loaderContent = $loaderContent;
        $this->application = $application;
    }

    /**
     * Показать экран смерти.
     *
     * @param string $message Сообщение об ошибке.
     *
     * @return bool
     */
    public function die(string $message = '') : ?bool
    {
        if (!empty($_SESSION['PHPUNIT_RUNNING']) && $_SESSION['PHPUNIT_RUNNING'] === true) {
            echo $message;
            return false;
        }

        $content = $this->prepareErrorScreen($message);

        $this->application->RestartBuffer();
        echo $content;

        die();
    }

    /**
     * Подготовить контент страницы.
     *
     * @param string $message Сообщение об ошибке.
     *
     * @return string
     */
    private function prepareErrorScreen(string $message) : string
    {
        $content = $this->loaderContent->getContentPage(Request::createFromGlobals(), self::ERROR_PAGE);

        return str_replace(self::ERROR_MESSAGE_TAG, $message, $content);
    }
}
