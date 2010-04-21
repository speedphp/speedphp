<?php
/**
 * spDB 函数（全称：SpeedPHP DataBase），处理数据库的函数。
 *
 * 该扩展可以达到简单使用spModel子类的快捷方式，在没有spModel子类定义的情况下，直接对该表(spModel拥有的)操作。
 *
 * 该扩展仅提供spModel子类的简便使用方式，如需要强大或丰富的spModel子类功能，请仍然对子类进行定义并使用该子类。
 *
 * 使用spDB前，需要使用import(SP_PATH."/Extensions/spDB.php");来载入spDB的所在文件
 *
 * 开发者可以方便地：
 * 
 * 1. 初始化一个spModel的子类，即使这个子类的定义不存在
 * 2. 调用该对象的继承spModel而来的全部方法
 */

/**
 * spDB 函数
 *
 * @param table    表名
 * @param pk    主键，在无需使用到主键的情况下，可以忽略。
 */
function spDB($table, $pk = 'id'){
	$class_name = "spdb_tblname_".$table;
	if(isset($GLOBALS['G_SP']["inst_class"][$class_name])){
		return $GLOBALS['G_SP']["inst_class"][$class_name];
	}
	$GLOBALS['G_SP']["inst_class"][$class_name] = spClass($GLOBALS['G_SP']['db']['driver'], $GLOBALS['G_SP']['db'], $GLOBALS['G_SP']['sp_core_path'].$GLOBALS['G_SP']['db_driver_path']);
	return spClass('spDBrecord')->__setdatabase($GLOBALS['G_SP']["inst_class"][$class_name], $table, $pk);
}

class spDBrecord extends spModel
{
	/**
	 * 构造函数，覆盖spModel的构造函数处理
	 */
	public function __construct(){}
	
	/**
	 * 设置当前的_db，表名等，并返回当前子类
	 *
	 * @param db    _db对象
	 * @param table    表名
	 * @param pk    主键
	 */
	public function __setdatabase($db, $table, $pk)
	{
		$this->table = $table;
		$this->pk = $pk;
		$this->tbl_name = $GLOBALS['G_SP']['db']['prefix'] . $table;
		$this->_db = $db;
		return $this;
	}
}