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
 * spView 基础视图类
 */
class spView {
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		if(FALSE != $GLOBALS['G_SP']['view']['auto_ob_start'])ob_start();
	}
}

