<?php

use Bitrix\Main\Application;
use Local\Services\Console\ConsoleCommandConfigurator;

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = __DIR__. DIRECTORY_SEPARATOR . '..';
$GLOBALS['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'];

define("LANGUAGE_ID", "pa");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("LOG_FILENAME", 'php://stderr');
define("BX_NO_ACCELERATOR_RESET", true);
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", "Y");
define("NO_AGENT_CHECK", true);
defined('PUBLIC_AJAX_MODE') || define('PUBLIC_AJAX_MODE', true);

/** @noinspection PhpIncludeInspection */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

// Альтернативный способ вывода ошибок типа "DB query error.":
$GLOBALS["DB"]->debug = true;

global $DB;
$app = Application::getInstance();
$con = $app->getConnection();
$DB->db_Conn = $con->getResource();

if (in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) === false) {
    echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.\PHP_SAPI.' SAPI'.\PHP_EOL;
}

if (!container()->has('console.command.manager')) {
    exit('Service console.command.manager not registered.');
}

/**
 * @var ConsoleCommandConfigurator $consoleCommandManager
 */
$consoleCommandManager = container()->get('console.command.manager')
    ->init();

$consoleCommandManager->run();

/** @noinspection PhpIncludeInspection */
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
