<?php

use framework\dotenv;

define('DOCUMENT_ROOT', dirname(__FILE__) . '/');
define('TEMPLATE_PATH', '/template');
define('CSS_PATH', TEMPLATE_PATH . '/css/');
define('JS_PATH', TEMPLATE_PATH . '/js/');
define('ENV_FILE', dirname(__FILE__) . '/.env');

spl_autoload_register(function ($class) {
    if (mb_strpos($class, 'pdf') !== false)
        require_once \DOCUMENT_ROOT . 'Dompdf/autoload.inc.php';
    else
        include \DOCUMENT_ROOT . str_replace('\\', '/', $class) . '.php';
});

error_reporting(E_ALL);
ini_set('display_errors', 1);

$offset = 3;
$is_DST = false;
$timezone_name = timezone_name_from_abbr('', $offset * 3600, $is_DST);
date_default_timezone_set($timezone_name);

unset($offset, $is_DST, $timezone_name);

$dotenv = new dotenv;
if (!$dotenv->load(ENV_FILE)) exit();
unset($dotenv);