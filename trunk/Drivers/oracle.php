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
 * db_oracle Oracle数据库的驱动支持
 */
class db_oracle {
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
		$result = $this->exec($sql);
		oci_fetch_all($result, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		oci_free_statement($result);
		return $res;
	}
	
	/**
	 * 返回当前插入记录的主键ID
	 */
	public function newinsertid()
	{
		return FALSE; // 使用spModel的create来进行查找最后插入ID
	}
	
	/**
	 * 格式化带limit的SQL语句
	 */
	public function setlimit($sql, $limit)
	{
		$limitarr = explode(',',str_replace(' ','',$limit));
		$total = (isset($limitarr[1])) ? ($limitarr[1] + $limitarr[0]) : $limitarr[0];
		$start = (isset($limitarr[1])) ? $limitarr[1] : 0;
		return "SELECT * FROM ( SELECT *, ROWNUM sptmp_limit_rownum FROM ({$sql}) sptmp_limit_tblname WHERE ROWNUM <= {$total} )WHERE sptmp_limit_rownum >= {$start}";
	}

	/**
	 * 执行一个SQL语句
	 * 
	 * @param sql 需要执行的SQL语句
	 */
	public function exec($sql)
	{
		$this->arrSql[] = $sql;
		$result = oci_parse($this->conn, $sql);
		if( !$result or !oci_execute($result) ){
			$e = oci_error();spError('{$sql}<br />执行错误: ' . strip_tags($e['message']));
		}
		return $result;
	}

	/**
	 * 获取数据表结构
	 *
	 * @param tbl_name  表名称
	 */
	public function getTable($tbl_name)
	{
		return $this->getArray("SELECT column_name AS Field FROM USER_TAB_COLUMNS WHERE table_name = '{$tbl_name}'");
	}

	/**
	 * 构造函数
	 *
	 * @param dbConfig  数据库配置
	 */
	public function __construct($dbConfig)
	{
		if(!function_exists('oci_connect'))spError('PHP环境未安装ORACLE函数库！');
		$linkfunction = ( TRUE == $dbConfig['persistent'] ) ? 'oci_pconnect' : 'oci_connect';
		if( ! $this->conn = $linkfunction($dbConfig['login'], $dbConfig['password'], $dbConfig['host'], 'AL32UTF8') ){
			$e = oci_error();spError('数据库链接错误 : ' . strip_tags($e['message']));
		}
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
		$value = addslashes($value); // ?
		$value = str_replace("_","\_",$value);
		$value = str_replace("%","\%",$value);
		return $value;
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		if( TRUE != $dbConfig['persistent'] )@oci_close($this->conn);
	}
}
