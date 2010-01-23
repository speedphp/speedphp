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
 * spController 基础控制器程序父类 应用程序中的每个控制器程序都应继承于spController
 */
class spController { 

	/**
	 * 视图对象
	 */
	public $v;
	
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
		echo "<html><head><http-equiv='refresh' content='{$delay};url={$url}'></head><body></body></html>";
		exit;
    }
}

/**
 *
 * spUrl
 *
 * 控制器应用级扩展——URL模式的构造函数
 *
 */
function spUrl($controller = null, $action = null, $args = null, $anchor = null) {
	$controller = ( null != $controller ) ? $controller : $GLOBALS['G_SP']["default_controller"];
	$action = ( null != $action ) ? $action : $GLOBALS['G_SP']["default_action"];
	$url = "index.php?". $GLOBALS['G_SP']["url_controller"]. "={$controller}&";
	$url .= $GLOBALS['G_SP']["url_action"]. "={$action}";
	if(null != $args)foreach($args as $key => $arg) $url .= "&{$key}={$arg}";
	if(null != $anchor) $url .= "#".$anchor;
	return $url;
}