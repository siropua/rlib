<?php

require_once "rlib/twitteroauth/twitteroauth.php";

class rTwitter{
	
	protected $db = null;
	protected $user = null;
	
	protected $authData = array();
	
	protected $connection = null;
	
	public function __construct(&$db, $user){
		$this->db = $db;
		if(is_numeric($user)){
			$this->user = new myUser($db, '', false);
			$this->user->getByID($user);
		}else{
			$this->user = $user;
		}
		
		if($this->user->getID()){
			$this->authData = $this->db->selectRow('SELECT * FROM users_twitter_auth 
				WHERE user_id = ?d', $this->user->getID());
		}
	}
	
	public function createConnetion(){
		if($this->connection) return true;
		
		$this->connection = new TwitterOAuth(
			TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, 
			$this->authData['oauth_token'], $this->authData['oauth_token_secret']
		);
		
		$this->connection->get('account/verify_credentials');
		
		if($this->connection->http_code == 401){
			return $this->loggedOut();
		}elseif($this->connection->http_code == 400){
			return false;
		}
		
		return true;
	}
	
	public function twit($msg, $id = 0){
		if(!$this->authData) return false;
		if(empty($this->authData['is_valid'])) return false;
		if(!defined('TWITTER_CONSUMER_KEY') || !TWITTER_CONSUMER_KEY) return false;
		if(!defined('TWITTER_CONSUMER_SECRET') || !TWITTER_CONSUMER_SECRET) return false;
		if(!$this->connection) if(!$this->createConnetion()) return false;
		
		$return = $this->connection->post('statuses/update', array(
			'status' => $msg, 'trim_user' => 1
		));
		
		$this->saveTwit($return, $id);
		
		// print_r($return);
		
		return true;
	}
	
	protected function loggedOut(){
		$this->db->query('UPDATE users_twitter_auth SET is_valid = 0 WHERE user_id = ?d', 
			$this->user->getID());
		
		return false;
	}
	
	public function saveTwit($twit, $id = 0){
		$this->db->query('INSERT INTO users_twitts SET ?a ON DUPLICATE KEY UPDATE text = VALUES(text)', array(
			'twitter_id' => $this->authData['twitter_uid'],
			'user_id' => $this->user->getID(),
			'status_id' => $twit->id,
			'source' => $twit->source,
			'text' => $twit->text,
			'created_at' => $twit->created_at,
			'created_at_int' => @strtotime($twit->created_at),
			'dateadd' => time(),
			'post_id' => $id
		));
	}
}