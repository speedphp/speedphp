<?php
/////////////////////////////////////////////////////////////////
// SpeedPHP中文PHP框架, Copyright (C) 2008 - 2011 SpeedPHP.com //
/////////////////////////////////////////////////////////////////

$cfg_dbhost = '服务器';
$cfg_dbname = '表名';
$cfg_dbuser = '用户';
$cfg_dbpwd  = '密码';

class plusdb
{
	private $_db;
	public function __construct(){
		global $cfg_dbhost, $cfg_dbname, $cfg_dbuser, $cfg_dbpwd;
		$this->_db = mysql_connect($cfg_dbhost, $cfg_dbuser, $cfg_dbpwd) or db::error("数据库链接错误 : " . mysql_error()); 
		mysql_select_db($cfg_dbname, $this->_db) or db::error("无法找到数据库，请确认数据库名称正确！");
		$this->runSql("SET NAMES UTF8");
	}
	public function findSql($sql){
		if( ! $result = $this->runSql($sql) )return FALSE;
		if( ! mysql_num_rows($result) )return FALSE;
		$rows = array();
		while($rows[] = mysql_fetch_array($result,MYSQL_ASSOC)){}
		mysql_free_result($result);
		array_pop($rows);
		return $rows;
	}
	public function newid(){return mysql_insert_id($this->_db);}
	public function runSql($sql){
		$this->arrSql[] = $sql;
		if( $result = mysql_query($sql, $this->_db) ){
			return $result;
		}else{
			db::error("{$sql}<br />执行错误: " . mysql_error());
		}
	}
	public function escape($value) {
		if(is_null($value))return 'NULL';
		if(is_bool($value))return $value ? 1 : 0;
		if(is_int($value))return (int)$value;
		if(is_float($value))return (float)$value;
		if(@get_magic_quotes_gpc())$value = stripslashes($value);
		return '\''.mysql_real_escape_string($value, $this->_db).'\'';
	}
	public function error($msg){
		error_log($msg);exit($msg);
	}
}