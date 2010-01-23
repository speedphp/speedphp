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
 * spView 基础视图类
 */
class spView {

	private $smarty = null;
	
	private $displayed = FALSE;

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		if(FALSE == $GLOBALS['G_SP']['view']['enabled'])return FALSE;
		if(FALSE != $GLOBALS['G_SP']['view']['auto_ob_start'])ob_start();
		$this->smarty = spClass($GLOBALS['G_SP']['view']['engine_name'],null,$GLOBALS['G_SP']['view']['engine_path']);
		$configs = $GLOBALS['G_SP']['view']['config'];
		if( is_array($configs) ){
			foreach( $configs as $key => $value ){
				if( isset($this->smarty->{$key}) )$this->smarty->{$key} = $value;
			}
		}
		spAddViewFunction('T', array(& $this, '__smarty_T'));
		spAddViewFunction('spUrl', array(& $this, '__smarty_spUrl'));
	}

	/**
	 * 输出页面
	 */
	public function display($tplname)
	{
		$this->addfuncs();
		$this->displayed = TRUE;
		if($GLOBALS['G_SP']['view']['debugging'] && SP_DEBUG)$this->smarty->debugging = TRUE;
		$this->smarty->display($tplname);
	}

		
	public function getView()
	{
		$this->addfuncs();
		return $this->smarty;
	}

	public function auto_display($tplname)
	{
		if( TRUE != $this->displayed && 
			FALSE != $GLOBALS['G_SP']['view']['auto_display'] &&
			TRUE == $this->smarty->template_exists($tplname)){
			$this->display($tplname);
		}
	}
	
	/**
	 * 注册已挂靠的视图函数
	 */
	public function addfuncs()
	{
		if( is_array($GLOBALS['G_SP']["view_registered_functions"]) ){
			foreach( $GLOBALS['G_SP']["view_registered_functions"] as $alias => $func )
			{
				$this->smarty->register_function($alias, $func);
			}
		}
	}

	public function __smarty_spUrl($params)
	{
		$controller = $GLOBALS['G_SP']["default_controller"];
		$action = $GLOBALS['G_SP']["default_action"];
		$args = array();
		$anchor = null;
		foreach($params as $key => $param){
			if( $key == $GLOBALS['G_SP']["url_controller"] ){
				$controller = $param;
			}elseif( $key == $GLOBALS['G_SP']["url_action"] ){
				$action = $param;
			}elseif( $key == 'anchor' ){
				$anchor = $param;
			}else{
				$args[$key] = $param;
			}
		}
		return spUrl($controller, $action, $args, $anchor);
	}

	public function __smarty_T($params)
	{
		return T($params['w']);
	}
}

/**
 * spHtml静态工具
 */
class spHtml
{
	/**
	 * 制造单个静态页面
	 * 
	 * @param spurl
	 * @param alias_url
	 * @param update_mode    更新模式，默认2为同时更新列表及文件
	 * 0是仅更新列表
	 * 1是仅更新文件
	 */
	public function make($spurl, $alias_url = null, $update_mode = 2)
	{
		$spurl = array_pad($spurl, 4, null);$spurl[] = TRUE;
		if( '*' == $spurl[1] or '*' == $spurl[2] )return FALSE;
		$cachedata = file_get_contents('http://'.$_SERVER["SERVER_NAME"].call_user_func_array('spUrl',$spurl));
		if( $url_item = call_user_func_array(array(& $this, 'getUrl'),$spurl) ){
			if( '/' == substr($url_item, 0, 1) ){$url_item = substr($url_item,1);}
			$filedir = dirname($url_item).'/';
			$filename = basename($url_item);
		}else{
			if( null == $alias_url ){
				$filedir = $GLOBALS['G_SP']['html']['file_root_name'].'/'.date('Y/n/d').'/';
				$filename = substr(time(),3,10).substr(mt_rand(100000, substr(time(),3,10)),4).".html";
			}else{
				if( '/' == substr($alias_url, 0, 1) ){$alias_url = substr($alias_url,1);}
				$filedir = $GLOBALS['G_SP']['html']['file_root_name'].'/'.dirname($alias_url) . '/';
				$filename = basename($alias_url);
			}
		}
		$baseuri = str_replace("\\","/",dirname($GLOBALS['G_SP']['url']["url_path_base"])).$filedir.$filename;
		$realfile = dirname($_SERVER["SCRIPT_FILENAME"]).'/'.$filedir.$filename;
		if( 0 == $update_mode or 2 == $update_mode )call_user_func_array($GLOBALS['G_SP']['html']['url_setter'], array($spurl, $baseuri));
		if( 1 == $update_mode or 2 == $update_mode ){
			__mkdirs($filedir);
			@file_put_contents($realfile, $cachedata);
		}
	}
	
	/**
	 * 制造多个静态页面
	 */
	public function makeAll($spurls)
	{
		foreach( $spurls as $single ){
			list($spurl, $alias_url) = $single;
			$this->make($spurl, $alias_url, 0);
		}
		foreach( $spurls as $single ){
			list($spurl, $alias_url) = $single;
			$this->make($spurl, $alias_url, 1);
		}
	}	

	/**
	 * 获取url的列表程序，可以按配置开启是否检查文件存在
     *
	 */
	public function getUrl($controller = null, $action = null, $args = null, $anchor = null, $force_no_check = FALSE)
	{
		if( $url_list = spAccess('r', 'sp_url_list') ){
			$url_list = explode("\n",$url_list);
			$args = (is_array($args) && !empty($args) ) ? serialize($args) : null;
			$url_input = "{$controller}|{$action}|{$args}|$anchor|";
			foreach( $url_list as $url ){
				if( substr($url,0,strlen($url_input)) == $url_input )
				{
					if( TRUE == $GLOBALS['G_SP']['html']['safe_check_file_exists'] && TRUE != $force_no_check ){
						$realfile = dirname($_SERVER["SCRIPT_FILENAME"]).'/'.substr($url,strlen($url_input));
						if( is_readable($realfile) )return substr($url,strlen($url_input));
					}else{
						return substr($url,strlen($url_input));
					}
				}
			}
		}
		return FALSE;
	}
	
	/**
	 * 写入url的列表程序，在make生成页面后，将spUrl参数及页面地址写入列表中
     *
	 */
	public function setUrl($spurl, $baseuri)
	{
		@list($controller, $action, $args, $anchor) = $spurl;
		$args = (is_array($args) && !empty($args)) ? serialize($args) : null;
		$url_input = "{$controller}|{$action}|{$args}|$anchor|$baseuri";
		$this->clear($controller, $action, $args, $anchor, FALSE);
		if( $url_list = spAccess('r', 'sp_url_list') ){
			spAccess('w', 'sp_url_list', $url_list."\n".$url_input);
		}else{
			spAccess('w', 'sp_url_list', $url_input);
		}
	}

	/**
	 * 清除静态文件
	 * 
	 * @param controller    控制器名称
	 * @param action    动作名称
	 */
	public function clear($controller, $action = null, $args = null, $anchor = null, $delete_file = TRUE)
	{
		if( $url_list = spAccess('r', 'sp_url_list') ){
			$url_list = explode("\n",$url_list);$re_url_list = array();
			if( '*' == $action ){
				$url_input = "{$controller}|";
			}elseif( '*' == $args ){
				$url_input = "{$controller}|{$action}|";
			}else{
				$url_input = "{$controller}|{$action}|{$args}|$anchor|";
			}
			foreach( $url_list as $url ){
				if( substr($url,0,strlen($url_input)) == $url_input )
				{
					$url_tmp = explode("|",$url);$baseuri = $url_tmp[4];
					$realfile = dirname($_SERVER["SCRIPT_FILENAME"]).'/'.$baseuri;
					if( TRUE == $delete_file )@unlink($realfile);
				}else{
					$re_url_list[] = $url;
				}
			}
			spAccess('w', 'sp_url_list', join("\n", $re_url_list));
		}
	}
	

	/**
	 * 清除全部静态文件
	 * 
	 */
	public function clearAll($delete_file = FALSE)
	{
		if( TRUE == $delete_file ){
			if( $url_list = spAccess('r', 'sp_url_list') ){
				$url_list = explode("\n",$url_list);
				foreach( $url_list as $url ){
					$url_tmp = explode("|",$url);$baseuri = $url_tmp[4];
					$realfile = dirname($_SERVER["SCRIPT_FILENAME"]).'/'.$baseuri;
					@unlink($realfile);
				}
			}
		}
		spAccess('c', 'sp_url_list');
	}
}

/**
 * __mkdirs
 *
 * 循环建立目录的辅助函数
 *
 */
function __mkdirs($dir, $mode = 0777)
{
	if (!is_dir($dir)) {
		__mkdirs(dirname($dir), $mode);
		return mkdir($dir, $mode);
	}
	return true;
}

/**
 * spAddViewFunction
 *
 * 将函数注册到模板内使用，该函数可以是对象的方法，类的方法或是函数。
 *
 */
function spAddViewFunction($alias, $callback_function)
{
	return $GLOBALS['G_SP']["view_registered_functions"][$alias] = $callback_function;
}


