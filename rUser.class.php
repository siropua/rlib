<?php

/**
 *	Users Class
 *
 * @version $Id: rUser.class.php,v 1.12 2008/05/21 09:44:33 steel Exp $
 * @copyright 2007
 */


if(!defined('USERS_TABLE')) define('USERS_TABLE', 'users');
if(!defined('UID_FIELD')) define('UID_FIELD', 'id');
if(!defined('LOGIN_FIELD')) define('LOGIN_FIELD', 'login');
if(!defined('PASS_FIELD')) define('PASS_FIELD', 'password');
if(!defined('PASSWORD_HASH_METHOD')) define('PASSWORD_HASH_METHOD', 'md5');
if(!defined('LOGIN_PREG')) define('LOGIN_PREG', '/^[a-z][a-z0-9\-_]+$/i');
if(!defined('DELETED_FLAG')) define('DELETED_FLAG', 'deleted');
if(!defined('USER_BLOCKED_FLAG')) define('USER_BLOCKED_FLAG', 'is_blocked');

/**
* login results
*/


if(!defined('LOGIN_OK')) define('LOGIN_OK', 1);
if(!defined('LOGIN_ERR_LOGIN')) define('LOGIN_ERR_LOGIN', 0);
if(!defined('LOGIN_NO_USER')) define('LOGIN_NO_USER', -1);
if(!defined('LOGIN_PASS_ERR')) define('LOGIN_PASS_ERR', -2);
if(!defined('LOGIN_BLOCKED_USER')) define('LOGIN_BLOCKED_USER', -3);


if(!defined('USERS_PATH')) define('USERS_PATH', ROOT . '/users_data');
if(!defined('USERS_URL')) define('USERS_URL', ROOT_URL . 'users_data/');

@define("RIGHTS_DELIM", "|");
@define("DENY_ALL_RIGHT", "deny_all");
@define("ACCEPT_ALL_RIGHT", "allow_all");



/**
 *
 *
 **/
class rUser{

	var $_db = null;
	var $_cookie_prefix = '';
	var $_authed = false;
	var $_auth_checked = false;
	var $_ID = 0;
	var $_data = array();
	var $_cookie_domain = '';
	var $_cookie_path = '/';
	var $_can = array();
	var $_lastAuthError = '';

	var $_selectString = 'SELECT * FROM users AS u ';
	
	protected $userpics = array(
		'' => array('prefix' => '100-', 'w' => 100, 'h' => 100, 'assign_as_next' => 1),
		'_50' => array('prefix' => '50-', 'w' => 1280, 'h' => 1024, 'assign_as_next' => true, 'method' => 'crop'),
		'_24' => array('prefix' => '24-', 'w' => 300, 'h' => 300, 'assign_as_next' => true, 'method' => 'crop'),
	
	);
	
	protected $userpicFolder = 'img';

	/**
	* Constructor
	* @param object $db_link Link to PEAR database object
	* @param string $cookie_prefix Prefix for cookies
	* @param mixed $autoauth if true, authenticate user after object creation
	* @return void
	*/
	function __construct($db_link, $cookie_prefix = 'user_', $autoauth = true){
		$this->_db = $db_link;
		$this->_cookie_prefix = $cookie_prefix;
		$this->_resetState();
		if($autoauth)
			$this->auth();

		if(defined('SITE_DOMAIN')){
			$this->_cookie_domain = SITE_DOMAIN;
		}
	}

	/**
	* Auth user
	* @return bool
	*/
	function auth()
	{
		global $_COOKIE, $_SESSION;

		/** filling  uid и hash vars.
		*
		*/
		@$this->_ID = (int)$_SESSION[$this->_cookie_prefix.'uid'];
		@$hash = trim($_SESSION[$this->_cookie_prefix.'hash']);

		if(!$this->_ID || !$hash)
		{
			@$this->_ID = (int)$_COOKIE[$this->_cookie_prefix.'uid'];
			@$hash = trim($_COOKIE[$this->_cookie_prefix.'hash']);
		};

		if(!$this->_ID || !$hash)
		{
			$_authed = false;
			$this->_auth_checked = true;
			$this->_lastAuthError = 'No ID ('.$this->_ID.') or HASH ('.$hash.')';
			return false;
		}

		/** no user */
		if(!$this->getByID($this->_ID))
		{
			$_authed = false;
			$this->_auth_checked = true;
			$this->_lastAuthError = 'No user '.$this->_ID.' in database';
			return false;
		}

		/** incorrect hash */
		if($this->_data[PASS_FIELD] != $hash){
			$this->_lastAuthError = 'Hash '.$this->_data[PASS_FIELD].' do not match'.$hash;
			$_authed = false;
			$this->_auth_checked = true;
			return false;
		}
		
		  /** user deleted */
		if(!empty($this->_data[DELETED_FLAG])){
				$this->_lastAuthError = 'User '.$this->_ID.' deleted';
				$_authed = false;
				$this->_auth_checked = true;
				return false;
		}
		
		/** user blocked */
		if(!empty($this->_data[USER_BLOCKED_FLAG])){
				$this->_lastAuthError = 'User '.$this->_ID.' is blocked';
				$_authed = false;
				$this->_auth_checked = true;
				return false;
		}

		$_SESSION[$this->_cookie_prefix.'uid'] = $this->_ID;
		$_SESSION[$this->_cookie_prefix.'hash'] = $hash;

		$this->_auth_checked = true;
		$this->_authed = true;
		$this->_can = unserialize($this->_data['rights']);
		return true;


	}

	/**
	* Login user
	* @param string $login
	* @param string $password
	* @param integer $save_time
	* @return int see LOGIN_* defines to determine login result
	*/
	function login($login, $password, $save_time = 0)
	{
		global $_SESSION, $_COOKIE;
		$save_time = (int)$save_time;
		$this->_resetState();
		if(!preg_match(LOGIN_PREG, $login) || !$password)
		{
			return LOGIN_ERR_LOGIN;
		}
		if(!$this->getByLogin($login))
			return LOGIN_NO_USER;

		$hashedPass = $this->hashPassword($password, $this->salt);

		
		if($hashedPass != $this->_data[PASS_FIELD])
			return LOGIN_PASS_ERR;

		if(DELETED_FLAG && !empty($this->_data[DELETED_FLAG]))
			return LOGIN_NO_USER;
		
		if(USER_BLOCKED_FLAG && !empty($this->_data[USER_BLOCKED_FLAG]))
			return LOGIN_BLOCKED_USER;

		$this->_ID = (int)$this->_data['id'];

		$this->saveAuthData($save_time);

		return LOGIN_OK;
	}

	public function saveAuthData($save_time = 0)
	{

		$_COOKIE[$this->_cookie_prefix.'uid'] =
			$_SESSION[$this->_cookie_prefix.'uid'] = $this->_data['id'];
		$_COOKIE[$this->_cookie_prefix.'hash'] =
			$_SESSION[$this->_cookie_prefix.'hash'] = $this->_data[PASS_FIELD];


		setcookie($this->_cookie_prefix.'uid', $this->_data['id'], $save_time, $this->_cookie_path);
		setcookie($this->_cookie_prefix.'hash', $this->_data[PASS_FIELD], $save_time, $this->_cookie_path);


		$this->_db->query('UPDATE ?# SET ip = ?, last_login = ? WHERE id = ?',
			USERS_TABLE, $this->getIP(), time(), $this->_ID);

		$this->auth();
		
	}

	/**
	* Хеширует пароль по заданому алгоритму
	* @param mixed $password
	* @param sting $salt
	* @return mixed
	*/
	function hashPassword($password, $salt = ''){
		if(!$salt && defined('USER_FORCE_SALT_HASH') && USER_FORCE_SALT_HASH)
			$salt = $this->salt;
		switch(PASSWORD_HASH_METHOD){
			case 'md5x2':
				return md5(md5($password).$salt);
			case 'md5sha1':
				return sha1(md5($password).$salt);
			break;
			case 'sha1salt':
				return sha1($salt.$password);
			
		}
		return md5($password);
	}
	
	/**
	* Устанавливает пароль
	* @param mixed $password
	* @param bool $forceLogin
	* @return void
	*/
	function setPassword($password, $forceLogin = true){
		$p = $this->hashPassword($password);
		$l = $this->authed();
		$this->setField(PASS_FIELD, $p);
		if($l && $forceLogin) $this->login($this->_data[LOGIN_FIELD], $password);
	}

	/**
	* Clear session and cookie
	* @return void
	*/
	function logout()
	{
		global $_SESSION, $_COOKIE;

		$_COOKIE[$this->_cookie_prefix.'uid'] =
			$_SESSION[$this->_cookie_prefix.'uid'] = 0;
		$_COOKIE[$this->_cookie_prefix.'hash'] =
			$_SESSION[$this->_cookie_prefix.'hash'] = '';


		setcookie($this->_cookie_prefix.'uid', 0, 0, $this->_cookie_path);
		setcookie($this->_cookie_prefix.'hash', '', 0, $this->_cookie_path);

		$this->_resetState();

	}

	/**
	* rUser::authed()
	* @param bool $force_reauth if true, user will be reauthed
	* @return bool authed or not =)
	*/
	function authed($force_reauth = false)
	{
		if(!$this->_auth_checked || $force_reauth)
				$this->auth();
		return $this->_authed;
	}

	/** in php5 we can use $user->field_name for read any user fields */
	/**
	* rUser::__get()
	* @param mixed $field
	* @return mixed
	*/
	function __get($field)
	{
		/* if(!$this->authed())
			return null; */
		return @$this->_data[$field];
	}

	/**
	* can()
	* @param mixed $action
	* @return mixed
	*/
	function can($action)
	{
		if(!$this->authed())
			return false;
		return (@$this->_can[$action] || @$this->_can[ACCEPT_ALL_RIGHT]) && (!@$this->_can[DENY_ALL_RIGHT]);
	}

	/**
	* cagetIDn()
	* @return mixed
	*/
	function getID()
	{
		return $this->_ID;
	}

	/**
	* rUser::getData()
	* @param mixed $key
	* @return mixed
	*/
	function getData($key = null)
	{
		return  $key == null ? $this->_data : $this->_data[$key];
	}

	/**
	* rUser::changePassword()
	* @param mixed $new_pass
	* @return bool
	*/
	function changePassword($new_pass){
		$this->_db->query('UPDATE ?# SET password = ? WHERE id = ?d', USERS_TABLE, $this->hashPassword($new_pass), $this->_ID);
		if($this->_authed) $this->login($this->_data[LOGIN_FIELD], $new_pass);
		return true;
	}




	/**
	* rUser::_fetchUserData()
	* Fetching user data from database to _data array
	* @return bool true if all ok, or false on any error.
	*/
	function _fetchUserData()
	{
		
		
		if(!$this->_data)
		{
			$this->_resetState();
			return false;
		}

		@$this->_data['can'] = unserialize($this->_data['rights']);
		$this->_ID = @(int)$this->_data['id'];

		if(!$this->_ID) return false;
		
		
		
		if(!empty($this->_data['userpic'])){
			foreach($this->userpics as $key => $u){
				$this->_data['userpics'][$key] = $this->getURL($this->userpicFolder).$u['prefix'].$this->_data['userpic'];
			}
		}

		return true;
	}

	/**
	* Reloads user data from database
	* @return void
	*/
	function reloadData()
	{
		if(!$this->_ID) return false;
		$this->_data = $this->_db->selectRow($this->_selectString.' WHERE ?# = ?d',
			UID_FIELD, $this->_ID);
		$this->_fetchUserData();
	}

	/**
	* Возвращает IP пользователя
	* @return string IP of current user
	*/
    public function getIP(){
        $ip = empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? @$_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ip = explode(',',$ip);
        return trim($ip[0]);
    }

    /**
    * Возвращает IP пользователя в unsigned int формате. Подходит для MySQL функции INET_NTOA
    * @return int IP пользователя
    **/
    public function getIntIP(){
    	return sprintf("%u", ip2long($this->getIP()));
    }


	    

	/**
	* Select user by their login, and fill _data array if user found
	* @param mixed $login
	* @return mixed
	*/
	function getByLogin($login)
	{
		$this->_data = $this->_db->selectRow($this->_selectString.' WHERE ?# = ?',
			LOGIN_FIELD, $login);

		return $this->_fetchUserData();
	}

	/**
	* getByID()
	* @param mixed $id
	* @return mixed
	*/
	function getByID($id)
	{
		$this->_data = $this->_db->selectRow($this->_selectString.' WHERE u.?# = ?d',
			UID_FIELD, $id);

		return $this->_fetchUserData();
	}

	/**
	* getField()
	* @param mixed $field
	* @return mixed
	*/
	function getField($field){
		return @$this->_data[$field];
	}

	/**
	* setFields()
	* @param mixed $array
	* @return void
	*/
	function setFields($array)
	{
		$res = $this->_db->query('UPDATE ?# SET ?a WHERE id = ?', USERS_TABLE, $array, $this->_ID);
		$this->_data = $this->_data + $array;
	}

	/**
	* setField()
	* @param mixed $name
	* @param mixed $value
	* @return void
	*/
	function setField($name, $value)
	{
		$this->setFields(array($name=>$value));
	}

	/**
	* doHit()
	* @return void
	*/
	function doHit(){
		$this->_db->query('UPDATE ?# SET hits = hits + 1, lastpage = ?, ip = ? WHERE id = ?d',
			USERS_TABLE, @$_SERVER['REQUEST_URI'], $this->getIP(), $this->_ID);
	}

	/**
	* getInfo()
	* @return mixed
	*/
	public function getInfo(){
		if(!$this->_ID) return array();
		$info = $this->_db->selectRow('SELECT * FROM users_info WHERE id = ?d', $this->_ID);
		
		if(!$info){
			$this->_db->query('INSERT INTO users_info SET id = ?d', $this->_ID);
			$info = $this->_db->selectRow('SELECT * FROM users_info WHERE id = ?d', $this->_ID);
		}

		list($info['byear'], $info['bmonth'], $info['bday']) = explode('-', @$info['birthday']);


		return $info;		
	}
	
	/**
	* setInfo()
	* @param mixed $a
	* @return void
	*/
	function setInfo($a){
		if(!$this->_ID) return false;

		if(!empty($a['byear']) || !empty($a['bmonth']) || !empty($a['byear'])){
			$a['birthday'] = $a['byear'].'-'.$a['bmonth'].'-'.$a['byear'];
			unset($a['byear'], $a['bmonth'], $a['bday']);
		}

		$this->_db->query('UPDATE users_info SET ?a WHERE id = ?d', $a, $this->_ID);
	}	
	
	/**
	* Reset all object vars
	* @return void
	*/
	function _resetState()
	{
		$this->_data = array();
		$this->_authed = false;
		$this->_auth_checked = false;
		$this->_ID = 0;
	}

	/**
	* getUserDir() DEPRECATED
	* @param string $sub_dir
	* @return mixed
	*/
	function getUserDir($sub_dir = '')
	{
		return $this->getPath($sub_dir);

	}
	/**
	* getPath()
	* @param string $sub_dir
	* @return mixed
	*/
	function getPath($sub_dir = '')
	{
		/*if(!$this->_authed)
			return false;*/
		$dir = USERS_PATH.'/'.$this->_getUserDir() . '/' . $sub_dir;
		if(!is_dir($dir))
		{
			// try create user dir
			if(!is_writable(USERS_PATH))
			{
				mkdir(USERS_PATH, 0777, true);
				chmod(USERS_PATH, 0777);
				if(!is_writable(USERS_PATH))
					return false;
			}
			prepareDir($dir);
		}
		return realpath($dir);

	}

	/**
	* getUserUrl() DEPRECATED
	* @param string $sub_dir
	* @return mixed
	*/
	function getUserURL($sub_dir = '')
	{
		return $this->getURL($sub_dir);
	}
	/**
	* getURL()
	* @param string $sub_dir
	* @return mixed
	*/
	function getURL($sub_dir = '')
	{
		return rtrim(USERS_URL . $this->_getUserDir() . '/' . $sub_dir, '/ ?').'/';
	}
	/**
	* _getUserDir()
	* @return mixed
	*/
	function _getUserDir()
	{
		$dir = $this->_data['login'][0].'/';
		$dir .= $this->_data['login'];
		return $dir;
	}

	/**
	* getCookiePrefix()
	* @return mixed
	*/
	function getCookiePrefix(){
		return $this->_cookie_prefix;
	}
	
	/**
	* Добавляет внутреннего рейтинга (за комменты и прочие мелкие шняги)
	* @param int $rating количество баллов
	* @param bool $firstVote добавлять ли количество голосов
	* @return void
	*/
	public function addIntRating($rating, $firstVote){
		$this->_db->query('UPDATE ?# SET int_rating = int_rating + ?d{, int_rating_count = int_rating_count + ?d} WHERE id = ?d', 
		USERS_TABLE, $rating, $firstVote ? 1 : DBSIMPLE_SKIP, $this->_ID);
	}
	
	
	/**
	* Добавляет внутреннего рейтинга (за комменты и прочие мелкие шняги)
	* @param int $rating количество баллов
	* @param bool $firstVote добавлять ли количество голосов
	* @return void
	*/
	public function addRating($rating, $firstVote){
		$this->_db->query('UPDATE ?# SET rating = rating + ?d{, rating_count = rating_count + ?d} WHERE id = ?d', 
			USERS_TABLE, $rating, $firstVote ? 1 : DBSIMPLE_SKIP, $this->_ID);
	}
	
	/**
	* Добавляет SkillPoints
	* @param mixed $sP
	* @return bool
	*/
	public function addSkillPoints($sP){
		
		return false;
	}
	
	/**
	* Возвращает временный ID юзера, стараясь его для юзера запомнить навсегда. 
	* Используется для всяких голосований анонимных и магазинов - чтобы можно было хранить 
	* корзинку юзера привязав её к простому ID
	*
	* TODO: прикрутить evercookie для вообще навсегда-навсегда запоминания :)
	*/
	public function getMyTempID(){

		if(!empty($this->tempID)) return $this->tempID;

		if(empty($_COOKIE[$this->_cookie_prefix.'temp_id']) || !($tempID = (int)$_COOKIE[$this->_cookie_prefix.'temp_id'])){
			return $this->createTempID();
		}

		if(empty($_COOKIE[$this->_cookie_prefix.'temp_key']) || !($tempKey = trim($_COOKIE[$this->_cookie_prefix.'temp_key']))){
			return $this->createTempID();
		}

		$user = $this->_db->selectRow('SELECT * FROM users_temp WHERE id = ?d', $tempID);

		if(!$user || ($user['pass_key'] != $tempKey)) 
			return $this->createTempID();

		return $user['id'];

	}

	/**
	* Создает временного юзера
	* return mixed
	*/
	protected function createTempID(){
		$key = uniqid('', true);
		$id = $this->_db->query('INSERT INTO users_temp SET ?a', array(
			'pass_key' => $key,
			'dateadd' => time()
		));

		setcookie($this->_cookie_prefix.'temp_id', $id, time() + (60*60*24*300), $this->_cookie_path);
		setcookie($this->_cookie_prefix.'temp_key', $key, time() + (60*60*24*300), $this->_cookie_path);


		$this->tempID = $id;

		return $id;

	}	
	
	/**
	* uploadUserpic
	* @param mixed $pic
	* @return void
	*/
	public function uploadUserpic($pic){
		
		require_once('rlib/Imager.php');
		$imager = new Imager;
		
		if(!$pic || !file_exists($pic)) return false;
		
		if(!$imager->setImage($pic)){
			return false;
		}
		
		$dir = $this->getPath($this->userpicFolder);
		
		if(!$imager->prepareDir($dir)){
			return false;
		}
		
		$url = uniqid('');
		
		
		if(!$base_name = $imager->packetResize($dir, $this->userpics, $url)){
			return false;
		}
		
		@unlink($pic);
		
		if(!empty($this->_data['userpic'])){
			// удаляем предыдущий
			// а пока не удаляем, малоличо
		}
		
		$this->setField('userpic', $base_name);

		
	}

	/**
	* Генерирует случайную соль.
	* @return string строка со случайной солью
	*/
	public function getRandSalt(){
		return substr(uniqid('').rand(1,100), -10);
	}
	
	
	
 
}

/************* </rUser> ***************************/
/**
* prepareDir
* @param mixed $dir
* @return bool
*/
function prepareDir($dir)
{
	$dir = rtrim($dir, "/\\");
	if (!is_dir($dir)) {
		if (!prepareDir(dirname($dir)))
			return false;
		if (@!mkdir($dir, 0777))
			return false;
		chmod($dir, 0777);
	}
	return true;
}