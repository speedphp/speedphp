<?php
/////////////////////////////////////////////////////////////////
// SpeedPHP中文PHP框架, Copyright (C) 2008 - 2010 SpeedPHP.com //
/////////////////////////////////////////////////////////////////

/**
 * spView 基础视图类
 */
class spView {
	/**
	 * 模板引擎实例
	 */
	public $engine = null;
	/**
	 * 模板是否已输出
	 */
	public $displayed = FALSE;

	/**
	 * 构造函数，进行模板引擎的实例化操作
	 */
	public function __construct()
	{
		if(FALSE == $GLOBALS['G_SP']['view']['enabled'])return FALSE;
		if(FALSE != $GLOBALS['G_SP']['view']['auto_ob_start'])ob_start();
		$this->engine = spClass($GLOBALS['G_SP']['view']['engine_name'],null,$GLOBALS['G_SP']['view']['engine_path']);
		if( $GLOBALS['G_SP']['view']['config'] && is_array($GLOBALS['G_SP']['view']['config']) ){
			$engine_vars = get_class_vars(get_class($this->engine));
			foreach( $GLOBALS['G_SP']['view']['config'] as $key => $value ){
				if( array_key_exists($key,$engine_vars) )$this->engine->{$key} = $value;
			}
		}
		if( !empty($GLOBALS['G_SP']['sp_app_id']) && isset($this->engine->compile_id) )$this->engine->compile_id = $GLOBALS['G_SP']['sp_app_id'];
		// 检查编译目录是否可写
		if( empty($this->engine->no_compile_dir) && (!is_dir($this->engine->compile_dir) || !is_writable($this->engine->compile_dir)))__mkdirs($this->engine->compile_dir);
		spAddViewFunction('T', array( 'spView', '__template_T'));
		spAddViewFunction('spUrl', array( 'spView', '__template_spUrl'));
	}

	/**
	 * 输出页面
	 * @param tplname 模板文件路径
	 */
	public function display($tplname)
	{
		try {
				$this->addfuncs();
				$this->displayed = TRUE;
				if($GLOBALS['G_SP']['view']['debugging'] && SP_DEBUG)$this->engine->debugging = TRUE;
				$this->engine->display($tplname);
		} catch (Exception $e) {
			spError( $GLOBALS['G_SP']['view']['engine_name']. ' Error: '.$e->getMessage() );
		}
	}
	
	/**
	 * 注册视图函数
	 */
	public function addfuncs()
	{
		if( is_array($GLOBALS['G_SP']["view_registered_functions"]) ){
			foreach( $GLOBALS['G_SP']["view_registered_functions"] as $alias => $func ){
				if( is_array($func) && !is_object($func[0]) )$func = array(spClass($func[0]),$func[1]);
				$this->engine->registerPlugin("function", $alias, $func);
				unset($GLOBALS['G_SP']["view_registered_functions"][$alias]);
			}
		}
	}
	/**
	 * 辅助spUrl的函数，让spUrl可在模板中使用。
	 * @param params 传入的参数
	 */
	public function __template_spUrl($params)
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
	public function __template_T($params)
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
	private $spurls = null;
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
		if(1 == spAccess('r','sp_html_making')){$this->spurls[] = array($spurl, $alias_url); return;}
		@list($controller, $action, $args, $anchor) = $spurl;
		if( $url_item = spHtml::getUrl($controller, $action, $args, $anchor, TRUE) ){
			@list($baseuri, $realfile) = $url_item;$update_mode = 1;
		}else{
			$file_root_name = ( '' == $GLOBALS['G_SP']['html']['file_root_name'] ) ? 
									'' : $GLOBALS['G_SP']['html']['file_root_name'].'/';
			if( null == $alias_url ){
				$filedir = $file_root_name .date('Y/n/d').'/';
				$filename = substr(time(),3,10).substr(mt_rand(100000, substr(time(),3,10)),4).".html";
			}else{
				$filedir = $file_root_name.dirname($alias_url) . '/';
				$filename = basename($alias_url);
			}
			$baseuri = rtrim(dirname($GLOBALS['G_SP']['url']["url_path_base"]), '/\\')."/".$filedir.$filename;
			$realfile = APP_PATH."/".$filedir.$filename;
		}
		if( 0 == $update_mode or 2 == $update_mode )spHtml::setUrl($spurl, $baseuri, $realfile);
		if( 1 == $update_mode or 2 == $update_mode ){
			$remoteurl = 'http://'.$_SERVER["SERVER_NAME"].':'.$_SERVER['SERVER_PORT'].
										'/'.ltrim(spUrl($controller, $action, $args, $anchor, TRUE), '/\\');
			$cachedata = file_get_contents($remoteurl);
			if( FALSE === $cachedata ){
				$cachedata = $this->curl_get_file_contents($remoteurl);
				if( FALSE === $cachedata )spError("无法从网络获取页面数据，请检查：<br />1. spUrl生成地址是否正确！<a href='{$remoteurl}' target='_blank'>点击这里测试</a>。<br />2. 设置php.ini的allow_url_fopen为On。<br />3. 检查是否防火墙阻止了APACHE/PHP访问网络。<br />4. 建议安装CURL函数库。");
			}
			__mkdirs(dirname($realfile));
			file_put_contents($realfile, $cachedata);
		}
	}
	
	/**
	 * 当file_get_contents失效时，程序将调用CURL函数来进行网络数据获取
	 * @param url 访问地址
	 */
	function curl_get_file_contents($url)
    {
    	if(!function_exists('curl_init'))return FALSE;
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);
        curl_close($c);
        if (FALSE === $contents)return FALSE;
        return $contents;
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
	
	public function start(){spAccess('w','sp_html_making',1);$this->spurls = null;}
	public function commit(){spAccess('c','sp_html_making');$this->makeAll($this->spurls);}

	/**
	 * 获取url的列表程序，可以按配置开启是否检查文件存在
	 * @param controller    控制器名称，默认为配置'default_controller'
	 * @param action    动作名称，默认为配置'default_action' 
	 * @param args    传递的参数，数组形式
	 * @param anchor    跳转锚点
	 * @param force_no_check    是否检查物理文件是否存在
	 */
	public function getUrl($controller = null, $action = null, $args = null, $anchor = null, $force_no_check = FALSE)
	{
		if( $url_list = spAccess('r', 'sp_url_list') ){
			$url_list = explode("\n",$url_list);
			$args_en = !empty($args) ? json_encode($args) : "";
			$url_input = "{$controller}|{$action}|{$args_en}|$anchor|";
			foreach( $url_list as $url ){
				if( substr($url,0,strlen($url_input)) == $url_input ){
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
		$this->clear($controller, $action, $args, $anchor, FALSE);
		$args = !empty($args) ? json_encode($args) : '';
		$url_input = "{$controller}|{$action}|{$args}|{$anchor}|{$baseuri}|{$realfile}";
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
	 * @param delete_file    是否删除物理文件，FALSE将只删除列表中该静态文件的地址，而不删除物理文件。
	 */
	public function clear($controller, $action = null, $args = FALSE, $anchor = '', $delete_file = TRUE)
	{
		if( $url_list = spAccess('r', 'sp_url_list') ){
			$url_list = explode("\n",$url_list);$re_url_list = array();
			if( null == $action ){
				$prep = "{$controller}|";
			}elseif( FALSE === $args ){
				$prep = "{$controller}|{$action}|";
			}else{
				$args = !empty($args) ? json_encode($args) : '';
				$prep = "{$controller}|{$action}|{$args}|{$anchor}|";
			}
			foreach( $url_list as $url ){
				if( substr($url,0,strlen($prep)) == $prep ){
					$url_tmp = explode("|",$url);$realfile = $url_tmp[5];
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
					$url_tmp = explode("|",$url);$realfile = $url_tmp[5];
					@unlink($realfile);
				}
			}
		}
		spAccess('c', 'sp_url_list');
	}
}