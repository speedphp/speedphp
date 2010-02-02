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

/**
 * dump  格式化输出变量程序
 * 
 * @param vars    变量
 * @param output    是否将内容输出
 * @param show_trace    是否将使用spError对变量进行追踪输出
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
function import($sfilename, $auto_search = TRUE, $auto_error = FALSE)
{
	if(isset($GLOBALS['G_SP']["import_file"][md5($sfilename)]))return TRUE;
	if( TRUE == @is_readable($sfilename) ){
		require($sfilename);
		$GLOBALS['G_SP']['import_file'][md5($sfilename)] = TRUE;
		return TRUE;
	}else{
		if(TRUE == $auto_search){
			foreach(array_merge( $GLOBALS['G_SP']['sp_include_path'],
										array($GLOBALS['G_SP']['model_path']), 
										 $GLOBALS['G_SP']['include_path'] ) as $sp_include_path){
				if(isset($GLOBALS['G_SP']["import_file"][md5($sp_include_path.'/'.$sfilename)]))return TRUE;
				if( is_readable( $sp_include_path.'/'.$sfilename ) ){
					require($sp_include_path.'/'.$sfilename);
					$GLOBALS['G_SP']['import_file'][md5($sp_include_path.'/'.$sfilename)] = TRUE;
					return TRUE;
				}
			}
		}
	}
	if( TRUE == $auto_error ){
		spError('未能找到名为：{$sfilename}的文件');
	}
	return FALSE;
}

/**
 * spAccess 数据缓存及存取程序
 * 
 * @param method    数据存取模式，取值"w"为存入数据，取值"r"读取数据，取值"c"为删除数据
 * @param name    标识数据的名称
 * @param value    存入的值，在读取数据和删除数据的模式下均为NULL
 * @param life_time    变量的生存时间，默认为永久保存
 */
function spAccess($method, $name, $value = NULL, $life_time = -1)
{
	if(!is_dir($GLOBALS['G_SP']['sp_cache']))__mkdirs($GLOBALS['G_SP']['sp_cache']);
	$sfile = $GLOBALS['G_SP']['sp_cache'].'/'.md5($name).".php";
	if('w' == $method){ // 写数据
		$life_time = ( -1 == $life_time ) ? '300000000' : $life_time;
		$value = '<?php die();?>'.( time() + $life_time ).serialize($value);
		return file_put_contents($sfile, $value);
	}elseif('c' == $method){ // 清除数据
		return @unlink($sfile);
	}else{ // 读数据
		if( !is_readable($sfile) )return FALSE;
		$arg_data = file_get_contents($sfile);
		if( substr($arg_data, 14, 10) < time() ){
			spAccess('c', $name);
			return FALSE;
		}
		return unserialize(substr($arg_data, 24));
	}
}

/**
 * spClass  类实例化函数  自动载入类定义文件，实例化并返回对象句柄
 * 
 * @param class_name    类名称
 * @param args   类初始化时使用的参数，数组形式
 * @param sdir 载入类定义文件的路径，可以是目录+文件名的方式，也可以单独是目录。sdir的值将传入import()进行载入
 */
function spClass($class_name, $args = null, $sdir = null)
{
	if(preg_match('/[^a-z0-9\-_.]/i', $class_name))spError($class_name."类名称错误，请检查。");
	// 检查是否该类已经实例化，直接返回已实例对象，避免再次实例化
	if(isset($GLOBALS['G_SP']["inst_class"][$class_name])){
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	// 如果$sdir不能读取，则测试是否仅路径
	if(null != $sdir && !import($sdir) && !import($sdir.'/'.$class_name.'.php'))return FALSE;

	$has_define = FALSE;
	// 类定义存在
	if(class_exists($class_name, false) || interface_exists($class_name, false)){
		$has_define = TRUE;
	}else{
		if( TRUE == import($class_name.'.php')){
			$has_define = TRUE;
		}
	}
	if(FALSE != $has_define){
		$GLOBALS['G_SP']["inst_class"][$class_name] = new $class_name($args);
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	spError($class_name."类定义不存在，请检查。");
}

/**
 * spError 框架定义的系统级错误提示
 * 
 * @param msg    出错信息
 * @param output    是否输出
 * @param stop    是否停止程序
 */
function spError($msg, $output = TRUE, $stop = TRUE){
	if(TRUE != SP_DEBUG)exit;
	$traces = debug_backtrace();
	$notice_html = SP_PATH."/Misc";
	$bufferabove = ob_get_clean();
	require($notice_html."/notice.php");
	if(TRUE == $stop)exit;
}
/**
 * spErrorHandler 系统错误提示函数
 * @param errno    出错类型
 * @param errstr    错误信息
 * @param errfile    出错的文件
 * @param errline    出错语句行号
 */
function spErrorHandler($errno, $errstr, $errfile, $errline) {
	if( E_ERROR == $errno || E_PARSE == $errno )spError($errstr);
}

/**
 * spLaunch  执行挂靠程序
 * 
 * @param configname    挂靠程序设置点名称
 */
function spLaunch($configname){
	if( isset($GLOBALS['G_SP']['launch'][$configname]) && is_array($GLOBALS['G_SP']['launch'][$configname]) ){
		foreach( $GLOBALS['G_SP']['launch'][$configname] as $launch ){
			if( is_array($launch) ){
				spClass($launch[0])->{$launch[1]}();
			}else{
				call_user_func($launch);
			}
		}
	}
}
/**
 *
 * T
 *
 * 多语言实现，翻译函数
 *
 * @param w    默认语言的词语
 *
 */
function T($w) {
	$method = $GLOBALS['G_SP']["lang"][spController::getLang()];
	if(!isset($method) || 'default' == $method){
		return $w;
	}elseif( function_exists($method) ){
		return ( $tmp = call_user_func($method, $w) ) ? $tmp : $w;
	}elseif( is_array($method) ){
		return ( $tmp = spClass($method[0])->{$method[1]}($w) ) ? $tmp : $w;
	}elseif( file_exists($method) ){
		$dict = require($method);
		return isset($dict[$w]) ? $dict[$w] : $w;
	}else{
		return $w;
	}
}

/**
 *
 * spUrl
 *
 * URL模式的构建函数
 *
 * @param controller    控制器名称，默认为配置'default_controller'
 * @param action    动作名称，默认为配置'default_action' 
 * @param args    传递的参数，数组形式
 * @param anchor    跳转锚点
 * @param no_sphtml    是否应用spHtml设置，在FALSE时效果与不启用spHtml相同。
 */
function spUrl($controller = null, $action = null, $args = null, $anchor = null, $no_sphtml = FALSE) {
	if(TRUE == $GLOBALS['G_SP']['html']["enabled"] && TRUE != $no_sphtml){
		// 当开启HTML生成时，将查找HTML列表获取静态文件名称。
		$realhtml = spClass($GLOBALS['G_SP']['html']['url_getter'][0])->{$GLOBALS['G_SP']['html']['url_getter'][1]}($controller, $action, $args, $anchor);
		if(isset($realhtml[0]))return $realhtml[0];
	}
	$controller = ( null != $controller ) ? $controller : $GLOBALS['G_SP']["default_controller"];
	$action = ( null != $action ) ? $action : $GLOBALS['G_SP']["default_action"];
	if( TRUE == $GLOBALS['G_SP']['url']["url_path_info"] ){ // 使用path_info方式
		$url = $GLOBALS['G_SP']['url']["url_path_base"]."/{$controller}/{$action}";
		if(null != $args)foreach($args as $key => $arg) $url .= "/{$key}/{$arg}";
	}else{
		$url = $GLOBALS['G_SP']['url']["url_path_base"]."?". $GLOBALS['G_SP']["url_controller"]. "={$controller}&";
		$url .= $GLOBALS['G_SP']["url_action"]. "={$action}";
		if(null != $args)foreach($args as $key => $arg) $url .= "&{$key}={$arg}";
	}
	if(null != $anchor) $url .= "#".$anchor;
	return $url;
}


/**
 * __mkdirs
 *
 * 循环建立目录的辅助函数
 *
 * @param dir    目录路径
 * @param mode    文件权限
 */
function __mkdirs($dir, $mode = 0777)
{
	if (!is_dir($dir)) {
		__mkdirs(dirname($dir), $mode);
		return @mkdir($dir, $mode);
	}
	return true;
}

/**
 * spExt
 *
 * 扩展类获取扩展配置的函数
 *
 * @param ext_node_name    扩展配置名
 */
function spExt($ext_node_name)
{
	return (empty($GLOBALS['G_SP']['ext'][$ext_node_name])) ? FALSE : $GLOBALS['G_SP']['ext'][$ext_node_name];
}

/**
 * spAddViewFunction
 *
 * 将函数注册到模板内使用，该函数可以是对象的方法，类的方法或是函数。
 *
 * @param alias    函数在模板内的别名
 * @param callback_function    回调的函数或方法
 */
function spAddViewFunction($alias, $callback_function)
{
	return $GLOBALS['G_SP']["view_registered_functions"][$alias] = $callback_function;
}

/**
 * json_decode/json_encode
 *
 * 兼容在未配置JSON扩展的情况下使用Services_JSON类
 *
 */
if ( !function_exists('json_decode') ){
	function json_decode($content, $assoc=false){
		if ( $assoc ){
			return spClass("Services_JSON",SERVICES_JSON_LOOSE_TYPE)->decode($content);
		} else {
			return spClass("Services_JSON")->decode($content);
		}
	}
}
if ( !function_exists('json_encode') ){
    function json_encode($content){return spClass("Services_JSON")->encode($content);}
}

/**
 * spConfigReady   快速将用户配置覆盖到框架默认配置
 * 
 * @param preconfig    默认配置
 * @param useconfig    用户配置
 */
function & spConfigReady(&$preconfig, &$useconfig = null){
	$nowconfig = $preconfig;
	if (is_array($useconfig)){
		foreach ($useconfig as $key => $val){
			if (is_array($useconfig[$key])){
				$nowconfig[$key] = is_array($nowconfig[$key]) ? spConfigReady($nowconfig[$key], $useconfig[$key]) : $useconfig[$key];
			}else{
				$nowconfig[$key] = $val;
			}
		}
	}
	return $nowconfig;
}