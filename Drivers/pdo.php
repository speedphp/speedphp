<?php
/////////////////////////////////////////////////////////////////
// SpeedPHP中文PHP框架, Copyright (C) 2008 - 2010 SpeedPHP.com //
/////////////////////////////////////////////////////////////////

/**
 * db_pdo_mysql PDO MySQL数据驱动类
 */
class db_pdo_mysql extends db_pdo {
	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name){
		return $this->getArray("DESCRIBE {$tbl_name}");
	}
}
/**
 * db_pdo_sqlite PDO Sqlite数据驱动类
 */
class db_pdo_sqlite extends db_pdo {
	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name){
		$tmptable = $this->getArray("SELECT * FROM SQLITE_MASTER WHERE name = '{$tbl_name}' AND type='table'");
		$tmp = explode('[',$tmptable[0]['sql']);
		foreach( $tmp as $value ){
			$towarr = explode(']', $value);
			if( isset($towarr[1]) )$columns[]['Field'] = $towarr[0];
		}
		array_shift($columns);
		return $columns;
	}
}

/**
 * db_pdo PDO驱动类 
 */
class db_pdo {
	/**
	 * 数据库链接句柄
	 */
	public $conn;
	/**
	 * 执行的SQL语句记录
	 */
	public $arrSql;
	/**
	 * exec执行影响行数
	 */
	private $num_rows;

	/**
	 * 按SQL语句获取记录结果，返回数组
	 * 
	 * @param sql  执行的SQL语句
	 */
	public function getArray($sql)
	{
		$this->arrSql[] = $sql;
		$rows = $this->conn->prepare($sql);
		$rows->execute();
		return $rows->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * 返回当前插入记录的主键ID
	 */
	public function newinsertid()
	{
		return $this->conn->lastInsertId();
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
		$result = $this->conn->exec($sql);
		if( FALSE !== $result ){
			$this->num_rows = $result;
			return $result;
		}else{
			spError("{$sql}<br />执行错误: " .$this->conn->errorInfo());
		}
	}
	
	/**
	 * 返回影响行数
	 */
	public function affected_rows()
	{
		return $this->num_rows;
	}

	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name){}

	/**
	 * 构造函数
	 *
	 * @param dbConfig  数据库配置
	 */
	public function __construct($dbConfig)
	{
		if(!class_exists("PDO"))spError('PHP环境未安装PDO函数库！');
		try {
		    $this->conn = new PDO($dbConfig['host'], $dbConfig['login'], $dbConfig['password']); 
		} catch (PDOException $e) {
		    echo '数据库链接错误/无法找到数据库 :  ' . $e->getMessage();
		}
	}
	/**
	 * 对特殊字符进行过滤
	 *
	 * @param value  值
	 */
	public function __val_escape($value, $quotes = FALSE) {
		if(is_null($value))return 'NULL';
		if(is_bool($value))return $value ? 1 : 0;
		if(is_int($value))return (int)$value;
		if(is_float($value))return (float)$value;
		if(@get_magic_quotes_gpc())$value = stripslashes($value);
		$value = $this->conn->quote($value);
		//if($quotes)$value = "'{$value}'";
		return $value;
	}

	/**
	 * 析构函数
	 */
	public function __destruct(){
		$this->conn = null;
	}
	
	/**
	 * getConn 取得PDO对象
	 */
	public function getConn()
	{
		return $this->conn;
	}
}

