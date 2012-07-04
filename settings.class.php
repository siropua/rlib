<?php

require_once('rlib/rTable.class.php');


class siteSettings extends rTableClass{
	
	private $cache = array();
	
	function __construct($table = 'site_settings', $db){
		parent::__construct($db, $table);
	}
	
	function getValue($id, $default = false, $autoCreate = true){
		
		if(isset($this->cache[$id])) return $this->cache[$id];
		
		$s = parent::get($id);
		if(!$s){
			if($autoCreate){
				$this->add($id, $id, 'text');
				$this->set($id, $default);
			}
			$this->cache[$id] = $default;
			return $default;
		}
		$this->cache[$id] = $s['value'];
		return $s['value'];
	}
	
	function set($id, $val){
		
		$this->db->query('INSERT INTO ?# SET value = ?, id = ? ON DUPLICATE KEY UPDATE value = VALUES(value)', $this->table, $val, $id);
		$this->cache[$id] = $val;
	}
	
	function add($id, $name = NULL, $type = NULL){
		parent::add(array(
			'id' => $id,
			'type' => $type,
			'name' => $name
		));
	}
	
	function loadAll(){
		$cache = $this->db->selectCol('SELECT id AS ARRAY_KEY, value FROM ?#', $this->table);
		return $cache;
	}
	
}