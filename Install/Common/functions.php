<?php
/*
 *	安装程序公共函数库
 * */

function delete_dir($dir) {
	$dir = get_dir_path($dir);
	if (!is_dir($dir)) return FALSE;
	$list = glob($dir.'*');
	foreach((array)$list as $v) {
		is_dir($v) ? delete_dir($v) : @unlink($v);
	}
	return @rmdir($dir);
}

// 获取客户端IP地址
function get_client_ip() {
	static $ip = NULL;
	if ($ip !== NULL) return $ip;
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$pos =  array_search('unknown',$arr);
		if(false !== $pos) unset($arr[$pos]);
		$ip   =  trim($arr[0]);
	}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	// IP地址合法验证
	$ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
	return $ip;
}

// 获取站点域名
function get_domain(){
	$result = '';
	
	// http://localhost/yourphp/install//index.php?step=3 	=>  http://localhost/yourphp
	
	// $_SERVER["REQUEST_URI"]:/install/index.php?step=3 	$_SERVER["PHP_SELF"]:/install/index.php
	$scriptName = !empty ($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : $_SERVER["PHP_SELF"];
	
	// ""
	$rootpath = @preg_replace("/\/(I|i)nstall\/index\.php(.*)$/", "", $scriptName);
	
	//  $_SERVER['HTTP_HOST']:localhost		$_SERVER['SERVER_NAME']:localhost
	$domain = !empty ($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST']  : $_SERVER['SERVER_NAME'] ;
	$domain = $domain.$rootpath;
	
	$result = "http://".$domain;

	/* echo "<pre>";
	 var_dump($scriptName,$rootpath,$domain,$this->domain);
	 exit(); */
	return $result;
}