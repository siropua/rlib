<?php


class rInvite extends rTableClass{
	
	
	public function __construct(&$db, $table = 'invites'){
		parent::__construct($db, $table);
	}
	
	public function getByCode($code){
		return parent::get($code, 'code');
	}
	
	public function useByID($id){
		$id = (int)$id;
		if(!$id) return false;
		
		$this->db->query('UPDATE ?# 
				SET invites_left = invites_left - 1, 
					used_by_count = used_by_count + 1, 
					lastuse = ?d 
				WHERE id = ?d',
					$this->table, time(), $id
		);
		
		return true;
	}
	
	public function checkByCode($code){
		$i = $this->getByCode($code);
		if(!$i) return false;		
		return $i['invites_left'] > 0;
	}
	
	public function getUserInvitesCount($uid){
		$uid = (int)$uid;
		if(!$uid) return 0;
		
		return parent::sum('invites_left', '`user_id` = '.$uid);
	}
	
	public function getUserInvites($uid){
		return $this->db->select('SELECT * FROM ?# WHERE user_id = ?d AND (invites_left) ORDER BY invites_left DESC', $this->table, $uid);
	}
}