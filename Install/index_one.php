<?php

if (!is_file('./config.php')) header("location: ./Install");
header("Content-type: text/html; charset=utf-8");
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
define('YOURPHP', 'YourPHP');
define('UPLOAD_PATH', './Uploads/');
define('VERSION', 'v2.1 Released');
define('UPDATETIME', '20120306');
define('APP_NAME', 'Yourphp');
define('APP_PATH', './Yourphp/');
define('APP_LANG', false);
define('APP_DEBUG',false);
define('THINK_PATH','./Core/');
require(THINK_PATH.'/Core.php');
?>