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
 * spController 基础控制器程序父类 应用程序中的每个控制器程序都应继承于spController
 */
class spController { 

	/**
	 * 视图对象
	 */
	public $v;

	public function __construct()
	{	
		if(TRUE == $GLOBALS['G_SP']['view']['enabled']){
			$this->v = & spClass('spView');
		}
	}
    /**
     *
     * 跳转程序
     *
     * 应用程序的控制器类可以覆盖该函数以使用自定义的跳转程序
     *
     * @param <string> $url  需要前往的地址
     * @param <int> $delay   延迟时间
     */
    public function jump($url, $delay = 0){
		echo "<html><head><meta http-equiv='refresh' content='{$delay};url={$url}'></head><body></body></html>";
		exit;
    }

    /**
     *
     * 错误提示程序
     *
     * 应用程序的控制器类可以覆盖该函数以使用自定义的错误提示
     *
     * @param <mixed> $msg   错误提示需要的相关信息
     * @param <mixed> $url   跳转地址
     */
    public function error($msg, $url){
		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>function sptips(){alert(\"{$msg}\");location.href=\"{$url}\";}</script></head><body onload=\"sptips()\"></body></html>";
		exit;
    }

    /**
     *
     * 成功提示程序
     *
     * 应用程序的控制器类可以覆盖该函数以使用自定义的成功提示
	 *
     * @param <mixed> $msg   成功提示需要的相关信息
     * @param <mixed> $url   跳转地址
     */
    public function success($msg, $url){
		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>function sptips(){alert(\"{$msg}\");location.href=\"{$url}\";}</script></head><body onload=\"sptips()\"></body></html>";
		exit;
    }

	/**
	 * 魔术函数，获取赋值作为模板内变量
	 */
	private function __set($name, $value)
	{
		if(TRUE == $GLOBALS['G_SP']['view']['enabled']){
			$this->v->getView()->assign(array($name=>$value));
		}
	}
	
	/**
	 * 输出模板
	 */
	public function display($tplname, $output = TRUE)
	{
		if(TRUE == $GLOBALS['G_SP']['view']['enabled']){
			$this->v->display($tplname, $output);
		}else{
			require($tplname);
		}
		if( TRUE != $output )return ob_get_clean();
	}

	/**
	 * 魔术函数，实现对控制器扩展类的自动加载
	 */
	public function __call($name, $args)
	{
		if(in_array($name, $GLOBALS['G_SP']["auto_load_controller"])){
			return spClass($name, &$this)->__input($args);
		}elseif(!method_exists( $this, $name )){
			spError("method {$name} not defined");
		}
	}

	/**
	 * 获取视图的smarty对象
	 */
	public function & getView()
	{
		return $this->v->getView();
	}
	
	public function setLang($lang)
	{
		if( array_key_exists($lang, $GLOBALS['G_SP']["lang"]) ){
			@ob_start();
			$domain = ('www.' == substr($_SERVER["HTTP_HOST"],0,4)) ? substr($_SERVER["HTTP_HOST"],4) : $_SERVER["HTTP_HOST"];
			setcookie("SpLangCookies", $lang, time()+31536000, '/', $domain ); // 一年过期
			$_SESSION["SpLangSession"] = $lang;
			return TRUE;
		}
		return FALSE;
	}
	
	public function getLang()
	{
		if( !isset($_COOKIE['SpLangCookies']) )return $_SESSION["SpLangSession"];
		return $_COOKIE['SpLangCookies'];
	}
}

/**
 * spArgs 
 * 应用程序变量类
 * spArgs是封装了$_SESSION、$_GET/$_POST、$_COOKIE、$_SERVER、$_FILES、$_ENV等，提供一些简便的访问和使用这些
 * 全局变量的方法。
 */

class spArgs {
	/**
	 * 在内存中保存的变量
	 */
	private $args = null;

	/**
	 * 构造函数
	 *
	 */
	public function __construct($args){
		$this->args = $_REQUEST;
	}
	
	/**
	 * 获取应用程序请求变量值，获取顺序是由：$_SESSION -> $_GET -> $_POST-> $_COOKIE -> $_SERVER ->
	 * $_FILES -> $_ENV。 同时也可以指定获取的变量所属。
	 * 
	 * @param name    获取的变量名称，如果为空，则返回全部的请求变量
	 * @param default    当前获取的变量不存在的时候，将返回的默认值
	 * @param method    获取位置，取值GET，POST，COOKIE
	 */
	public function get($name = null, $default = null, $method = null)
	{
		if(null != $name){
			if( $this->has($name) ){
				if( null != $method ){
					switch (strtolower($method)) {
						case 'get':
							return $_GET[$name];
						case 'post':
							return $_POST[$name];
						case 'cookie':
							return $_COOKIE[$name];
					}
				}
				return $this->args[$name];
			}else{
				return (null === $default) ? FALSE : $default;
			}
		}else{
			return $this->args;
		}
	}

	/**
	 * 检测是否存在某值
	 * 
	 * @param name    检验的变量名
	 */
	public function has($name)
	{
		return isset($this->args[$name]) && !empty($this->args[$name]);
	}

	/**
	 * 构造输入函数，标准用法
	 */
	public function __input($args = -1)
	{
		if( -1 == $args )return $this;
		list( $name, $default, $method ) = $args;
		return $this->get($name, $default, $method);
	}
	
	/**
	 * 获取请求字符
	 */
	public function request(){
		return $_SERVER["QUERY_STRING"];
	}
}


/**
 *
 * T
 *
 * 多语言实现，翻译函数
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
		return $dict[$w];
	}else{
		return $w;
	}
}

/**
 *
 * spUrl
 *
 * URL模式的构造函数
 *
 */
function spUrl($controller = null, $action = null, $args = null, $anchor = null, $no_sphtml = FALSE) {
	if(TRUE == $GLOBALS['G_SP']['html']["enabled"] && TRUE != $no_sphtml){
		if( function_exists($GLOBALS['G_SP']['html']['url_getter']) ){
			$realhtml = call_user_func_array($GLOBALS['G_SP']['html']['url_getter'], array($controller, $action, $args, $anchor));
		}elseif( is_array($GLOBALS['G_SP']['html']['url_getter']) ){
			$realhtml = spClass($GLOBALS['G_SP']['html']['url_getter'][0])->{$GLOBALS['G_SP']['html']['url_getter'][1]}($controller, $action, $args, $anchor);
		}
		if($realhtml)return $realhtml;
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