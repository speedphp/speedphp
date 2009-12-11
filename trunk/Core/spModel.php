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
 * spModel 系统模型类，所有模型类的父类 应用程序中的每个模型类都应继承于spModel。
 */
class spModel {
	/**
	 * 供检验值的规则与返回信息
	 */
	public $verifier = null;
	
	/**
	 * 增加的自定义验证函数
	 */
	public $addrules = array();
	/**
	 * 表主键
	 */
	public $pk;
	/**
	 * 表名称
	 */
	public $table;

	/**
	 * 关联描述
	 */
	public $linker = null;
	
	/**
	 * 表全名
	 */
	protected $tbl_name = null;
	
	/**
	 * 数据驱动程序
	 */
	private $_db;


	/**
	 * 构造函数
	 */
	public function __construct($params = null)
	{
		if( null == $this->tbl_name )$this->tbl_name = $GLOBALS['G_SP']['db']['prefix'] . $this->table;
		$this->_db = spClass($GLOBALS['G_SP']['db']['driver'], $GLOBALS['G_SP']['db'], $GLOBALS['G_SP']['sp_core_path'].$GLOBALS['G_SP']['db_driver_path']);
	}

	/**
	 * 查找一条记录
	 */
	public function find($conditions = null, $sort = null, $fields = null)
	{
		$where = "";
		$fields = empty($fields) ? "*" : $fields;
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
	public function findAll($conditions = null, $sort = null, $fields = null, $limit = null)
	{
		$where = "";
		$fields = empty($fields) ? "*" : $fields;
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
		$row = $this->__prepera_format($row);
		if(empty($row))return FALSE;
		foreach($row as $key => $value){
			$cols[] = $key;
			$vals[] = "'".$value."'";
		}
		$col = join(',', $cols);
		$val = join(',', $vals);
		
		$sql = "INSERT INTO {$this->tbl_name} ({$col}) VALUES ({$val})";
		if( FALSE != $this->_db->exec($sql) ){ // 获取当前新增的ID
			if( $newinserid = $this->_db->newinsertid() ){
				return $newinserid;
			}else{
				return array_pop( $this->find($row, "{$this->pk} DESC",$this->pk) );
			}
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
	public function __prepera_format($rows)
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
	 * 魔术函数，执行模型扩展类的自动加载及使用
	 */
	public function __call($name, $args)
	{
		if(in_array($name, $GLOBALS['G_SP']["auto_load_model"])){
			return spClass($name)->__input( $this, $args);
		}elseif(!method_exists( $this, $name )){
			spError("method {$name} not defined");
		}
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
		$row = $this->__prepera_format($row);
		if(empty($row))return FALSE;
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


/**
 * spPager
 * 数据分页程序
 */
class spPager {
	/**
	 * 模型对象
	 */
	private $model_obj = null;
	/**
	 * 页码数据
	 */
	private $pageData = null;
	/** 
	 * 调用时输入的参数
	 */
	private $input_args = null;
	
    public function __input(& $obj, $args){
		$this->model_obj = $obj;
		$this->input_args = $args;
		return $this;
	}
	
	public function __call($func_name, $func_args){
		if( ('spLinker' == $func_name || 'findAll' == $func_name || 'findSql' == $func_name ) && 0 != $this->input_args[0]){
			return $this->runpager($func_name, $func_args);
		}elseif(method_exists($this,$func_name)){
			return call_user_func_array(array($this, $func_name), $func_args);
		}else{
			return call_user_func_array(array($this->model_obj, $func_name), $func_args);
		}
	}
	
	public function getPager(){
		return $this->pageData;
	}
	
	private function runpager($func_name, $func_args){
		$page = $this->input_args[0];
		$pageSize = $this->input_args[1];
		list($conditions, $sort, $fields ) = $func_args;
		if('findSql'==$func_name){
			$total_count = array_pop( array_pop( $this->model_obj->findSql("SELECT COUNT({$this->model_obj->pk}) as sp_counter FROM ($conditions) sp_tmp_table_pager1") ) );
		}else{
			$total_count = $this->model_obj->findCount($conditions);
		}
		if($total_count > $pageSize){
			$total_page = ceil( $total_count / $pageSize );
			$this->pageData = array(
				"total_count" => $total_count,                                 // 总记录数
				"page_size"   => $pageSize,                                    // 分页大小
				"total_page"  => $total_page,                                  // 总页数
				"first_page"  => 1,                                            // 第一页
				"prev_page"   => ( ( 1 == $page ) ? 1 : ($page - 1) ),         // 上一页
				"next_page"   => ( ( $page == $total_page ) ? $total_page : ($page + 1)),     // 下一页
				"last_page"   => $total_page,                                  // 最后一页
				"current_page"=> $page,                                        // 当前页
				"all_pages"   => array()	                                   // 全部页码
			);
			for($i=1; $i <= $total_page; $i++)$this->pageData['all_pages'][] = $i;
			$this->input_args[2] = $this->pageData;
			$limit = ($page - 1) * $pageSize . "," . $pageSize;
			if('findSql'==$func_name)$conditions .= " LIMIT {$limit}";
		}
		if('findSql'==$func_name){
			return $this->model_obj->findSql($conditions);
		}elseif('spLinker' == $func_name){
			return $this->model_obj->spLinker()->prepare_result($this->model_obj->findAll($conditions, $sort, $fields, $limit));
		}else{
			return $this->model_obj->findAll($conditions, $sort, $fields, $limit);
		}
	}
}

/**
 * spVerifier
 * 数据验证程序
 */
class spVerifier {

	/** 
	 * 附加的检验规则函数
	 */
	private $add_rules = null;
	
	/** 
	 * 验证规则与信息
	 */
	private $verifier = null;
	
	/** 
	 * 验证返回信息记录
	 */
	private $messages = null;
	
	/** 
	 * 待验证字段
	 */
	private $checkvalues = null;
	
    public function __input(& $obj, $args){
		$this->verifier = (null != $obj->verifier) ? $obj->verifier : array();
		if(isset($args[1]) && is_array($args[1])){
			$this->verifier["rules"] = $this->verifier["rules"] + $args[1]["rules"];
			$this->verifier["messages"] = isset($args[1]["messages"]) ? ( $this->verifier["messages"] + $args[1]["messages"] ) : $this->verifier["messages"];
		}
		if(is_array($obj->addrules) && !empty($obj->addrules) ){foreach($obj->addrules as $addrule => $addveri)$this->addrules($addrule, $addveri);}
		if(empty($this->verifier["rules"]))spError("no verifier rules!");
		return is_array($args[0]) ? $this->checkrules($args[0]) : TRUE; // TRUE为不通过验证
	}
	
	public function addrules($rule_name, $checker){
		$this->add_rules[$rule_name] = $checker;
	}
	
	private function checkrules($values){ 
		$this->checkvalues = $values;
		foreach( $this->verifier["rules"] as $rkey => $rval ){
			$inputval = $values[$rkey];
			foreach( $rval as $rule => $rightval ){
				if(method_exists($this, $rule)){
					if(TRUE == $this->$rule($inputval, $rightval))continue;
				}elseif(null != $this->add_rules && isset($this->add_rules[$rule])){
					if( function_exists($this->add_rules[$rule]) ){
						if(TRUE == $this->add_rules[$rule]($inputval, $rightval, $values))continue;
					}elseif( is_array($this->add_rules[$rule]) ){
						if(TRUE == spClass($this->add_rules[$rule][0])->{$this->add_rules[$rule][1]}($inputval, $rightval, $values))continue;
					}
				}else{
					spError("unkown rules");
				}
				$this->messages[$rkey][] = (isset($this->verifier["messages"][$rkey][$rule])) ? $this->verifier["messages"][$rkey][$rule] : "{$rule}";
			}
		}
		// 返回FALSE则通过验证，返回数组则未能通过验证，返回的是提示信息。
		return (null == $this->messages) ? FALSE : $this->messages; 
	}

	private function notnull($val, $right){return $right === ( isset($val) && !empty($val) && "" != $val );}

	private function minlength($val, $right){return $this->cn_strlen($val) >= $right;}

	private function maxlength($val, $right){return $this->cn_strlen($val) <= $right;}
	
	private function equalto($val, $right){return $val == $this->checkvalues[$right];}
	
	private function istime($val, $right){$test = @strtotime($val);return $right == ( $test !== -1 && $test !== false );}
		
	private function email($val, $right){
		return $right == ( preg_match('/^[A-Za-z0-9]+([._\-\+]*[A-Za-z0-9]+)*@([A-Za-z0-9]+[-A-Za-z0-9]*[A-Za-z0-9]+\.)+[A-Za-z0-9]+$/', $val) != 0 );
	}
	
	public function cn_strlen($val){
		while($i<strlen($val)){$clen = ( strlen("快速") == 4 ) ? 2 : 3;
			if(preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/",$val[$i])){$i+=$clen;}else{$i+=1;}$n+=1;}
		return $n;
	}
}

/**
 * spCache
 * 函数缓存程序
 */
class spCache {
	
	/**
	 * 默认的数据生存期
	 */
	public $life_time = 3600;
	

	
	/**
	 * 模型对象
	 */
	private $model_obj = null;
	
	/** 
	 * 调用时输入的参数
	 */
	private $input_args = null;
	
    public function __input(& $obj, $args){
		$this->model_obj = $obj;
		$this->input_args = $args;
		return $this;
	}
	
	public function __call($func_name, $func_args){
		if( isset($this->input_args[0]) && -1 == $this->input_args[0] ){
			return $this->clear( $this->model_obj , $func_name, $func_args);
		}
		return $this->cache_obj( $this->model_obj , $func_name, $func_args, $this->input_args[0]);
	}

	public function cache_obj(& $obj, $func_name, $func_args = null, $life_time = null ){
		$cache_id = get_class($obj) . md5($func_name);
		if( null != $func_args )$cache_id .= md5(serialize($func_args));
		if( $cache_file = spAccess('r', "sp_cache_{$cache_id}") ){
			return unserialize( $cache_file );
		}
		if( null == $life_time ){
			$life_time = $this->life_time;
		}
		$run_result = call_user_func_array(array($obj, $func_name), $func_args);
		spAccess('w', "sp_cache_{$cache_id}", serialize($run_result), $life_time);
		if( $cache_list = spAccess('r', 'sp_cache_list') ){
			$cache_list = explode("\n",$cache_list);
			if( ! in_array( $cache_id, $cache_list ) )spAccess('w', 'sp_cache_list', join("\n", $cache_list) . $cache_id . "\n");
		}else{
			spAccess('w', 'sp_cache_list', $cache_id . "\n");
		}
		return $run_result;
	}

	public function clear(& $obj, $func_name, $func_args = null){
		$cache_id = get_class($obj) . md5($func_name);
		if( null != $func_args )$cache_id .= md5(serialize($func_args));
		if( $cache_list = spAccess('r', 'sp_cache_list') ){
			$cache_list = explode("\n",$cache_list);
			$new_list = '';
			foreach( $cache_list as $single_item ){
				if( $single_item == $cache_id || ( null == $func_args && substr($single_item,0,strlen($cache_id)) == $cache_id ) ){
					spAccess('c', "sp_cache_{$single_item}");
				}else{
					$new_list .= $single_item. "\n";
				}
			}
			spAccess('w', 'sp_cache_list', substr($new_list,0,-1));
		}
		return TRUE;
	}

	public function clear_all(){
		if( $cache_list = spAccess('r', 'sp_cache_list') ){
			$cache_list = explode("\n",$cache_list);
			foreach( $cache_list as $single_item )spAccess('c', "sp_cache_{$single_item}");
			spAccess('c', 'sp_cache_list');
		}
		return TRUE;
	}
}

/**
 * spLinker 
 * 数据库的表间关联程序
 */
class spLinker
{
	/**
	 * 模型对象
	 */
	private $model_obj = null;
	
	/** 
	 * 链接方式指示
	 */
	private $linker = null;
	
	/** 
	 * 预准备的结果
	 */
	private $prepare_result = null;
	
	/**
	 * 可支持的关联方法
	 */
	private $methods = array('find','findBy','findAll','spPager','create','delete','deleteByPk','update');
	/**
	 * 是否启用全部关联
	 */
	public $enabled = TRUE;
	
    public function __input(& $obj, $args = null){
		$this->linker = ((null != $args) ? $args[0] : array()) + ((null != $obj->linker) ? $obj->linker : array());
		if( !is_array($this->linker) or empty($this->linker) or (null != $args && FALSE == $args[0]) )$this->enabled = FALSE;
		$this->model_obj = $obj;
		return $this;
	}
	
	public function __call($func_name, $func_args){
		if( in_array( $func_name, $this->methods ) && FALSE != $this->enabled ){
			if( 'delete' == $func_name || 'deleteByPk' == $func_name )$maprecords = $this->prepare_delete($func_name, $func_args);
			if( null != $this->prepare_result ){
				$run_result = $this->prepare_result;$this->prepare_result();
			}elseif( !$run_result = call_user_func_array(array($this->model_obj, $func_name), $func_args) ){
				if( 'update' != $func_name )return FALSE;
			}
			if( null != $this->linker ){
				foreach( $this->linker as $thelinker ){
					if( FALSE == $thelinker['enabled'] )continue;
					$thelinker['type'] = strtolower($thelinker['type']);
					if( 'find' == $func_name || 'findBy' == $func_name ){
						$run_result[$thelinker['map']] = $this->do_select( $thelinker, $run_result );
					}elseif( 'findAll' == $func_name ){
						foreach( $run_result as $single_key => $single_result )
							$run_result[$single_key][$thelinker['map']] = $this->do_select( $thelinker, $single_result );
					}elseif( 'create' == $func_name ){
						$this->do_create( $thelinker, $run_result, $func_args );
					}elseif( 'update' == $func_name ){
						$this->do_update( $thelinker, $func_args );
					}elseif( 'delete' == $func_name || 'deleteByPk' == $func_name ){
						$this->do_delete( $thelinker, $maprecords );
					}
				}
			}
			return $run_result;
		}else{
			return call_user_func_array(array($this->model_obj, $func_name), $func_args);
		}
	}
	public function prepare_result($run_result = null){
		unset($this->prepare_result);$this->prepare_result = null;
		$this->prepare_result = $run_result;
		return $this;
	}
	private function prepare_delete($func_name, $func_args)
	{
		if('deleteByPk'==$func_name){
			return $this->model_obj->findAll(array($this->model_obj->pk=>$func_args[0]));
		}else{
			return $this->model_obj->findAll($func_args[0]);
		}
	}
	private function do_delete( $thelinker, $maprecords ){
		if( FALSE == $maprecords )return FALSE;
		foreach( $maprecords as $singlerecord ){
			if(!empty($thelinker['condition'])){
				if( is_array($thelinker['condition']) ){
					$fcondition = array($thelinker['fkey']=>$singlerecord[$thelinker['mapkey']]) + $thelinker['condition'];
				}else{
					$fcondition = "{$thelinker['fkey']} = '{$singlerecord[$thelinker['mapkey']]}' AND {$thelinker['condition']}";
				}
			}else{
				$fcondition = array($thelinker['fkey']=>$singlerecord[$thelinker['mapkey']]);
			}
			$returns = spClass($thelinker['fclass'])->delete($fcondition);
		}
		return $returns;
	}
	private function do_update( $thelinker, $func_args ){
		if( !is_array($func_args[1][$thelinker['map']]) )return FALSE;
		if( !$maprecords = $this->model_obj->findAll($func_args[0]))return FALSE;
		foreach( $maprecords as $singlerecord ){
			if(!empty($thelinker['condition'])){
				if( is_array($thelinker['condition']) ){
					$fcondition = array($thelinker['fkey']=>$singlerecord[$thelinker['mapkey']]) + $thelinker['condition'];
				}else{
					$fcondition = "{$thelinker['fkey']} = '{$singlerecord[$thelinker['mapkey']]}' AND {$thelinker['condition']}";
				}
			}else{
				$fcondition = array($thelinker['fkey']=>$singlerecord[$thelinker['mapkey']]);
			}
			$returns = spClass($thelinker['fclass'])->update($fcondition, $func_args[1][$thelinker['map']]);
		}
		return $returns;
	}
	private function do_create( $thelinker, $newid, $func_args ){
		if( !is_array($func_args[0][$thelinker['map']]) )return FALSE;
		if('hasone'==$thelinker['type']){
			$newrows = $func_args[0][$thelinker['map']];
			$newrows[$thelinker['fkey']] = $newid;
			return spClass($thelinker['fclass'])->create($newrows);
		}elseif('hasmany'==$thelinker['type']){
			if(array_key_exists(0,$func_args[0][$thelinker['map']])){ // 多个新增
				foreach($func_args[0][$thelinker['map']] as $singlerows){
					$newrows = $singlerows;
					$newrows[$thelinker['fkey']] = $newid;
					$returns = spClass($thelinker['fclass'])->create($newrows);	
				}
				return $returns;
			}else{ // 单个新增
				$newrows = $func_args[0][$thelinker['map']];
				$newrows[$thelinker['fkey']] = $newid;
				return spClass($thelinker['fclass'])->create($newrows);
			}
		}
	}
	private function do_select( $thelinker, $run_result ){
		if( FALSE == $thelinker['enabled'] )return FALSE;
		if(empty($thelinker['mapkey']))$thelinker['mapkey'] = $this->model_obj->pk;
		if( 'manytomany' == $thelinker['type'] ){
			$do_func = 'findAll';
			$midcondition = array($thelinker['mapkey']=>$run_result[$thelinker['mapkey']]);
			if( !$midresult = spClass($thelinker['midclass'])->findAll($midcondition,null,$thelinker['fkey']) )return FALSE;
			$tmpkeys = array();foreach( $midresult as $val )$tmpkeys[] = "'".$val[$thelinker['fkey']]."'";
			if(!empty($thelinker['condition'])){
				if( is_array($thelinker['condition']) ){
					$fcondition = "{$thelinker['fkey']} in (".join(',',$tmpkeys).")";
					foreach( $thelinker['condition'] as $tmpkey => $tmpvalue )$fcondition .= " AND {$tmpkey} = '{$tmpvalue}'";
				}else{
					$fcondition = "{$thelinker['fkey']} in (".join(',',$tmpkeys).") AND {$thelinker['condition']}";
				}
			}else{
				$fcondition = "{$thelinker['fkey']} in (".join(',',$tmpkeys).")";
			}
		}else{
			$do_func = ( 'hasone' == $thelinker['type'] ) ? 'find' : 'findAll';
			if(!empty($thelinker['condition'])){
				if( is_array($thelinker['condition']) ){
					$fcondition = array($thelinker['fkey']=>$run_result[$thelinker['mapkey']]) + $thelinker['condition'];
				}else{
					$fcondition = "{$thelinker['fkey']} = '{$run_result[$thelinker['mapkey']]}' AND {$thelinker['condition']}";
				}
			}else{
				$fcondition = array($thelinker['fkey']=>$run_result[$thelinker['mapkey']]);
			}
		}
		if(TRUE == $thelinker['countonly'])$do_func = "findCount";
		return spClass($thelinker['fclass'])->$do_func($fcondition, $thelinker['sort'], $thelinker['field'], $thelinker['limit'] );
	}
}
