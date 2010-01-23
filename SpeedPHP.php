<?php
/////////////////////////////////////////////////////////////////////////////
//
// SpeedPHP - 快速的中文PHP框架
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
 * 单入口应用程序请在应用程序的入口文件中包含本文件。
 *
 * 多入口应用程序需要在每个入口处包含本文件。
 *
 */

// 记录程序开始执行的时间
$GLOBALS['sp_time_start'] = microtime();

// 定义系统路径

if(!defined('SP_PATH')) define('SP_PATH', dirname(__FILE__));
if(!defined('APP_PATH')) define('APP_PATH', dirname(SP_PATH).'/app');

// 载入配置文件
$GLOBALS['G_SP'] = spConfigReady(require(SP_PATH."/spConfig.php"),$spConfig);


// 载入核心MVC架构文件
import($GLOBALS['G_SP']["sp_core_path"]."/spController.php", FALSE);
import($GLOBALS['G_SP']["sp_core_path"]."/spModel.php", FALSE);
import($GLOBALS['G_SP']["sp_core_path"]."/spView.php", FALSE);

// 根据配置文件进行一些全局变量的定义
if('debug' == $GLOBALS['G_SP']['mode']){
	define("SP_DEBUG",TRUE); // 当前正在调试模式下
}else{
	define("SP_DEBUG",FALSE); // 当前正在部署模式下
}
define('SP_VERSION', '2.0.876'); // 定义当前框架版本

// 如果是调试模式，打开警告输出
if (SP_DEBUG) {
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
} else {
	error_reporting(0);
}

// 自动开启SESSION
if($GLOBALS['G_SP']['auto_session'])session_start();

// 如果使用PATH_INFO
if(TRUE == $GLOBALS['G_SP']['url']["url_path_info"] && !empty($_SERVER['PATH_INFO'])){
	$url_args = explode("/", $_SERVER['PATH_INFO']);$url_sort = array();
	for($u = 1; $u < count($url_args); $u++){
		if($u == 1)$url_sort[$GLOBALS['G_SP']["url_controller"]] = $url_args[$u];
		elseif($u == 2)$url_sort[$GLOBALS['G_SP']["url_action"]] = $url_args[$u];
		else {$url_sort[$url_args[$u]] = isset($url_args[$u+1]) ? $url_args[$u+1] : "";$u+=1;}}
	if("POST" == strtoupper($_SERVER['REQUEST_METHOD'])){$_REQUEST = $_POST =  $_POST + $url_sort;
	}else{$_REQUEST = $_GET = $_GET + $url_sort;}
}

// 转向控制器，执行用户级代码
$__controller = isset($_REQUEST[$GLOBALS['G_SP']["url_controller"]]) ? 
	$_REQUEST[$GLOBALS['G_SP']["url_controller"]] : 
	$GLOBALS['G_SP']["default_controller"];
$__action = isset($_REQUEST[$GLOBALS['G_SP']["url_action"]]) ? 
	$_REQUEST[$GLOBALS['G_SP']["url_action"]] : 
	$GLOBALS['G_SP']["default_action"];

$handle_controller = spClass($__controller, null, $GLOBALS['G_SP']["controller_path"].'/'.$__controller.".php");
// 调用控制器出错将调用路由错误处理函数
if(!is_object($handle_controller) || !method_exists($handle_controller, $__action)){
	eval($GLOBALS['G_SP']["dispatcher_error"]);
	exit;
}

// 对路由进行自动执行相关操作
spLaunch("router_prefilter");

// 执行用户代码
$handle_controller->$__action();


// 控制器程序运行完毕，进行模板的自动输出
if(FALSE != $GLOBALS['G_SP']['view']['auto_display']){
	$__tplname = $GLOBALS['G_SP']['view']['config']['template_dir']."/".
		$__controller.$GLOBALS['G_SP']['view']['auto_display_sep'].
			$__action.$GLOBALS['G_SP']['view']['auto_display_suffix'];
	$handle_controller->v->auto_display($__tplname);
}

/**
 * dump  格式化输出变量程序
 * 
 * @param vars    变量
 * @param output    是否将内容输出
 */
function dump($vars, $output = TRUE, $show_trace = FALSE)
{
	if(TRUE != SP_DEBUG && TRUE != $GLOBALS['G_SP']['allow_trace_onrelease'])exit;
	if( TRUE == $show_trace ){
		$content = spError(htmlspecialchars(print_r($vars, true)), TRUE, FALSE);
	}else{
		$content = "<div align=left><pre>\n" . htmlspecialchars(print_r($vars, true)) . "\n</pre></div>\n";
	}
    if(TRUE != $output) { return $content; }
    echo $content;
    return null;
}

/**
 * import  载入包含文件
 * 
 * @param filename    需要载入的文件名或者文件路径
 * @param auto_search    载入文件找不到时是否搜索系统路径或文件，搜索路径的顺序为：应用程序包含目录 -> 应用程序Model目录 -> sp框架包含文件目录
 * @param auto_error    自动提示扩展类载入出错信息
 */
function import($filename, $auto_search = TRUE, $auto_error = FALSE)
{
	if(isset($GLOBALS['G_SP']["import_file"][md5($filename)]))return TRUE;
	if( is_readable($filename) ){
		require($filename);
		$GLOBALS['G_SP']['import_file'][md5($filename)] = TRUE;
		return TRUE;
	}else{
		if(TRUE == $auto_search){
			foreach(array_merge( $GLOBALS['G_SP']['sp_include_path'],
										array($GLOBALS['G_SP']['model_path']), 
										 $GLOBALS['G_SP']['include_path'] ) as $sp_include_path){
				if(isset($GLOBALS['G_SP']["import_file"][md5($sp_include_path.'/'.$filename)]))return TRUE;
				if( is_readable( $sp_include_path.'/'.$filename ) ){
					require($sp_include_path.'/'.$filename);
					$GLOBALS['G_SP']['import_file'][md5($sp_include_path.'/'.$filename)] = TRUE;
					return TRUE;
				}
			}
		}
	}
	if( TRUE == $auto_error ){
		spError('未能找到名为：{$filename}的文件');
	}
	return FALSE;
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
	$file = $GLOBALS['G_SP']['sp_cache'].'/'.md5($name).".php";
	if('w' == $method){ // 写数据
		$life_time = ( -1 == $life_time ) ? '300000000' : $life_time;
		$value = '<?php die();?>'.( time() + $life_time ).serialize($value);
		return file_put_contents($file, $value);
	}elseif('c' == $method){ // 清除数据
		return @unlink($file);
	}else{ // 读数据
		if( !is_readable($file) )return FALSE;
		$arg_data = file_get_contents($file);
		if( substr($arg_data, 14, 10) < time() ){
			spAccess('c', $name);
			return FALSE;
		}
		return unserialize(substr($arg_data, 24));
	}
}

/**
 * spClass  类实例化程序  提供自动载入类定义文件，实例化并返回对象句柄的功能
 * 
 * @param class_name    类名
 * @param args   类初始化时使用的参数，请以数组形式输入
 * @param dir
 */
function spClass($class_name, $args = null, $dir = null)
{
	// 检查是否该类已经实例化，直接返回已实例对象，避免再次实例化
	if(isset($GLOBALS['G_SP']["inst_class"][$class_name])){
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	// 如果$dir不能读取，则测试是否仅路径
	if(null != $dir && !import($dir) && !import($dir.'/'.$class_name.'.php'))return FALSE;

	$has_define = FALSE;
	// 类定义存在
	if(class_exists($class_name, false) || interface_exists($class_name, false)){
		$has_define = TRUE;
	}else{
		if( TRUE == import($sp_include_path.'/'.$class_name.'.php')){
			$has_define = TRUE;
		}
	}
	if(FALSE != $has_define){
		$GLOBALS['G_SP']["inst_class"][$class_name] = & new $class_name($args);
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	spError($class_name."类定义不存在，请检查。");
}

/**
 * spError  系统级错误提示
 * 
 * @param msg    出错信息
 * @param output    是否输出
 * @param stop    是否停止程序
 */
function spError($msg, $output = TRUE, $stop = TRUE){
	if(TRUE != SP_DEBUG && FALSE != $stop)exit;
	$traces = debug_backtrace();
	$notice_html = SP_PATH."/Misc";
	$bufferabove = ob_get_clean();
	require($notice_html."/notice.php");
	if(TRUE == $stop)exit;
}

function spLaunch($configname){
	if( is_array($GLOBALS['G_SP']['launch'][$configname]) ){
		foreach( $GLOBALS['G_SP']['launch'][$configname] as $launch ){
			if( is_array($launch) ){
				spClass($launch[0])->{$launch[1]}();
			}else{
				call_user_func($launch);
			}
		}
	}
}

function & spConfigReady(&$preconfig, &$useconfig = null){
	$nowconfig = $preconfig;
	if (is_array($useconfig))
		foreach ($useconfig as $key => $val)
			if (is_array($useconfig[$key]))
				$nowconfig[$key] = is_array($nowconfig[$key]) ? spConfigReady($nowconfig[$key], $useconfig[$key]) : $useconfig[$key];
			else
				$nowconfig[$key] = $val;
	return $nowconfig;
}

