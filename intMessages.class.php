<?php

/**
* Класс для организации внутренней почты на движке rSite'а
* Я настолько часто делал эту срань, что заебался и решил сделать общий класс.
* Пока все очень просто и в общих чертах, насколько вообще нужно. 
*/


@define('IMSG_TABLE', 'messages'); // таблица с мессагами. можно переопределить, поэтому стоит @

require_once('rlib/rTable.class.php');

class intMessages extends rTableClass{
	
	var $userLink = null; // сообщения подразумевают, что к ним полюбасику привязан юзер
	
	var $select = 'SELECT m.* FROM messages m'; // для переопределения в наследниках
	
	function __construct($db, $user){
		parent::__construct($db, IMSG_TABLE);
		$this->userLink = $user;
	}
	
	function getInbox($limit = 20){
		$m = $this->db->select($this->select.' WHERE to_id = ?d AND in_inbox = 1 ORDER BY m.id DESC LIMIT '.$limit, $this->userLink->getID());
		foreach($m as $n=>$v){
			$this->_processMSG(&$m[$n]);
		}
		return $m;
	}
	
	function getSentbox($limit = 20){
		$m = $this->db->select($this->select.' WHERE from_id = ?d AND in_sentbox = 1 ORDER BY m.id DESC LIMIT '.$limit, $this->userLink->getID());
		foreach($m as $n=>$v){
			$this->_processMSG(&$m[$n]);
		}
		return $m;
	}
	
	// сколько всего сообщений в инбоксе
	function inInbox(){
		return $this->getCount('in_inbox = 1 AND to_id = '.$this->userLink->getID());
	}
	
	// сколько всего сообщений в отправленных
	function inSentbox(){
		return $this->getCount('in_inbox = 1 AND to_id = '.$this->userLink->getID());
	}
	
	function getMessage($id){
		$m = $this->db->selectRow($this->select.' WHERE m.id = ?d', $id);
		if( $m['from_id'] != $this->userLink->getID() && 
			$m['to_id'] != $this->userLink->getID() ) return false;
		
		$this->_processMSG(&$m);
		
		return $m;
	}
	
	function delete($id){
		$m = $this->getMessage($id);
		
		if($m['from_id'] == $this->userLink->getID()){
			$this->put($id, array(
				'in_sentbox' => 0
			));
			return true;
		}
		if($m['to_id'] == $this->userLink->getID()){
			$this->put($id, array(
				'in_inbox' => 0
			));
			return true;
		}
		
		return false;
	}
	
	function create($toUser, $title, $msg){
		if(!$msg) return false;
		$this->add(array(
			'from_id' => $this->userLink->getID(),
			'to_id' => $toUser,
			'sentdate' => time(),
			'title' => htmlspecialchars($title),
			'text' => htmlspecialchars($msg)
		));
		return true;
	}
	
	// для всяческих обработок сообщений. нужно для переопределения в наследниках
	function _processMSG(&$msg){
		
	}
	
	
}