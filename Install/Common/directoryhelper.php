<?php
/**
* 
*/
class DirectoryHelper
{
	/**
	 * 将路径中的"\\"转换成"/"，末尾没有"/"的话添加"/"
	 * @param  string $path，路径字符串
	 * @example E:\wamp\www\epp\ed 经转换后 E:/wamp/www/epp/ed/
	 * dirname(__FILE__):E:\wamp\www\epp\ed\	这种形式的路径是通过dirname获取得到的，php代码中并不能直接使用
	 * beauty_path(dirname(__FILE__)):E:/wamp/www/epp/ed/		这种形式的路径，php代码中可以直接使用
	 * 如，include "E:/wamp/www/epp/ed/directory.class.php";
	 * @return string $path，“美化”后的路径字符串
	 */
	public function beauty_path($path)
	{
		$path = str_replace('\\', '/', $path);
		if(substr($path, -1) != '/') $path = $path.'/';
		return $path;
	}

	/**
	 * Ⅰ.根据传入的路径创建目录：传入单个字符串则创建单个目录，传入数组则循环创建多级目录
	 * Ⅱ.目录模式
	 * @param string path，创建目录的路径，为空则表示在当前目录下创建目录
	 * @param array&string $dirs，目录字符串
	 * @param number $mode
	 * @example
	 *	$folders = array (
	 *			'Uploads',
	 *			'Public/Data',
	 *			'Cache',
	 *			'Cache/Html',
	 *			'Cache/Cache',
	 *	);
	 *
	 *	$path = "E:/test";
     *
	 *	// $data = createDir($folders,$path);//传入路径$path
	 *	$data = createDir($folders);//不传入路径$pathinfo(path)
	 * @return 单个字符串路径时返回1,0；多个路径组成的字符串数组时返回数组
	 * Array
	 *(
	 *    [/] => 1
	 *    [Uploads] => 1
	 *    [Public/Data] => 1
	 *    [Cache] => 1
	 *    [Cache/Html] => 1
	 *    [Cache/Cache] => 1
	 *    [Cache/Data] => 1
	 *    [Cache/Temp] => 1
	 *    [Cache/Logs] => 1
	 *)
	 * 
	 * */
	public function create_dir($dirs,$path = "./",$mode=0777)
	{
		if (empty($dirs)) {
			return false;//	必要参数不能为空
		}

		// 传入路径字符串
		if (is_string($dirs)) {
			
			if (is_dir($dirs)) return true;

			/*if(substr($path, -1) != '/') $path = $path.'/';//	$path = "E:/test";	=>	$path = "E:/test/";

			$dirs = str_replace('\\', '/', $dirs);
			if(substr($dirs, -1) != '/') $dirs = $dirs.'/';//	Uploads	=>	Uploads/*/

			$path = $this->beauty_path($path);
			$dirs = $this->beauty_path($dirs);

			$dirs = $path.$dirs;//	$dirs = "E:/test/Uploads/";
// echo "$dirs <br />";
			$dir_info = explode('/', $dirs);
			$current_dir = '';

			for ($i=0,$len=count($dir_info)-1; $i < $len; $i++) {
				$current_dir .= $dir_info[$i].'/';
				if (@is_dir($current_dir)) continue;

				@mkdir($current_dir,$mode,ture);
				@chmod($current_dir, $mode);
			}

			if (is_dir($current_dir)) {
				$data[$current_dir] = 1;//	目录创建成功
			}else{
				$data[$current_dir] = 0;//	目录创建失败
			}

			return $data[$current_dir];
		}

		// 传入路径数组
		if (is_array($dirs)) {
			foreach ($dirs as $dir) {
				$data[$dir] = $this->create_dir($dir,$path);
			}

			return $data;
		}

		return false;
	}


	public function dir_is_writable($dirs,$path = "./",$type = 0)
	{
		if (empty($dirs)) {
			return false;//	必要参数不能为空
		}

		$data = "";

		if (is_string($dirs)) {
			$dirs = $this->beauty_path($dirs);
			$path = $this->beauty_path($path);

			$dirs = $path.$dirs;

			$tfile = "write_test.txt";

			$fp = @fopen( $dirs.$tfile,"w");

			if (!$fp ){
				return 0;
			}

			fwrite($fp, "This directory($dirs) is writable!");
			fclose( $fp );

			
			if ($type == 1) {
				$rs = 1;// 作测试使用，$type的值为1时保留生成的测试文件
			}else{
				$rs = @unlink( $dirs."/".$tfile );
			}

			if ($rs){
				return $data[$dirs] = 1;
				// return true;
			}
			return false;
		}

		if (is_array($dirs)) {
			foreach ($dirs as $dir) {
				$data[$dir] = $this->dir_is_writable($dir,$path,$type);
			}

			return $data;
		}

		return $data;
	}

	public function dir_is_readable($dirs,$path = "./")
	{
		if (empty($dirs)) {
			return false;
		}

		if (is_string($dirs)) {
			$dirs = $this->beauty_path($dirs);
			$path = $this->beauty_path($path);

			$dirs = $path.$dirs;

			if (is_readable($dirs)) {
				return $data[$dirs] = 1;
			}

			return 0;
		}


		if (is_array($dirs)) {
			foreach ($dirs as $dir) {
				$data[$dir] = $this->dir_is_readable($dir,$path);
			}

			return $data;
		}
	}


}

/*$dir_obj = new DirectoryHelper();
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

$path = "E:/test23";*/

/*创建目录*/
// $data = $dir_obj->create_dir($folders);//不传入路径$path
// $data = $dir_obj->create_dir($folders,$path);//传入路径$path


/*目录写入测试*/
// $data = $dir_obj->dir_is_writable($folders,$path);

/*目录读取测试*/
/*$data = $dir_obj->dir_is_readable($folders,$path);
echo "<pre>";
print_r($data);*/



/**
 * 优化方法：
 * 方法中有很多重复的操作，如，“美化”路径，判断传入的参数是字符串还是数组等。
 * 可以考虑将这些相同的代码提取出来，放到构造函数中，实例化对象时自动进行这些操作。
 * 
 * 实例化对象时，传入多个参数：方法中需要的参数；调用哪个方法；返回结果存放到哪个成员变量中（并不直接返回结果，将处理结果存放到成员变量中）
 * 通过构造函数来处理不同方法中相同的操作，由构造函数来调用方法，调用方法返回的结果通过成员变量来使用
 */
?>
