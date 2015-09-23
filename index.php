<?php
/**
 *
 * index(入口文件)
 *
 * @package      	YOURPHP
 * @author          liuxun QQ:147613338 <web@yourphp.cn>
 * @copyright     	Copyright (c) 2008-2011  (http://www.yourphp.cn)
 * @license         http://www.yourphp.cn/license.txt
 * @version        	YourPHP企业网站管理系统 v2.1 2011-03-01 yourphp.cn $
 */

if (!is_file('./config.php')) header("location: ./Install");

header("Content-type: text/html; charset=utf-8");

define('YOURPHP', 'YourPHP');
define('UPLOAD_PATH', './Uploads/');
define('VERSION', 'v2.1 Released');
define('UPDATETIME', '20120306');
define('APP_NAME', 'Yourphp');
define('APP_PATH', './Yourphp/');

define('APP_LANG', true);
define('APP_DEBUG',true);//	开启调试模式
define('THINK_PATH','./ThinkPHP/');

require(THINK_PATH.'/ThinkPHP.php');
?>