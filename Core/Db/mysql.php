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
 * mysql 模型应用级扩展
 */
class mysql {
	/**
	 * 链接句柄
	 */
	private $conn;
	/**
	 * 执行的SQL语句记录
	 */
	public $arrSql;



	/**
	 * 按SQL语句获取记录结构，返回数组
	 * 
	 * @param query
	 */
	public function getArray($sql)
	{
		if( ! $result = $this->exec($sql) )return FALSE;
		if( ! mysql_num_rows($result) )return FALSE;
		$rows = array();
		while($rows[] = mysql_fetch_array($result,MYSQL_ASSOC)){}
		mysql_free_result($result);
		$this->arrSql[] = $sql;
		array_pop($rows);
		return $rows;
	}

	/**
	 * 执行一个SQL语句
	 * 
	 * @param query
	 */
	public function exec($sql)
	{
		if( $result = mysql_query($sql, $this->conn) ){
			return $result;
		}else{
			die("Invalid query: " . mysql_error());
		}
	}

	/**
	 * 获取数据表结构
	 */
	public function getTable($tbl_name)
	{
		return $this->getArray("DESCRIBE {$tbl_name}", $this->conn);
	}

	/**
	 * 构造函数
	 */
	public function __construct($dbConfig)
	{
		$this->conn = mysql_connect($dbConfig['host'], $dbConfig['login'], $dbConfig['password'])
		or die("Could not connect : " . mysql_error()); 
		mysql_select_db($dbConfig['database'], $this->conn) or die("Could not select database");
		$this->exec("SET NAMES UTF8");
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		mysql_close($this->conn);
	}
}

