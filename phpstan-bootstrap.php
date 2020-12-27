<?php

require_once __DIR__ . '/vendor/autoload.php';

@set_time_limit(0);

$_SERVER['DOCUMENT_ROOT'] = __DIR__. DIRECTORY_SEPARATOR . '..';
$GLOBALS['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'];

define('SITE_CHARSET', 'UTF-8');
define('SITE_ID', 's1');
