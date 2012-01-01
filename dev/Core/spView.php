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
			//$engine_vars = get_class_vars(get_class($this->engine));
			foreach( $GLOBALS['G_SP']['view']['config'] as $key => $value ){
				//if( array_key_exists($key,$engine_vars) )$this->engine->{$key} = $value;
				$this->engine->{$key} = $value;
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
