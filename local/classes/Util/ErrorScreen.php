<?php

namespace Local\Util;

use CMain;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ErrorScreen
 * @package Local\Util
 *
 * @since 16.03.2021 Легкий рефакторинг.
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
     * @var string $pathErrorHandler Путь к файлу, выводящему ошибки.
     */
    private $pathErrorHandler;

    /**
     * ErrorScreen constructor.
     *
     * @param LoaderContent $loaderContent    Загрузчик контента.
     * @param CMain         $application      Экземпляр $APPLICATION.
     * @param string        $pathErrorHandler Путь к файлу, выводящему ошибки.
     *
     * @throws RuntimeException Файл-шаблон не найден.
     */
    public function __construct(
        LoaderContent $loaderContent,
        CMain $application,
        string $pathErrorHandler = ''
    ) {
        $this->loaderContent = $loaderContent;
        $this->application = $application;
        $this->pathErrorHandler = $pathErrorHandler ?: static::ERROR_PAGE;

        if (!file_exists($_SERVER['DOCUMENT_ROOT' . $this->pathErrorHandler])) {
            throw new RuntimeException(
                'Файл-шаблон ' . $this->pathErrorHandler . ' вывода ошибок не существует'
            );
        }
    }

    /**
     * Показать экран смерти.
     *
     * @param string $message Сообщение об ошибке.
     *
     * @return boolean
     */
    public function die(string $message = '') : ?bool
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL') && defined('__PHPUNIT_PHAR__')) {
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
        $content = $this->loaderContent->getContentPage(Request::createFromGlobals(), $this->pathErrorHandler);

        return str_replace(self::ERROR_MESSAGE_TAG, $message, $content);
    }
}
