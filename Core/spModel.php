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
 * spModel 系统模型类，所有模型类的父类 应用程序中的每个模型类都应继承于spModel。
 */
class spModel {
	
	/**
	 * 表主键
	 */
	public $pk;
	/**
	 * 表名称
	 */
	public $table;
	
	/**
	 * 表全名
	 */
	protected $tbl_name;
	
	/**
	 * 数据驱动程序
	 */
	private $_db;

	/**
	 * 构造函数
	 */
	public function __construct($params = null)
	{
		$this->tbl_name = $GLOBALS['G_SP']['db']['prefix'] . $this->table;
		$this->_db = spClass($GLOBALS['G_SP']['db']['driver'], $GLOBALS['G_SP']['db'], $GLOBALS['G_SP']['sp_core_path'].$GLOBALS['G_SP']['db_driver_path']);
	}

	/**
	 * 查找一条记录
	 */
	public function find($conditions = null, $sort = null, $fields = '*')
	{
		$where = "";
		if(is_array($conditions)){
			$join = array();
			foreach( $conditions as $key => $condition )$join[] = "{$key} = '{$condition}'";
			$where = "WHERE ".join(" AND ",$join);
		}else{
			if(null != $conditions)$where = "WHERE ".$conditions;
		}
		if(null != $sort)$sort = "ORDER BY {$sort}";
		$sql = "SELECT {$this->tbl_name}.{$fields} FROM {$this->tbl_name} {$where} {$sort} limit 1";
		if( $record = $this->_db->getArray($sql) ){
			return array_pop($record);
		}else{
			return FALSE;
		}
	}
	
	/**
	 * 查找全部记录
	 */
	public function findAll($conditions = null, $sort = null, $fields = '*', $limit = null)
	{
		$where = "";
		if(is_array($conditions)){
			$join = array();
			foreach( $conditions as $key => $condition )$join[] = "{$key} = '{$condition}'";
			$where = "WHERE ".join(" AND ",$join);
		}else{
			if(null != $conditions)$where = "WHERE ".$conditions;
		}
		if(null != $sort)$sort = "ORDER BY {$sort}";
		if(null != $limit)$limit = "LIMIT {$limit}";
		$sql = "SELECT {$this->tbl_name}.{$fields} FROM {$this->tbl_name} {$where} {$sort} {$limit}";
		return $this->_db->getArray($sql);
	}

	/**
	 * 根据SQL查询记录
	 */
	public function findSql($sql)
	{
		return $this->_db->getArray($sql);
	}

	/**
	 * 创建记录
	 */
	public function create($row)
	{
		if(!is_array($row))return FALSE;
		$row = $this->format($row);
		foreach($row as $key => $value){
			$cols[] = $key;
			$vals[] = "'".$value."'";
		}
		$col = join(',', $cols);
		$val = join(',', $vals);
		
		$sql = "INSERT INTO {$this->tbl_name} ({$col}) VALUES ({$val})";
		if( FALSE != $this->_db->exec($sql) ){
			return array_pop( $this->find($row, "{$this->pk} DESC",$this->pk) );
		}
		return FALSE;
	}

	/**
	 * 创建多条记录
	 */
	public function createAll($rows)
	{
		foreach($rows as $row)$this->create($row);
	}

	/**
	 * 按表字段调整适合的字段
	 */
	public function format($rows)
	{
		$columns = $this->_db->getTable($this->tbl_name);
		$newcol = array();
		foreach( $columns as $col ){
			$newcol[$col['Field']] = $col['Field'];
		}
		return array_intersect_key($rows,$newcol);
	}
	
	/**
	 * 删除记录
	 */
	public function delete($conditions)
	{
		$where = "";
		if(is_array($conditions)){
			$join = array();
			foreach( $conditions as $key => $condition )$join[] = "{$key} = '{$condition}'";
			$where = "WHERE ( ".join(" AND ",$join). ")";
		}else{
			if(null != $conditions)$where = "WHERE ( ".$conditions. ")";
		}
		$sql = "DELETE FROM {$this->tbl_name} {$where}";
		return $this->_db->exec($sql);
	}

	/**
	 * 按字段值查找一条记录
	 */
	public function findBy($field, $value)
	{
		return $this->find(array($field=>$value));
	}

	/**
	 * 更新符合条件的记录中的某字段值
	 */
	public function updateField($conditions, $field, $value)
	{
		return $this->update($conditions, array($field=>$value));
	}

	/**
	 * 直接执行SQL语句
	 */
	public function query($sql)
	{
		return $this->_db->exec($sql);
	}

	/**
	 * 返回当前执行的SQL语句
	 */
	public function dumpSql()
	{
		return end( $this->_db->arrSql );
	}

	/**
	 * 按条件查找记录总数
	 */
	public function findCount($conditions = null)
	{
		$where = "";
		if(is_array($conditions)){
			$join = array();
			foreach( $conditions as $key => $condition )$join[] = "{$key} = '{$condition}'";
			$where = "WHERE ".join(" AND ",$join);
		}else{
			if(null != $conditions)$where = "WHERE ".$conditions;
		}
		$sql = "SELECT COUNT({$this->pk}) as sp_counter FROM {$this->tbl_name} {$where}";
		return array_pop( array_pop( $this->_db->getArray($sql) ) );
	}

	/**
	 * 更新记录
	 * 
	 * @param conditions    更新的条件
	 * @param row    更新的数据，数组形式
	 */
	public function update($conditions, $row)
	{
		$where = "";
		$row = $this->format($row);
		if(is_array($conditions)){
			$join = array();
			foreach( $conditions as $key => $condition )$join[] = "{$key} = '{$condition}'";
			$where = "WHERE ".join(" AND ",$join);
		}else{
			if(null != $conditions)$where = "WHERE ".$conditions;
		}
		foreach($row as $key => $value)$vals[] = "{$key} = '{$value}'";
		$values = join(", ",$vals);
		$sql = "UPDATE {$this->tbl_name} SET {$values} {$where}";
		return $this->_db->exec($sql);
	}

	/**
	 * 按主键删除记录
	 */
	public function deleteByPk($pk)
	{
		return $this->delete(array($this->pk=>$pk));
	}

}
