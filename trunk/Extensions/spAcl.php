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
 * 基于组的用户权限判断机制
 * 要使用该权限控制程序，需要在应用程序配置中做以下配置：
 * 有限控制的情况，在配置中使用	'launch' => array( 'router_prefilter' => array( array('spAcl','mincheck'), ), )
 * 强制控制的情况，在配置中使用	'launch' => array( 'router_prefilter' => array( array('spAcl','maxcheck'), ), )
 */
class spAcl
{
	/**
	 * 当前权限检查的处理程序设置，可以是函数名或是数组（array(类名,方法)的形式）
	 */
	public $checker = array('spAclPlus','check');
	
	/**
	 * 获取当前会话的用户标识
	 */
	public function get()
	{
		return $_SESSION["SpAclSession"];
	}

	/**
	 * 强制控制的检查程序，适用于后台。无权限控制的页面均不能进入
	 */
	public function maxcheck()
	{
		$acl_handle = $this->__check();
		if( 1 !== $acl_handle ){
			$this->error('Access Fail!');
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 有限的权限控制，适用于前台。仅在权限表声明禁止的页面起作用，其他无声明页面均可进入
	 */
	public function mincheck()
	{
		$acl_handle = $this->__check();
		if( 0 === $acl_handle ){
			$this->error('Access Fail!');
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * 使用程序调度器进行检查等处理
	 */
	private function __check()
	{
		GLOBAL $__controller, $__action;
		$checker = $this->checker; $name = $this->get();

		if( is_array($checker) ){
			return spClass($checker[0])->{$checker[1]}($name, $__controller, $__action);
		}else{
			return call_user_func_array($checker, array($name, $__controller, $__action));
		}
	}
	/**
	 * 默认的错误提示跳转
	 */
	public function error($msg)
	{
		$url = spUrl(); // 跳转到首页，在强制权限的情况下，请将该页面设置成可以进入。
		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>function sptips(){alert(\"{$msg}\");location.href=\"{$url}\";}</script></head><body onload=\"sptips()\"></body></html>";
		exit;
	}

	/**
	 * 设置当前用户，内部使用SESSION记录
	 * 
	 * @param name    用户标识：可以是组名或用户名
	 */
	public function set($name)
	{
		$_SESSION["SpAclSession"] = $name;
	}
}

/**
 * 权限判断的数据接口，开发者可以修改spAcl的接口设置以便使用自定义的接口。
 * 表结构：
 * CREATE TABLE acl
 * (
 * 	aclid int NOT NULL AUTO_INCREMENT,
 * 	name VARCHAR(200),
 * 	controller VARCHAR(50),
 * 	action VARCHAR(50),
 * 	acl_name VARCHAR(50),
 * 	PRIMARY KEY (aclid)
 * ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
 */
class spAclPlus extends spModel
{

	public $pk = 'aclid';
	/**
	 * 表名
	 */
	public $table = 'acl';

	/**
	 * 检查对应的权限
	 *
	 * 返回1是通过检查，0是不能通过检查（控制器及动作存在但用户标识没有记录）
	 * 返回-1是无该权限控制（即该控制器及动作不存在于权限表中）
	 * 
	 * @param name    用户标识：可以是组名或是用户名
	 * @param controller    控制器名称
	 * @param action    动作名称
	 */
	public function check($name = null, $controller, $action)
	{
		$rows = array('controller' => $controller, 'action' => $action );
		if( FALSE == $this->find($rows) )return -1;
		if( null != $name ){
			$rows = array( 'name' => $name, 'controller' => $controller, 'action' => $action );
			if( FALSE != $this->find($rows) )return 1;
		}
		return 0;
	}

}
?>