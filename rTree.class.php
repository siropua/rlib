<?php

class rTree
{
	private $_db = null;
	private $_table = '';
	private $_fields = array('*');
	private $_order = '';
	private $_where = '';
	private $_fieldset = array('id' => 'id', 'parent' => 'parent_id');
	
	function __construct($db, $table, $fields = array(), $order = array(), $where= ''){
		$this->_db = $db;
		$this->_table = $table;
		if (!empty($fields)) $this->_fields = $fields;
		if (!empty($order)) $this->_order = ' ORDER BY '.implode(',', $order);
		if (!empty($where)) $this->_where = ' WHERE '.$where;
	}
	
	function setTreeSettings($fields = array()){
		if (!empty($fields)){
			if (@isset($fields['id'])) $this->_fieldset['id'] = $fields['id'];
			if (@isset($fields['parent'])) $this->_fieldset['parent'] = $fields['parent'];
		}
	}
	
	protected function getQueryRes($fields = false){
		if (!$fields) $fields = $this->_fields;
		if (!in_array($this->_fieldset['id'], $fields) && !in_array('*', $fields)) $fields[] = $this->_fieldset['id'];
		if (!in_array($this->_fieldset['parent'], $fields) && !in_array('*', $fields)) $fields[] = $this->_fieldset['parent'];
		return $this->_db->select('SELECT '.implode(',', $fields).' FROM '.$this->_table.$this->_where.$this->_order);
	}
	
	protected function createArray($res){
		$tree = array();
		foreach ($res as $row)
			foreach (array_keys($row) as $key) 
				$tree[$row[$this->_fieldset['parent']]][$row[$this->_fieldset['id']]][$key] = $row[$key];
		return $tree;
	}
	
	protected function createTreeArray($tree, $pid = 0){
		static $level;
		$res_array = array();
		
		$level = isset($level) ? ++$level : 1;
		foreach ($tree as $id => $root){
			if ($pid != $id) continue;
			if (count($root)){
				$_f = true;
				foreach ($root as $key => $val){
					$res_array[$key] = $val;
					$res_array[$key]['level'] = $level;
					if ($_f){
						$res_array[$key]['index'] = 'first';
						$_f = false;
					}else $res_array[$key]['index'] = '';
					if (count(@$tree[$key])){
						$res_array += $this->createTreeArray($tree, $key);
					}else $res_array[$key]['end'] = 1;
				}
				if (!$res_array[$key]['index']) $res_array[$key]['index'] = 'last';
				else $res_array[$key]['index'] = 'single';
			}
		}
		$level--;
		return $res_array;
	}
	
	function createTree($res, $pid = 0){
		$tree = $this->createArray($res);
		return $this->createTreeArray($tree, $pid);
	}
	
	function getTree($pid = 0){
		return $this->createTree($this->getQueryRes(), $pid);
	}
	
	function getChildArray($pid = 0){
		$res_array = array();
		$tree_array = $this->createTree($this->getQueryRes(array('id', 'parent_id')));
		reset($tree_array);
		list($key, $val) = each($tree_array);
		while ($pid != $val['id']) list($key, $val) = each($tree_array);
		$_l = $val['level'];
		$res_array[] = $key;
		list($key, $val) = each($tree_array);
		while ($val['level'] > $_l){
			$res_array[] = $key;
			list($key, $val) = each($tree_array);
		}
		return $res_array;
	}
	
	function getPathArray($id = 0){
		$res_array = array();
		if (!$id) return $res_array;
		$tree_array = array_reverse($this->createTree($this->getQueryRes(array('id', 'parent_id'))), true);
		reset($tree_array);
		list($key, $val) = each($tree_array);
		while ($id != $val['id']) list($key, $val) = each($tree_array);
		$_l = $val['level'];
		while ($val['level'] > 1){
			list($key, $val) = each($tree_array);
			if ($_l > $val['level']){
				$res_array[$val['level']] = $key;
				$_l = $val['level'];
			}
		}
		return array_reverse($res_array, true);
	}
	
	function getFullPathArray($id = 0){
		$res_array = array();
		if (!$id) return $res_array;
		$tree_array = array_reverse($this->createTree($this->getQueryRes(array('id', 'parent_id'))), true);
		reset($tree_array);
		list($key, $val) = each($tree_array);
		while ($id != $val['id']) list($key, $val) = each($tree_array);
		$_l = $val['level'];
		while ($val['level'] > 1){
			list($key, $val) = each($tree_array);
			if ($_l > $val['level']){
				$res_array[$val['level']] = $key;
				$_l = $val['level'];
			}
		}
		$res_array = array_reverse($res_array, true);
		$res_array[$tree_array[$id]['level']] = $id;
		return $res_array;
	}
	
}
