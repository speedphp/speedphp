<?php
/////////////////////////////////////////////////////////////////
// SpeedPHP中文PHP框架, Copyright (C) 2008 - 2010 SpeedPHP.com //
/////////////////////////////////////////////////////////////////

/**
 * db_bae 封装了百度应用开发平台（BAE）的数据库操作驱动
 */
class db_bae {
	/**
	 * 数据库链接句柄
	 */
	public $conn;
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
		if( ! $result = $this->exec($sql) )return array();
		if( ! $result->num_rows )return array();
		$rows = array();
		while($rows[] = $result->fetch_array(MYSQLI_ASSOC)){}
		array_pop($rows);
		return $rows;
	}
	
	/**
	 * 返回当前插入记录的主键ID
	 */
	public function newinsertid()
	{
		return $this->conn->insert_id;
	}
	
	/**
	 * 格式化带limit的SQL语句
	 */
	public function setlimit($sql, $limit)
	{
		return $sql. " LIMIT {$limit}";
	}

	/**
	 * 执行一个SQL语句
	 * 
	 * @param sql 需要执行的SQL语句
	 */
	public function exec($sql)
	{
		$this->arrSql[] = $sql;
		if( $result = $this->conn->query($sql) ){
			return $result;
		}else{
			spError("{$sql}<br />执行错误: " . $this->conn->error);
		}
	}
	
	/**
	 * 返回影响行数
	 */
	public function affected_rows()
	{
		return $this->conn->affected_rows;
	}

	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name)
	{
		return $this->getArray("DESCRIBE {$tbl_name}");
	}

	/**
	 * 构造函数
	 *
	 * @param dbConfig  数据库配置
	 */
	public function __construct($dbConfig = null)
	{
		@require_once ('app_config.php');
		$this->conn = APP_INIT_MYSQL();
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
		return '\''.$this->conn->real_escape_string($value).'\'';
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		if( TRUE != $GLOBALS['G_SP']['db']['persistent'] )@$this->conn->close();
	}
}

