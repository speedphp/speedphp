<?php
/**
 * spUrlRewrite 类，以扩展形式支持SpeedPHP框架URL_REWRITE的扩展。
 *
 * 该扩展的使用，首先要确定服务器开启URL_REWRITE功能，并且在.htaccess中已经有以下的内容
 *
 * .htaccess是针对当前应用程序的
 *
 * <IfModule mod_rewrite.c>
 * RewriteEngine On
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteCond %{REQUEST_FILENAME} !-d
 * RewriteRule ^(.*)$ index.php?$1 [L]
 * </IfModule>
 *
 * 本扩展要求SpeedPHP框架2.3.8版本(不含2.3.8)以上，以支持对spUrl函数的挂靠程序。
 *
 * 应用程序配置中需要使用到路由挂靠点以及spUrl挂靠点
 * 'launch' => array( 
 *	 	'router_prefilter' => array( 
 *			array('spUrlRewrite', 'setReWrite'), 
 *		),
 *  	'function_url' => array(
 *			array("spUrlRewrite", "getReWrite"),
 * 	    ),
 *),
 *
 * 对spUrlRewrite的配置
 *
 * 'ext' => array(
 * 		'spUrlRewrite' => array(
 *			'hide_default' => true, // 隐藏默认的main/index名称，但这前提是需要隐藏的默认动作是无GET参数的
 * 			'args_path_info' => false, // 地址参数是否使用path_info的方式，默认否
 *			'suffix' => '.html', // 生成地址的结尾符
 *		),
 * ),
 *
 */

class spUrlRewrite
{
	var $params = array(
		'hide_default' => true,
		'args_path_info' => false,
		'suffix' => '.html',
	);
	/**
	 * 构造函数，处理配置
	 */
	public function __construct()
	{
		$params = spExt('spUrlRewrite');
		if(is_array($params))$this->params = array_merge($this->params, $params);
	}	
	/**
	 * 在控制器/动作执行前，对路由进行改装，使其可以解析URL_WRITE的地址
	 */
	public function setReWrite()
	{
		GLOBAL $__controller, $__action;

		$uri = substr($_SERVER["REQUEST_URI"], strlen(dirname($GLOBALS['G_SP']['url']['url_path_base'])));
		
		$lasturi = stristr($uri,$this->params['suffix']);
		$firsturi = explode('/',trim(substr($uri, 0, -strlen($lasturi)),"\/\\"));
		if( true == $this->params['hide_default'] && !isset($firsturi[1]) ){ // 开启隐藏默认名称
			$__controller = $GLOBALS['G_SP']['default_controller'];
			$__action = $firsturi[0];
		}else{
			// 不开启
			$__controller = (empty($firsturi[0])) ? $GLOBALS['G_SP']['default_controller'] : $firsturi[0];
			$__action = (empty($firsturi[1])) ? $GLOBALS['G_SP']['default_action'] : $firsturi[1];
		}
		$lasturi = substr($lasturi, strlen($this->params['suffix']));
		if( "" != $lasturi ){
			if(true == $this->params['args_path_info']){
				$lasturi = explode('/',$lasturi);
				for($u = 1; $u < count($lasturi); $u++){
					spClass("spArgs")->set($lasturi[$u], isset($lasturi[$u+1]) ? $lasturi[$u+1] : false);$u+=1;
				}
			}else{
				$lasturi = explode('&',ltrim($lasturi,'?'));
				foreach( $lasturi as $val ){
					$valarr = explode('=',$val);spClass("spArgs")->set(isset($valarr[0])?$valarr[0]:"",isset($valarr[1])?$valarr[1]:"");
				}
			}
		}
	}


	/**
	 * 在构造spUrl地址时，对地址进行URL_WRITE的改写
	 *
	 * @param urlargs    spUrl的参数
	 */
	public function getReWrite($urlargs = array())
	{
		$url = dirname($GLOBALS['G_SP']['url']["url_path_base"]);
		if( $GLOBALS['G_SP']["default_controller"] == $urlargs['controller'] && $GLOBALS['G_SP']["default_action"] == $urlargs['action'] ){
			// 空操作
		}elseif( true == $this->params['hide_default'] && $GLOBALS['G_SP']["default_controller"] == $urlargs['controller'] ){ // 开启隐藏默认名称
			$url .= '/'.(null != $urlargs['action'] ? $urlargs['action'] : $GLOBALS['G_SP']["default_action"]).$this->params['suffix'];
		}else{
			// 不开启
			$controller = (null != $urlargs['controller']) ? $urlargs['controller'] : $GLOBALS['G_SP']["default_controller"];
			$action = (null != $urlargs['action']) ? $urlargs['action']: $GLOBALS['G_SP']["default_action"];
			$url .= "/{$controller}/{$action}".$this->params['suffix'];
		}
		if(null != $urlargs['args']){
			if(true == $this->params['args_path_info']){
				foreach($urlargs['args'] as $key => $arg)$url .= "/{$key}/{$arg}";
			}else{
				$url .= '?';
				foreach($urlargs['args'] as $key => $arg)$url .= "{$key}={$arg}&";
				$url = rtrim($url,'&');
			}
		}
		return $url .((null != $urlargs['anchor']) ? "#{$anchor}" : '');
	}
}