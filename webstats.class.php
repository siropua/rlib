<?php


require_once('rlib/referer.class.php');
require_once('rlib/rTable.class.php');


/**
 * Класс, обеспечивающий основную работу со статистикой
 *
 * @package rLib
 * @author Steel Ice
 * @copyright Copyright (c) 2010
 * @version $Id$
 * @access public
 **/
class webStats{

	protected $db = null;

	public $referer = null;
	public $agent = null;
	
	protected $tbl_prefix = 'ref_';

	function __construct($db){
		$this->db = $db;
		$this->referer = new Referer;
		$this->agent = new userAgent($db, 'stat_agents');
	}

	/**
	 * Возвращает IP пользователя
	 *
	 * @return string
	 **/
	public function IP(){
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Возвращает IP пользователя в числовом формате
	 *
	 * @return int
	 **/
	public function intIP($ip = null){
		return ip2long($ip ? $ip : $this->IP());
	}
	
	public function trackReferer(){	

		if(defined('REFERER_TRACK_DISABLED') && REFERER_TRACK_DISABLED) return false;
		
		if(!$data = $this->referer->parse()) return false;
		$visitHash = empty($data['search']) ? md5($data['url']) : md5($data['search'].' '.$data['search_engine']);
		$sourceID = $this->db->selectCell('SELECT id FROM ?# WHERE hash = ?', $this->tbl_prefix.'sources', $visitHash);
		if(!$sourceID)
			$sourceID = $this->db->query('INSERT INTO ?# SET ?a', $this->tbl_prefix.'sources', array(
				'hash' => $visitHash,
				'url' => $data['url'],
				'search' => trim(@$data['search']),
				'search_engine' => trim(@$data['search_engine']),
				'total_visits' => 1,
				'first_visit' => time(),
				'last_visit' => time()
			));
		else
			$this->db->query('UPDATE ?# SET last_visit = ?d, total_visits = total_visits + 1, url = ? WHERE id = ?d',
				$this->tbl_prefix.'sources', time(), $data['url'], $sourceID);

		$landingID = $this->db->query('INSERT INTO ?# SET uri = ?, first_land = ?d, last_land = ?d ON DUPLICATE KEY UPDATE lands_count = lands_count + 1, last_land = VALUES(last_land)', 
			$this->tbl_prefix.'landings', $_SERVER['REQUEST_URI'], time(), time());
		
		return $this->db->query('INSERT INTO ?# SET source_id = ?d, dateadd = ?d, datevisit = CURDATE(), ip = ?d, land_id = ?d ON DUPLICATE KEY UPDATE visits = visits + 1',
			$this->tbl_prefix.'visits', $sourceID, time(), $this->intIP(), $landingID);		
		
	
	}


}


/**
 * Работа с USER AGENT
 *
 * @package rlib
 * @author Steel Ice
 * @copyright Copyright (c) 2010
 * @version $Id$
 * @access public
 **/
class userAgent extends rTableClass{

	protected $agent = '';

	function __construct($db, $table){
		if(!empty($_SERVER['HTTP_USER_AGENT']))
			$this->setAgent($_SERVER['HTTP_USER_AGENT']);
		parent::__construct($db, $table);
	}

	/**
	 * Возвращает ID агента. Если юзерагент не найден - он будет создан в таблице.
	 *
	 * @return int
	 **/
	function getAgentID(){
		$id = $this->db->selectCell('SELECT id FROM ?# WHERE name = ?',
			$this->table, $this->agent);
		if(!$id)
			$id = $this->add(array(
				'name' => $this->agent
			));
		return $id;
	}

	/**
	 * Устанавливает юзерагента
	 *
	 * @param mixed $agent Строка юзерагента
	 * @return
	 **/
	public function setAgent($agent){
		$this->agent = $agent;
	}

	/**
	 * Возвращает строку юзерагента
	 *
	 * @return string строка юзерагента
	 **/
	public function getAgent(){
		return $this->agent;
	}

}