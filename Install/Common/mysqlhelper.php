<?php
/**
* 
*/
class MysqlHelper
{
	public $dbHost = 'localhost';
	public $dbPort = 3306;
	public $dbUser = "root";
	public $dbPwd = "root";
	public $dbName = "";
	public $conn = NULL;
	public $version = '';

	public function __construct()
	{
		
	}

	/**
	 * 连接MySQL数据库
	 * @param string $dbHost
	 * @param string $dbPort
	 * @param string $dbUser
	 * @param string $dbPwd
	 * @return resource|boolean  
	 * */
	public function connect($dbHost="",$dbPort="",$dbUser="",$dbPwd="")
	{
		$dbHost = empty($dbHost) ? $this->dbHost : $dbHost;
		$dbPort = empty($dbPort) ? $this->dbPort : $dbPort;
		$dbUser = empty($dbUser) ? $this->dbUser : $dbUser;
		$dbPwd = empty($dbPwd) ? $this->dbPwd : $dbPwd;

		$dbHost = $dbHost.":".$dbPort;

		$conn = mysql_connect($dbHost,$dbUser,$dbPwd);
		if ($conn) 
		{
			mysql_query("SET NAMES 'utf8'");
			return $this->conn = $conn;
		}
		
		return FALSE;
	}

	/**
	 * 比较MySQL版本
	 * @param real $v 版本号
	 * @return string|boolean  
	 * */
	public function version($v = 4.1)
	{
		$this->version = mysql_get_server_info($this->conn);

		if ($this->version > $v) 
		{
			return $this->version;
		}

		return false;
	}


	/**
	 * 选择数据库
	 * @param string $dbName 	数据库名
	 * @return boolean|number  
	 * */
	public function select_db($dbName = NULL)
	{
		if (empty($dbName)) 
		{
			return false;// 缺少必要参数
		}

		return mysql_select_db($dbName,$this->conn) ? 1 : 0;
	}
	
	/**
	 * 创建数据库
	 * @param string $dbName 	数据库名
	 * @return boolean|number  
	 * */
	function create_db($dbName = NULL) 
	{
		if (empty($dbName)) 
		{
			return FALSE;
		}
		
		$query = "CREATE DATABASE IF NOT EXISTS `".$dbName."` DEFAULT CHARSET utf8 COLLATE utf8_general_ci;";
		$res = mysql_query($query,$this->conn);
		
		return $res ? 1 : 0;
	}


	/**
	 * 将传入的sql文件分割成可以执行的sql语句数组
	 * @param string $sqlFile 	sql后缀的文件名称
	 * @param string $dbPrefix 	数据表前缀
	 * @return boolean|array 	可执行的sql语句数组
	 * */
	function sql_split($sqlFile,$dbPrefix) 
	{
		if (empty($sqlFile)) 
		{
			return FALSE;
		}
		
		$sqlFileContent = file_get_contents($sqlFile);
		
		//	替换表前缀
		if($dbPrefix != "yourphp_")
		{
			$sqlFileContent = str_replace("yourphp_", $dbPrefix, $sqlFileContent);
		}
		
		// 替换内容
		$sqlFileContent = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8",$sqlFileContent);
		$sqlFileContent = str_replace("\r", "\n", $sqlFileContent);
		
		// 拆分成一个完整的create语句（SQL语句）
		$queriesarray = explode(";\n", trim($sqlFileContent));
		unset($sqlFileContent);
		
		$sqlsArray = array();
		$num = 0;
		foreach($queriesarray as $query)
		{
			$sqlsArray[$num] = '';
			// 拆分成行
			$queries = explode("\n", trim($query));
			
			//  删除$queries数组中所有等值为false的条目
			$queries = array_filter($queries);
		
			foreach($queries as $query)
			{
				// 	去掉sql文件中的注释部分
				$str1 = substr($query, 0, 1);
				if($str1 != '#' && $str1 != '-')
					$sqlsArray[$num] .= $query;// 拼接所有符合条件的sql语句
			}
			$num++;
		}
		return $sqlsArray;// 可执行的sql语句，数组
	}
	
	/**
	 * 批量执行sql_split生成的sql数组（create语句）
	 * @param string $sqlsArray
	 * @return boolean|number|array
	 * $data[0] = array(
	 * 	'name' => 't_access',
	 *  'status' => 1
	 * ) 
	 * */
	function sql_execute($sqlsArray = NULL) 
	{
		if (empty($sqlsArray))
			return FALSE;
		
		$data = array();
		
		if (is_string($sqlsArray)) 
		{
			$sql = trim($sqlsArray);
			
			if (strstr($sql, 'CREATE TABLE'))
			{//  判断sql数组中是否有Create table语句
				
				preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
			
				// $matches[0] = "CREATE TABLE `your_access`";
				// $matches[1] = "your_access";
				mysql_query("DROP TABLE IF EXISTS `$matches[1]");// 如果数据表已存在，则删除
			
				$data['name'] = $matches[1];
				// 执行sql文件中的单条sql语句
				mysql_query($sql,$this->conn) ? $data['status'] = 1 : $data['status'] = 0;
			}
			return $data;
		}
		
		if (is_array($sqlsArray)) 
		{
			$sqlsCount = count($sqlsArray);
			for ($i=0; $i < $sqlsCount; $i++) 
			{
				$sql = trim($sqlsArray[$i]);
		
				if (strstr($sql, 'CREATE TABLE'))
				{//  判断sql数组中是否有Create table语句
					
					preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
						
					// $matches[0] = "CREATE TABLE `your_access`";
					// $matches[1] = "your_access";
					mysql_query("DROP TABLE IF EXISTS `$matches[1]");// 如果数据表已存在，则删除
		
					$data[$i]['name'] = $matches[1];
					// 执行sql文件中的单条sql语句
					mysql_query($sql,$this->conn) ? $data[$i]['status'] = 1 : $data[$i]['status'] = 0;
				}
			}
			
			return $data;
		}
		
		return 0;
	}
	
	/**
	 * 批量执行insert语句
	 * @param string $sqlsArray，格式化后的
	 * @return number|boolean  
	 * */
	function insert_sqls_execute($sqlsArray = NULL) {
		if (empty($sqlsArray)) {
			return -1;
		}
		
		$result = array();
		
		if (is_string($sqlsArray)) {
			$sql = trim($sqlsArray);
			mysql_query($sql,$this->conn);
			if (mysql_affected_rows() > 0) {
				return TRUE;
			}
		}
		
		if (is_array($sqlsArray)) {
			foreach ($sqlsArray as $k => $sql) {
				$sql = trim($sql);
				mysql_query($sql,$this->conn);
				$result[$k]['status'] = (mysql_affected_rows() > 0) ?  1 : 0;
			}
			
			return $result;
		}
		
		return FALSE;
	}
}


// $mysql_helper = new MysqlHelper();
// $conn = $mysql_helper->connect();
// $a = $mysql_helper->select_db('test');
// $b = $mysql_helper->sql_split('yourphp.sql', 'tt_');
// $c = $mysql_helper->sql_execute($b);



// echo "<pre>";
// print_r($a);
// print_r($c);






?>