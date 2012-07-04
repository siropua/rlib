<?php


class rTableClass
{
	var $db = null;
	
	var $table = '';
	
	function __construct($dbLink, $table)	{
		$this->db = $dbLink;
		$this->setTable($table);
	}
	
	function setTable($table){ $this->table = $table; }
	function getTable(){ return $this->table; }
	
	function get($id, $field = null){
		
		if(is_array($id)){
			$w = array();
			foreach($id as $n=>$v) $w[] = "`$n` = '".mysql_real_escape_string($v)."'";
			$w = implode(' AND ', $w);
			return $this->db->selectRow('SELECT * FROM ?# WHERE '.$w.' LIMIT 1', 
			$this->table);
		}
		
		if($field !== null){
			return $this->db->selectRow('SELECT * FROM ?# WHERE ?# = ? LIMIT 1',
				$this->table, $field, $id);
		}
		
		return $this->db->selectRow('SELECT * FROM ?# WHERE id = ?', 
			$this->table, $id);
	}
	
	function getCell($field, $where){
		if(is_numeric($where)){
			$where = 'id = '.$where;
		}
		return $this->db->selectCell('SELECT ?# FROM ?# WHERE '.$where, $field,	$this->table);
	}
	
	function put($id, $data, $allowed = null)
	{
		if($allowed && is_array($allowed)){
			$data2 = array();
			foreach($allowed as $f) if(isset($data[$f])) $data2[$f] = $data[$f];
			$data = $data2;
		}

		if(is_array($id)){
			//if(isset($id[0]) && is_numeric($id[0]))
				$this->db->query('UPDATE ?# SET ?a WHERE id IN (?a)', $this->table, $data, $id);
			
		}elseif(is_numeric($id)){
			$this->db->query('UPDATE ?# SET ?a WHERE id = ?d', $this->table, $data, $id);
		}else{
			$this->db->query('UPDATE ?# SET ?a WHERE id = ?', $this->table, $data, $id);
		}
		return true;
	}
	
	function add($data, $allowed = null)
	{		
		if($allowed && is_array($allowed)){
			$data2 = array();
			foreach($allowed as $f) if(@$data[$f]) $data2[$f] = $data[$f];
			$data = $data2;
		}
	
		$id = $this->db->query('INSERT INTO ?# SET ?a', $this->table, $data);
		return $id;
	}
	
	function remove($id)
	{
		if(is_array($id)){
			$this->db->query('DElETE FROM ?# WHERE id IN (?a)', $this->table, $id);
		}elseif(is_numeric($id)){
			$this->db->query('DElETE FROM ?# WHERE id = ?', $this->table, $id);
		}else{
			$this->db->query('DELETE FROM ?# WHERE '.$id, $this->table);
		}
	}
	
	function getList($where = '', $order = '', $limit = ''){
		if($where)
			$where = ' WHERE '.$this->formatWhere($where).' ';
		else
			$where = '';
		
		if($order)
			$order = ' ORDER BY '.$order.' ';
		else
			$order = '';
		
		if($limit)
			$limit = ' LIMIT '.$limit.' ';
		else
			$limit = '';
		
		return $this->db->select('SELECT * FROM ?#'.$where.$order.$limit, $this->table);
	}
	
	function getRow($where = '', $order = ''){
		if($where)
			$where = ' WHERE '.$where.' ';
		else
			$where = '';
		
		if($order)
			$order = ' ORDER BY '.$order.' ';
		else
			$order = '';
		return $this->db->selectRow('SELECT * FROM ?#'.$where.$order.' LIMIT 1', $this->table);
	}
	
	function getCount($where = '')
	{
		if($where)
			$where = ' WHERE '.$where;
		else
			$where = '';
		return $this->db->selectCell('SELECT COUNT(*) FROM ?#'.$where, $this->table);
	}
	
	function inc($field, $rowID)
	{
		$where = is_numeric($rowID) ? ' WHERE id = '.$rowID : ' WHERE '.$rowID;
		$this->db->query('UPDATE ?# SET ?# = ?# + 1 '.$where, $this->table, $field, $field);
	}
	
	function dec($field, $rowID)
	{
		$where = is_numeric($rowID) ? ' WHERE id = '.$rowID : ' WHERE '.$rowID;
		$this->db->query('UPDATE ?# SET ?# = ?# - 1 '.$where, $this->table, $field, $field);
	}
	
	function max($field, $where = ''){
		if($where){
			$where = ' WHERE '.$this->formatWhere($where);
		}
		return $this->db->selectCell('SELECT MAX(?#) FROM ?# '.$where, $field, $this->table);
	}
	
	function min($field, $where = ''){
		if($where){
			$where = 'WHERE '.$where;
		}
		return $this->db->selectCell('SELECT MAX(?#) FROM ?# '.$where, $field, $this->table);
	}
	
	function sum($field, $where = ''){
		if($where){
			$where = 'WHERE '.$where;
		}
		return $this->db->selectCell('SELECT SUM(?#) FROM ?# '.$where, $field, $this->table);		
	}
	
	/**
	* Превращает массив с условиями WHERE в sql-строку
	* @var mixed $where исходные данные
	* @return string sql-строка
	*/
	protected function formatWhere($where = false){
		if(is_array($where)){
			$where1 = array();
			foreach($where as $f=>$v){
				if($v === null){
					$where1[] = "ISNULL(`$f`)";
				}else{
					$where1[] = $f . " = '".mysql_escape_string($v)."'";
				}
			}
			$where = implode(' AND ', $where1);
		}
		
		return $where;
	}
}