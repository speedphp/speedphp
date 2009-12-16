<?php
/////////////////////////////////////////////////////////////////////////////
//
// SpeedPHP - 快速的中文PHP框架
//
// Copyright (c) 2008 - 2009 SpeedPHP.com All rights reserved.
//
// 许可协议请查看 http://www.speedphp.com/
//
// 作者：jake（jake@speedphp.com）
//
/////////////////////////////////////////////////////////////////////////////

/**
 * mysql MySQL数据库的驱动支持 
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
	
	public function newinsertid()
	{
		return mysql_insert_id($this->conn);
	}

	/**
	 * 执行一个SQL语句
	 * 
	 * @param query
	 */
	public function exec($sql)
	{
		$this->arrSql[] = $sql;
		if( $result = mysql_query($sql, $this->conn) ){
			return $result;
		}else{
			//dump(debug_backtrace());
			//die("{$sql}<br />Invalid query: " . mysql_error());
			spError("{$sql}<br />Invalid query: " . mysql_error());
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
	
	public function __val_escape($value) {
		if(is_null($value))return 'NULL';
	    if(is_bool($value))return $value ? 1 : 0;
	    if(is_int($value))return (int)$value;
	    if(is_float($value))return (float)$value;
	    if(get_magic_quotes_gpc())$value = stripslashes($value);
	    return mysql_real_escape_string($value, $this->conn);
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		mysql_close($this->conn);
	}
}

