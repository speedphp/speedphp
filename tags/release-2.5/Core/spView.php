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
	/**
	 * smarty实例
	 */
	private $smarty = null;
	/**
	 * 模板是否已输出
	 */
	private $displayed = FALSE;

	/**
	 * 构造函数，进行Smarty类的实例化操作
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
		spAddViewFunction('T', array( $this, '__smarty_T'));
		spAddViewFunction('spUrl', array( $this, '__smarty_spUrl'));
	}

	/**
	 * 输出页面
	 * @param tplname 模板文件路径
	 */
	public function display($tplname)
	{
		$this->addfuncs();
		$this->displayed = TRUE;
		if($GLOBALS['G_SP']['view']['debugging'] && SP_DEBUG)$this->smarty->debugging = TRUE;
		$this->smarty->display($tplname);
	}

	/**
	 * 获取Smarty的实例
	 */	
	public function getView()
	{
		$this->addfuncs();
		return $this->smarty;
	}
	/**
	 * 自动输出页面
	 * @param tplname 模板文件路径
	 */
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
	/**
	 * 辅助spUrl的函数，让spUrl可在模板中使用。
	 * @param params 传入的参数
	 */
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
	/**
	 * 辅助T的函数，让T可在模板中使用。
	 * @param params 传入的参数
	 */
	public function __smarty_T($params)
	{
		return T($params['w']);
	}
}

/**
 * spHtml
 * 静态HTML生成类
 */
class spHtml
{
	/**
	 * 生成单个静态页面
	 * 
	 * @param spurl spUrl的参数
	 * @param alias_url 生成HTML文件的名称，如果不设置alias_url，将使用年月日生成目录及随机数为文件名的形式生成HTML文件。
	 * @param update_mode    更新模式，默认2为同时更新列表及文件
	 * 0是仅更新列表
	 * 1是仅更新文件
	 */
	public function make($spurl, $alias_url = null, $update_mode = 2)
	{
		$spurl = array_pad($spurl, 4, null);$spurl[] = TRUE;
		if( '*' == $spurl[1] or '*' == $spurl[2] )return FALSE;
		if( $url_item = call_user_func_array($GLOBALS['G_SP']['html']['url_getter'],$spurl) ){
			list($baseuri, $realfile) = $url_item;
		}else{
			if( null == $alias_url ){
				$filedir = $GLOBALS['G_SP']['html']['file_root_name'].'/'.date('Y/n/d').'/';
				$filename = substr(time(),3,10).substr(mt_rand(100000, substr(time(),3,10)),4).".html";
			}else{
				$filedir = $GLOBALS['G_SP']['html']['file_root_name'].'/'.dirname($alias_url) . '/';
				$filename = basename($alias_url);
			}
			$baseuri = rtrim(dirname($GLOBALS['G_SP']['url']["url_path_base"]), '/\\')."/".$filedir.$filename;
			$realfile = dirname($_SERVER['SCRIPT_FILENAME'])."/".$filedir.$filename;
		}
		if( 0 == $update_mode or 2 == $update_mode )
			call_user_func_array($GLOBALS['G_SP']['html']['url_setter'], array($spurl, $baseuri, $realfile));
		if( 1 == $update_mode or 2 == $update_mode ){
			__mkdirs(dirname($realfile));
			$cachedata = @file_get_contents('http://'.$_SERVER["SERVER_NAME"].call_user_func_array("spUrl",$spurl));
			@file_put_contents($realfile, $cachedata);
		}
	}
	
	/**
	 * 批量生成静态页面
	 * @param spurls 数组形式，每项是一个make()的全部参数
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
	 * @param controller    控制器名称，默认为配置'default_controller'
	 * @param action    动作名称，默认为配置'default_action' 
	 * @param args    传递的参数，数组形式
	 * @param anchor    跳转锚点
	 * @param force_no_check    是否检查物理文件是否存在
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
					$url_item = explode("|",substr($url,strlen($url_input)));
					if( TRUE == $GLOBALS['G_SP']['html']['safe_check_file_exists'] && TRUE != $force_no_check ){
						if( !is_readable($url_item[1]) )return FALSE;
					}
					return $url_item;
				}
			}
		}
		return FALSE;
	}
	
	/**
	 * 写入url的列表程序，在make生成页面后，将spUrl参数及页面地址写入列表中
	 *
	 * @param spurl spUrl的参数
	 * @param baseuri URL地址对应的静态HTML文件访问地址
     *
	 */
	public function setUrl($spurl, $baseuri, $realfile)
	{
		@list($controller, $action, $args, $anchor) = $spurl;
		$args = (is_array($args) && !empty($args)) ? serialize($args) : null;
		$url_input = "{$controller}|{$action}|{$args}|$anchor|$baseuri|$realfile";
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
	 * @param controller    需要清除HTML文件的控制器名称
	 * @param action    需要清除HTML文件的动作名称，默认为清除该控制器全部动作产生的HTML文件
	 * 如果设置了action将仅清除该action产生的HTML文件
	 *
	 * @param args    传递的参数，默认为空将清除该动作任何参数产生的HTML文件
	 * 如果设置了args将仅清除该动作执行参数args而产生的HTML文件
	 *
	 * @param anchor    跳转锚点，默认为空将清除该动作任何锚点产生的HTML文件
	 * 如果设置了anchor将仅清除该动作跳转到锚点anchor产生的HTML文件
	 *
	 * @param delete_file    是否删除物理文件，FALSH将只删除列表中该静态文件的地址，而不删除物理文件。
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
	 * @param delete_file    是否删除物理文件，FALSH将只删除列表中该静态文件的地址，而不删除物理文件。
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