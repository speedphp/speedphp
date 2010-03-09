<?php
/////////////////////////////////////////////////////////////////////////////
//
// SpeedPHP - 快速的中文PHP框架
//
// Copyright (c) 2008 - 2009 SpeedPHP.com All rights reserved.
//
// 许可协议请查看 http://www.speedphp.com/
//
/////////////////////////////////////////////////////////////////////////////

define('SP_VERSION', '2.3.8'); // 当前框架版本

/**
 * spCore
 *
 * SpeedPHP应用框架的系统执行程序
 */

// 定义系统路径
if(!defined('SP_PATH')) define('SP_PATH', dirname(__FILE__).'/SpeedPHP');
if(!defined('APP_PATH')) define('APP_PATH', dirname(__FILE__).'/app');

// 载入核心函数库
require(SP_PATH."/spFunctions.php");
if (substr(PHP_VERSION, 0, 1) != '5')spError("The SpeedPHP Framework Require PHP5!");

// 载入配置文件
$GLOBALS['G_SP'] = spConfigReady(require(SP_PATH."/spConfig.php"),$spConfig);

// 根据配置文件进行一些全局变量的定义
if('debug' == $GLOBALS['G_SP']['mode']){
	define("SP_DEBUG",TRUE); // 当前正在调试模式下
}else{
	define("SP_DEBUG",FALSE); // 当前正在部署模式下
}
// 设置错误处理函数
set_error_handler("spErrorHandler");
@set_magic_quotes_runtime(0);

// 自动开启SESSION
if($GLOBALS['G_SP']['auto_session'])session_start();

// 载入核心MVC架构文件
import($GLOBALS['G_SP']["sp_core_path"]."/spController.php", FALSE, TRUE);
import($GLOBALS['G_SP']["sp_core_path"]."/spModel.php", FALSE, TRUE);
import($GLOBALS['G_SP']["sp_core_path"]."/spView.php", FALSE, TRUE);

// 当在二级目录中使用SpeedPHP框架时，自动获取当前访问的文件名
if("/index.php" == $GLOBALS['G_SP']['url']["url_path_base"]){
	if(basename($_SERVER['SCRIPT_NAME']) === basename($_SERVER['SCRIPT_FILENAME']))
		$GLOBALS['G_SP']['url']["url_path_base"] = $_SERVER['SCRIPT_NAME'];
	elseif (basename($_SERVER['PHP_SELF']) === basename($_SERVER['SCRIPT_FILENAME']))
		$GLOBALS['G_SP']['url']["url_path_base"] = $_SERVER['PHP_SELF'];
	elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === basename($_SERVER['SCRIPT_FILENAME']))
		$GLOBALS['G_SP']['url']["url_path_base"] = $_SERVER['ORIG_SCRIPT_NAME'];
}

// 在使用PATH_INFO的情况下，对路由进行预处理
if(TRUE == $GLOBALS['G_SP']['url']["url_path_info"] && !empty($_SERVER['PATH_INFO'])){
	$url_args = explode("/", $_SERVER['PATH_INFO']);$url_sort = array();
	for($u = 1; $u < count($url_args); $u++){
		if($u == 1)$url_sort[$GLOBALS['G_SP']["url_controller"]] = $url_args[$u];
		elseif($u == 2)$url_sort[$GLOBALS['G_SP']["url_action"]] = $url_args[$u];
		else {$url_sort[$url_args[$u]] = isset($url_args[$u+1]) ? $url_args[$u+1] : "";$u+=1;}}
	if("POST" == strtoupper($_SERVER['REQUEST_METHOD'])){$_REQUEST = $_POST =  $_POST + $url_sort;
	}else{$_REQUEST = $_GET = $_GET + $url_sort;}
}

// 构造执行路由
$__controller = isset($_REQUEST[$GLOBALS['G_SP']["url_controller"]]) ? 
	$_REQUEST[$GLOBALS['G_SP']["url_controller"]] : 
	$GLOBALS['G_SP']["default_controller"];
$__action = isset($_REQUEST[$GLOBALS['G_SP']["url_action"]]) ? 
	$_REQUEST[$GLOBALS['G_SP']["url_action"]] : 
	$GLOBALS['G_SP']["default_action"];

// 对路由进行自动执行相关操作
spLaunch("router_prefilter");

$handle_controller = spClass($__controller, null, $GLOBALS['G_SP']["controller_path"].'/'.$__controller.".php");
// 调用控制器出错将调用路由错误处理函数
if(!is_object($handle_controller) || !method_exists($handle_controller, $__action)){
	eval($GLOBALS['G_SP']["dispatcher_error"]);
	exit;
}

// 执行用户代码
$handle_controller->$__action();

// 控制器程序运行完毕，进行模板的自动输出
if(FALSE != $GLOBALS['G_SP']['view']['auto_display']){
	$__tplname = $GLOBALS['G_SP']['view']['config']['template_dir']."/".
		$__controller.$GLOBALS['G_SP']['view']['auto_display_sep'].
			$__action.$GLOBALS['G_SP']['view']['auto_display_suffix'];
	$handle_controller->v->auto_display($__tplname);
}

