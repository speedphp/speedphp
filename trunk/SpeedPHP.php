<?php
/////////////////////////////////////////////////////////////////////////////
//
// SpeedPHP - 快速的中文PHP应用框架
//
// Copyright (c) 2008 - 2009 SpeedPHP.com All rights reserved.
//
// 许可协议请查看 http://www.speedphp.com/
//
// 作者：jake（jake@speedphp.com）
//
/////////////////////////////////////////////////////////////////////////////


/**
 *
 * spCore
 *
 * 初始化程序及基础命令集，提供应用程序最基础的程序执行机制及相关命令。
 *
 */

// 记录程序开始执行的时间
$GLOBALS['G_SP']['sp_time_start'] = microtime();

// 定义系统路径

if(!defined('SP_PATH')) define('SP_PATH', dirname(__FILE__));
if(!defined('APP_PATH')) define('APP_PATH', dirname(SP_PATH).'/app');

// 载入配置文件
if(isset($spConfig)){
	$GLOBALS['G_SP'] = array_merge(require(SP_PATH."/spConfig.php"),$spConfig);
}else{
	$GLOBALS['G_SP'] = require(SP_PATH."/spConfig.php");
}

// 载入核心MVC架构文件
import($GLOBALS['G_SP']["sp_core_path"]."/spController.php");
import($GLOBALS['G_SP']["sp_core_path"]."/spModel.php");
import($GLOBALS['G_SP']["sp_core_path"]."/spView.php");

// 根据配置文件进行一些全局变量的定义
if('debug' == $GLOBALS['G_SP']['mode']){
	define("SP_DEBUG",TRUE); // 当前正在调试模式下
}else{
	define("SP_DEBUG",FALSE); // 当前正在部署模式下
}
define('SP_VERSION', '1.0.2'); // 定义当前框架版本

// 自动开启SESSION
session_start();

// 如果是调试模式，打开警告输出
if (SP_DEBUG) {
    error_reporting(error_reporting(0) & ~E_STRICT);
} else {
    error_reporting(0);
}




// 转向控制器，执行用户级代码
$__controller = isset($_GET[$GLOBALS['G_SP']["url_controller"]]) ? 
	$_GET[$GLOBALS['G_SP']["url_controller"]] : 
	$GLOBALS['G_SP']["default_controller"];
$__action = isset($_GET[$GLOBALS['G_SP']["url_action"]]) ? 
	$_GET[$GLOBALS['G_SP']["url_action"]] : 
	$GLOBALS['G_SP']["default_action"];
$handle_controller = spClass($__controller, null, $GLOBALS['G_SP']["controller_path"].'/'.$__controller.".php");
// 调用控制器出错将调用路由错误处理函数
if(!is_object($handle_controller) || !method_exists($handle_controller, $__action)){
	eval($GLOBALS['G_SP']["dispatcher_error"]);
	exit;
}
// 加载视图对象
$handle_controller->v = spClass('spView');

// 执行用户代码
$handle_controller->$__action();

/**
 * dump  格式化输出变量程序
 * 
 * @param vars    变量
 * @param output    是否将内容输出
 */
function dump($vars, $output = TRUE)
{
	$content = "<pre>\n";
	$content .= htmlspecialchars(print_r($vars, TRUE));
	$content .= "\n</pre>\n";
    if(TRUE != $output) { return $content; }
    echo $content;
    return null;
}

/**
 * import  载入包含文件
 * 
 * @param file    需要载入的文件路径
 */
function import($file)
{
	if(isset($GLOBALS['G_SP']["import_file"][md5($file)]))return TRUE;
	if( is_readable($file) ){
		require($file);
		$GLOBALS['G_SP']['import_file'][md5($file)] = TRUE;
		return TRUE;
	}else{
		return FALSE;
	}
}


/**
 * spAccess  高速数据存取程序  正常情况下使用文件系统缓存
 * 
 * @param method    存取方向，取值"w"为存入数据，取值"r"读取数据
 * @param name    标识数据的名称
 * @param value    存入数据的值
 * @param life_time    变量的生存时间
 */
function spAccess($method, $name, $value = NULL, $life_time = -1)
{
	if('w' == $method){ // 写数据
		$file = $GLOBALS['G_SP']['sp_cache'].'/'.md5($name);
		$life_time = ( -1 == $life_time ) ? '300000000' : $life_time;
		$value = '<?php die();?>'.( time() + $life_time ).serialize($value);
		return file_put_contents($file, $value);
	}elseif('c' == $method){ // 清除数据
		$file = $GLOBALS['G_SP']['sp_cache'].'/'.md5($name);
		return @unlink($file);
	}else{ // 读数据
		$file = $GLOBALS['G_SP']['sp_cache'].'/'.md5($name);
		if( !is_readable($file) )return FALSE;
		$arg_data = file_get_contents($file);
		if( substr($arg_data, 14, 11) < time() ){
			spAccess('c', $name);
			return FALSE;
		}
		return unserialize($arg_data, 25);
	}
}

/**
 * spClass  类实例化程序  提供自动载入类定义文件，实例化并返回对象句柄的功能
 * 
 * @param class_name    类名
 * @param args   类初始化时使用的参数，请以数组形式输入
 * @param dir    载入类定义文件的路径或文件
 */
function spClass($class_name, $args = null, $dir = '')
{
	// 检查是否该类已经实例化，直接返回已实例对象，避免再次实例化
	if(isset($GLOBALS['G_SP']["inst_class"][$class_name])){
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	// 如果$dir不能读取，则测试是否仅路径
	if('' != $dir && !is_file($dir))$dir = $dir.'/'.$class_name.'.php';
	if('' != $dir && !import($dir))return FALSE;
	$has_define = FALSE;
	// 类定义存在
	if(class_exists($class_name, false) || interface_exists($class_name, false)){
		$has_define = TRUE;
	}else{
		if( TRUE == import($GLOBALS['G_SP']['model_path'].'/'.$class_name.'.php')){
			$has_define = TRUE;
		}
	}
	if(FALSE != $has_define){
		$GLOBALS['G_SP']["inst_class"][$class_name] = & new $class_name($args);
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	die("不能寻找到类定义: ".$class_name);
}

/**
 * spGetConfig  获取系统配置，亦可作为应用程序自定义配置的存取程序
 * 
 * @param vars    配置标识名
 */
function spGetConfig($vars)
{
	return $GLOBALS['G_SP'][$vars];
}

/**
 * spSetConfig  设置系统配置，亦可作为应用程序自定义配置的存取程序
 * 
 * @param vars    配置标识名，也可以是配置文件的路径
 * @param value    值
 */
function spSetConfig($vars, $value = "")
{
	$GLOBALS['G_SP'][$vars] = $value;
}
