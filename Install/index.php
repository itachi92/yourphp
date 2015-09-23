<?php
class Install{
	public $steps= array(
					'1'=>'安装许可协议',
					'2'=>'运行环境检测',
					'3'=>'安装参数设置',
					'4'=>'安装详细过程',
					'5'=>'安装完成'
				);
	
	public $site_dir = '';
	public $dir_helper = NULL;

	public function __construct() {
		header("Content-type:text/html;charset=utf-8");
		
		//	检测php版本
		if( phpversion() <'5.2.0' )
			exit('您的php版本过低，不能安装本软件，请升级到5.2.0或更高版本再安装，谢谢！');
		
		//	检查是否已经进行安装
		if (file_exists('../install.lock')){
			echo '你已经安装过该系统，如果想重新安装，请先删除站点根目录下的 install.lock 文件，然后再安装。';
			exit;
		}
		
		//	检查程序安装文件是否齐全
		$sqlFile = 'yourphp.sql';
		$configFile =  'config.php';
		if(!file_exists($sqlFile) || !file_exists($configFile)){
			echo '缺少必要的安装文件!';exit;
		}
		
		//	设置脚本最大执行时间
		@set_time_limit(1000);
		
		//	php版本低于5.3时，关闭魔术引用
		if(phpversion() <= '5.3.0')
			set_magic_quotes_runtime(0);
		
		//	设置默认时区
		date_default_timezone_set('PRC');
		
		//	设置错误级别
		error_reporting(E_ALL & ~E_NOTICE);
		
		//	包含公共函数库
		include_once 'Common/functions.php';

		// 包含目录辅助类
		include_once 'Common/directoryhelper.php';
		$this->dir_helper = new DirectoryHelper();

		// 当前应用根目录
		/*
		 * __FILE__:E:\wamp\www\epp\ed\Install\index.php
		 * dirname(__FILE__):E:\wamp\www\epp\ed\Install
		 * substr(dirname(__FILE__), 0,-8):E:\wamp\www\epp\ed
		 * get_dir_path(substr(dirname(__FILE__), 0, -8)):E:/wamp/www/epp/ed/
		 *
		 * 方法有待改善
		 * */
		$currentDir = substr(dirname(__FILE__), 0, -8);
		$this->site_dir  = $this->dir_helper->beauty_path($currentDir);
	}
	
	public function start() {
		$steps = $this->steps;
		
		$step = isset($_GET['step'])? $_GET['step'] : 1;
		
		// 根据url中的step参数拼接方法名
		$stepFunction = step.$step;
		$this->$stepFunction($step,$steps);
	}
	
	public function step1($step,$steps){
		include_once ('./templates/header.html');
		include_once ("./templates/s1.html");
		include_once ('./templates/footer.html');
	}
	
	public function step2($step,$steps){
		
		$data = array();
		
		//	服务器信息
		$data['server']['server_name'] = $_SERVER["SERVER_NAME"];//	服务器域名
		$data['server']['server_host'] = empty ($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_HOST"] : $_SERVER["SERVER_ADDR"];//	服务器主机
		$data['server']['os_info'] = php_uname();//	服务器操作系统信息
		$data['server']['server_software'] = $_SERVER["SERVER_SOFTWARE"];//	服务器解析引擎
		$data['server']['php_version'] = @ phpversion();//	PHP版本
		$data['server']['install_path'] = $this->site_dir;//	安装路径
		$data['server']['max_execution_time'] = ini_get('max_execution_time');//	脚本最大执行时间

		$server = array(
			'服务器域名/IP地址' => $data['server']['server_name']."/".$data['server']['server_host'],
			'服务器操作系统' => $data['server']['os_info'],
			'服务器解译引擎' => $data['server']['server_software'],
			'PHP版本' => $data['server']['php_version'],
			'安装路径' => $data['server']['install_path'],
			'脚本超时时间' => $data['server']['max_execution_time']."秒"
			);

		$data['server']['result'] = $server;

		$err = 0; //错误数记录

		//	环境要求：支持
		$gd = function_exists('gd_info') ? gd_info() : false;
		$data['surpport']['GD'] = $gd['GD Version'];// GD库支持
		
		$mysql = function_exists('mysql_connect') ? true : false;
		$data['surpport']['mysql'] = $mysql;// MySQL支持
		
		$upload = ini_get('file_uploads') ? ini_get('upload_max_filesize') : false;
		$data['surpport']['upload'] = $upload;//	文件上传支持

		$session = function_exists('session_start') ? true : false;
		$data['surpport']['session'] = $session;//	session支持

		foreach ($data['surpport'] as  $key => $item) {
			if ($item) {
				$surpport[$key] = "<font color=green>[√]On</font>";

				if ($key == 'GD') {
					$surpport[$key] = "<font color=green>[√]On</font>&nbsp;".$item;
				}

				if ($key == "upload") {
					$surpport[$key] = "<font color=green>[√]On</font>&nbsp;文件上传大小限制：".$item;
				}

			}else{
				$surpport[$key] = "<font color=red>[×]Off</font>";
				$err++;
			}
		}

		$data['surpport']['result'] = $surpport;// 检测结果

		//	目录创建、目录读写权限测试
		$folders = array (
				'/',
				'Uploads',
				'Public/Data',
				'Cache',
				'Cache/Html',
				'Cache/Cache',
				'Cache/Data',
				'Cache/Temp',
				'Cache/Logs'
		);

		// 1) 目录创建
		$status = $this->dir_helper->create_dir($folders,$this->site_dir);//	这里的Install仅用于测试，实际使用需要删除

		// 2) 目录写入、读取权限测试
		foreach ($status as $key => $value) {
			if ($value == 1) {
				// echo "$key <br />";
				$write_able[$key] = $this->dir_helper->dir_is_writable($key,$this->site_dir);//	目录写入测试
				$read_able[$key] = $this->dir_helper->dir_is_writable($key,$this->site_dir);//	目录读取测试

				if ($write_able[$key]) {
					$w = '<font color=green>[√]写</font>';
				}else{
					$w = '<font color=red>[×]写</font>';
					$err++;
				}

				if ($read_able[$key]) {
					$r = '<font color=green>[√]读</font>' ;
				} else {
					$r = '<font color=red>[×]读</font>';
					$err++;
				}

				//	目录权限测试结果
				$directory[$key] = $r."&nbsp;".$w;
			}
		}

		$data['directory']['writable'] = $write_able;//	用于测试使用
		$data['directory']['readable'] = $read_able;//	用于测试使用
		
		$data['directory']['result'] = $directory;//	检测结果

		// echo "<pre>";
		// print_r($data);
		// exit();
		include_once ('./templates/header.html');
		include_once ("./templates/s2.html");
		include_once ('./templates/footer.html');
		
	}
	
	public function step3($step,$steps){
		$data = array();

		if($_GET['testdbpwd']){
				$dbHost = $_POST['dbHost'].':'.$_POST['dbPort'];
				$conn = @mysql_connect($dbHost, $_POST['dbUser'], $_POST['dbPwd']);
				if($conn){
					echo json_encode(1);
					exit; 
				}else{
					echo json_encode(0);
					exit;
				}
			}
		
		$domain = get_domain();

		include_once ('./templates/header.html');
		include_once ("./templates/s3.html");
		include_once ('./templates/footer.html');
		
	}
	
	public function step4($step,$steps){
		$data = array();

		if ($_POST['dosubmit']) {
			// 对s3.html中提交过来的数据进行简单处理
			$data['config'] = $_POST;

			foreach ($data['config'] as $key => $item) 
			{
				$config[$key] = trim($item);
			}

			$data['result'] = $config;//转换后结果

			setcookie('config',serialize($config));

// 			echo "<pre>";
// 			print_r(unserialize($_COOKIE['config']));
// 			exit;
		}else{
			// 详细安装步骤：处理s4.html中的ajax提交
			$config = unserialize($_COOKIE['config']);
			// 包含Mysql操作辅助类
			require_once 'Common/mysqlhelper.php';
			
			$info = array();//	Ajax返回信息
			$n= intval(trim($_GET['n']));//	当前安装进度

			if ($n != 999999) {
				// 连接数据库测试
				$m_helper = new MysqlHelper();
				$status = $m_helper->connect($config['dbHost'],$config['dbPort'],$config['dbUser'],$config['dbPwd']);
				if(!$status){
					$info['msg'] = "数据库连接失败!";
					echo json_encode($info);exit;
				}

				// 数据库版本检测
				$min_v = 4.1;
				$status = $m_helper->version($min_v);
				if (!$status) {
					$info['msg'] = '数据库版本太低!';
					echo json_encode($info);exit;
				}

				// 选择数据库
				$status = $m_helper->select_db($config['dbName']);
				if (!$status) {
					// 数据库不存在，则创建数据库
					$b = $m_helper->create_db($config['dbName']);
					if ($b) {
						$m_helper->select_db($config['dbName']);
						$info['n']= 1;
						$info['msg'] = "成功创建数据库：".$config['dbName']."<br>";
						echo json_encode($info);exit;
					}else{
						$info['msg'] = '数据库 '.$config['dbName'].' 不存在，也没权限创建新的数据库！';
						echo json_encode($info);exit;
					}
				} 

				// 导入sql文件数据，创建数据表
				$sqlFile = './yourphp.sql';
				$sqls = $m_helper->sql_split($sqlFile, $config['dbPrefix']);// 格式化sql文件
			
				for ($i = $n,$len = count($sqls); $i < $len; $i++) {
					$data = $m_helper->sql_execute($sqls[$n-1]);// 执行数组中的sql语句
					$i = $i + 1;

					// 这里的$n与$n= intval(trim($_POST['n']));对应
					if ($data['status'] == 1) {
						$info['n'] = $i;
						$info['msg'] = "成功创建数据表：".$data['name']."<br/>";
						echo json_encode($info);exit;
					}else{
						$info['msg'] = "创建数据表失败：".$data['name']."<br/>";
						echo json_encode($info);exit;
					}
					
				}

				//	导入sql文件数据，添加data数据
				$sqlFile = './yourphp_data.sql';
				$sqls = $m_helper->sql_split($sqlFile, $config['dbPrefix']);// 格式化sql文件
				$data = $m_helper->insert_sqls_execute($sqls);
				if (!empty($data)) {
					$info['msg'] = "<span>data数据导入成功！</span><br />";
				}else{
					$info['msg'] = "<span>data数据导入失败！</span><br />";
				}
				
				// 导入sql文件数据，添加area数据
				$sqlFile = './yourphp_area.sql';
				$sqls = $m_helper->sql_split($sqlFile, $config['dbPrefix']);// 格式化sql文件
				$data = $m_helper->insert_sqls_execute($sqls);
				if (!empty($data)) {
					$info['msg'] .= "<span>area数据导入成功！</span><br />";
				}else{
					$info['msg'] .= "<span>area数据导入失败！</span><br />";
				}
				
				// 站点多语言设置、更新多语言配置
				if ($config['lang'] == 1) {
					$sqlFile = './yourphp_lang.sql';
					$sqls = $m_helper->sql_split($sqlFile, $config['dbPrefix']);// 格式化sql文件
					$data = $m_helper->insert_sqls_execute($sqls);
					if (!empty($data)) {
						$info['msg'] .= "<span>lang数据导入成功！</span><br />";
					}else{
						$info['msg'] .= "<span>lang数据导入失败！</span><br />";
					}
				}else{
					@unlink($this->site_dir.'index.php');
					@copy($this->site_dir.'Install/index_one.php',$this->site_dir.'index.php');
					mysql_query("UPDATE `{$config['dbPrefix']}menu` SET  `status` ='0'   WHERE model='Lang' ");
				}
				
				mysql_query("UPDATE `{$config['dbPrefix']}config` SET  `value` = '{$config['site_name']}' WHERE varname='site_name' and lang=1");
				mysql_query("UPDATE `{$config['dbPrefix']}config` SET  `value` = '{$config['site_url']}' WHERE varname='site_url' ");
				mysql_query("UPDATE `{$config['dbPrefix']}config` SET  `value` = '{$config['site_email']}' WHERE varname='site_email'");
				mysql_query("UPDATE `{$config['dbPrefix']}config` SET  `value` = '{$config['seo_description']}' WHERE varname='seo_description'  and lang=1");
				mysql_query("UPDATE `{$config['dbPrefix']}config` SET  `value` = '{$config['seo_keywords']}' WHERE varname='seo_keywords'  and lang=1");
				$info['msg'] .= "<span>配置修改成功！</span><br />";
				
				// 生成配置文件
				$configFile = "config.php";
				$strConfig = file_get_contents($this->site_dir.'Install/'.$configFile);
				$strConfig = str_replace('#DB_HOST#', $config['dbHost'], $strConfig);
				$strConfig = str_replace('#DB_NAME#', $config['dbName'], $strConfig);
				$strConfig = str_replace('#DB_USER#', $config['dbUser'], $strConfig);
				$strConfig = str_replace('#DB_PWD#', $config['dbPwd'], $strConfig);
				$strConfig = str_replace('#DB_PORT#', $config['dbPort'], $strConfig);
				$strConfig = str_replace('#DB_PREFIX#', $config['dbPrefix'], $strConfig);
				
				@file_put_contents($this->site_dir.'/'.$configFile, $strConfig);//	在站点根目录生成配置文件
				$info['msg'] .= "<span>成功生成配置文件！</span><br />";
				
				// 添加管理员
				$code=md5(time());
				$query = "UPDATE `{$dbPrefix}config` SET value='$code' WHERE varname='ADMIN_ACCESS'";//	管理员访问控制标志
				mysql_query($query);
				
				$time=time();
				$ip = get_client_ip();
				$password = hash ( sha1, $password.$code );
				$query = "INSERT INTO `{$dbPrefix}user` (`groupid`, `username`, `password`, `realname`, `email`, `createtime`, `updatetime`, `reg_ip`, `status`) VALUES( 1, '{$config['username']}', '{$config['password']}', '{$config['username']}', '{$config['site_email']}', '$time', '$time', '$ip', '1')";
				mysql_query($query);
				$info['msg'] .= "<span>管理员添加成功！</span><br />";
				
				$info=array('n'=>999999,'msg'=>$info['msg']);
				echo json_encode($info);exit;
			}

		}
		
		include_once ('./templates/header.html');
		include_once ("./templates/s4.html");
		include_once ('./templates/footer.html');
	}
	
	public function step5($step,$steps){
		$domain = get_domain();
		
		@touch('../install.lock');
		include_once ('./templates/header.html');
		include_once ("./templates/s5.html");
		include_once ('./templates/footer.html');
	}
	
	function test() {
		echo "悄悄是别离的笙箫，沉默是今晚的康桥";
	}
	

	
}

$install = new Install();
$install->start();