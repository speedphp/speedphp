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
 * mysql MySQL数据库的驱动支持 
 */
class mysql {
	/**
	 * 数据库链接句柄
	 */
	private $conn;
	/**
	 * 执行的SQL语句记录
	 */
	public $arrSql;

	/**
	 * 按SQL语句获取记录结果，返回数组
	 * 
	 * @param sql  执行的SQL语句
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
	 * 返回下一个插入的主键ID
	 */
	public function newinsertid()
	{
		return mysql_insert_id($this->conn);
	}

	/**
	 * 执行一个SQL语句
	 * 
	 * @param sql 需要执行的SQL语句
	 */
	public function exec($sql)
	{
		$this->arrSql[] = $sql;
		if( $result = mysql_query($sql, $this->conn) ){
			return $result;
		}else{
			spError("{$sql}<br />执行错误: " . mysql_error());
		}
	}

	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name)
	{
		return $this->getArray("DESCRIBE {$tbl_name}", $this->conn);
	}

	/**
	 * 构造函数
	 *
	 * @param dbConfig  数据库配置
	 */
	public function __construct($dbConfig)
	{
		$this->conn = mysql_connect($dbConfig['host'].":".$dbConfig['port'], $dbConfig['login'], $dbConfig['password']) or spError("数据库链接错误 : " . mysql_error()); 
		mysql_select_db($dbConfig['database'], $this->conn) or spError("无法找到数据库，请确认数据库名称正确！");
		$this->exec("SET NAMES UTF8");
	}
	/**
	 * 对特殊字符进行过滤
	 *
	 * @param value  值
	 */
	public function __val_escape($value) {
		if(is_null($value))return 'NULL';
		if(is_bool($value))return $value ? 1 : 0;
		if(is_int($value))return (int)$value;
		if(is_float($value))return (float)$value;
		if(@get_magic_quotes_gpc())$value = stripslashes($value);
		return mysql_real_escape_string($value, $this->conn);
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		@mysql_close($this->conn);
	}
}

