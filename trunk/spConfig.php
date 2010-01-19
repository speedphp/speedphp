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
 * spConfig
 *
 * SpeedPHP应用框架的系统默认配置
 */

return array(
	'mode' => 'debug', // 应用程序模式，默认为调试模式
	'sp_core_path' => SP_PATH.'/Core', // 框架MVC核心目录
	'sp_include_path' => array( SP_PATH.'/Extensions' ), // 框架扩展功能载入路径
	'mark_launch' => array( // 自动执行点的根节点
		'router_prefilter' => FALSE, // 路由自动执行函数
	),
	'auto_load_controller' => array('spArgs'), // 控制器自动加载的扩展类名
	'auto_load_model' => array('spPager','spVerifier','spCache','spLinker'), // 模型自动加载的扩展类名
	
	'sp_error_show_source' => 5, // spError显示代码的行数
	'allow_trace_onrelease' => FALSE, // 是否允许在部署模式下输出调试信息
		
	'inst_class' => array(), // 已实例化的类名称
	'import_file' => array(), // 已经载入的文件
	'sp_access_store' => array(), // 使用spAccess保存到内存的变量
	'view_registered_functions' => array(), // 视图内挂靠的函数记录

	'default_controller' => 'main', // 默认的控制器名称
	'default_action' => 'index',  // 默认的动作名称
	'url_controller' => 'c',  // 请求时使用的控制器变量标识
	'url_action' => 'a',  // 请求时使用的动作变量标识

	'auto_session' => TRUE, // 是否自动开启SESSION支持
	'dispatcher_error' => "spError('路由错误，请检查是否存在该函数。');", // 定义处理路由错误的函数
	
	'sp_cache' => APP_PATH.'/tmp', // 框架临时文件夹目录
	'controller_path' => APP_PATH.'/controller', // 用户控制器程序的路径定义
	'model_path' => APP_PATH.'/model', // 用户模型程序的路径定义

	'url' => array( // URL设置
		'url_path_info' => FALSE, // 是否使用path_info方式的URL
		'url_path_base' => '/index.php', // URL的根目录访问地址
	),
	
	'db' => array(  // 数据库连接配置
		'driver' => 'mysql',   // 驱动类型
		'host' => 'localhost', // 数据库地址
		'port' => 3306,        // 端口
		'login' => 'root',       // 用户名
		'password' => '',      // 密码
		'database' => '',      // 库名称
		'prefix' => '',           // 表前缀
	),
	'db_driver_path' => '/mysql.php', // 数据库驱动文件
	
	'view' => array( // 视图配置
		'enabled' => TRUE, // 开启视图
		'config' =>array(
			'template_dir' => APP_PATH.'/tpl', // 模板目录
			'compile_dir' => APP_PATH.'/tmp', // 编译目录
			'cache_dir' => APP_PATH.'/tmp', // 缓存目录
			'left_delimiter' => '{',  // smarty左限定符
			'right_delimiter' => '}', // smarty右限定符
		),
		'debugging' => FALSE, // 是否开启视图调试功能，在部署模式下无法开启视图调试功能
		'engine_name' => 'smarty', // 视图驱动名称
		'engine_path' => SP_PATH.'/Core/Smarty/Smarty.class.php', // smarty类库路径
		'auto_ob_start' => TRUE, // 是否自动开启缓存输出控制
		'auto_display' => FALSE, // 是否使用自动输出模板功能
		'auto_display_sep' => '/', // 自动输出模板的拼装模式，/为按目录方式拼装，_为按下划线方式，以此类推
		'auto_display_suffix' => '.html', // 自动输出模板的后缀名
	),
		
	'html' => array( 
		'enabled' => FALSE, // 是否开启真实静态HTML文件生成器
		'file_root_name' => 'topic', // 静态文件生成的根目录名称，设置为空则是直接在入口文件的同级目录生成
		'url_setter' => array("spHtml","setUrl"), // 写入URL的列表接口设置，这里同时还可以设置成单独的函数名称
		'url_getter' => array('spHtml','getUrl'), // 获取URL的列表接口设置
		'safe_check_file_exists' => FALSE, // 获取URL时，检查物理HTML文件是否存在，如文件不存在，则返回安全的动态地址
	),
	
	'lang' => array(), // 多语言设置，键是每种语言的名称，而值可以是default（默认语言），语言文件地址或者是翻译函数
					// 同时请注意，在使用语言文件并且文件中存在中文等时，请将文件设置成UTF8编码
	'ext' => array(), // 扩展使用的配置根目录
		
	'include_path' => array(
		APP_PATH.'/include',
	), // 用户程序扩展类载入路径
);
