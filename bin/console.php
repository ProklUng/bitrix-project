<?php

use Local\Services\Console\ConsoleCommandConfigurator;

$_SERVER["DOCUMENT_ROOT"] = __DIR__. DIRECTORY_SEPARATOR . '..';

define("LANGUAGE_ID", "pa");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("LOG_FILENAME", 'php://stderr');
define("BX_NO_ACCELERATOR_RESET", true);
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", "Y");
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// Альтернативный способ вывода ошибок типа "DB query error.":
$GLOBALS["DB"]->debug = true;

global $DB;
$app = \Bitrix\Main\Application::getInstance();
$con = $app->getConnection();
$DB->db_Conn = $con->getResource();

if (false === in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
    echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.\PHP_SAPI.' SAPI'.\PHP_EOL;
}

/**
 * @var ConsoleCommandConfigurator $consoleCommandManager
 */
$consoleCommandManager = container()->get('console.command.manager')
                                    ->init();

$consoleCommandManager->run();

