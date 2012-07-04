<?php

/**
 *	Users Class
 *
 * @version $Id: rUser.class.php,v 1.12 2008/05/21 09:44:33 steel Exp $
 * @copyright 2007
 */


@define('USERS_TABLE', 'users');
@define('UID_FIELD', 'id');
@define('LOGIN_FIELD', 'login');
@define('PASS_FIELD', 'password');
@define('PASSWORD_HASH_METHOD', 'md5');
@define('LOGIN_PREG', '/^[a-z][a-z0-9\-_]+$/i');

if(!defined('EMAIL_FIELD'))
	define('EMAIL_FIELD', 'email');
if(!defined('EMAIL_PREG'))
	define('EMAIL_PREG', '/^[a-z0-9_.\-]+@[a-z0-9_.\-]+\.[a-z0-9]{2,4}$/i');


@define('DELETED_FLAG', 'deleted');

/**
* login results
*/


@define('LOGIN_OK', 1);
@define('LOGIN_ERR_LOGIN', 0);
@define('LOGIN_NO_USER', -1);
@define('LOGIN_PASS_ERR', -2);


@define('USERS_PATH', ROOT . '/users_data');
@define('USERS_URL', ROOT_URL . 'users_data/');

@define("RIGHTS_DELIM", "|");
@define("DENY_ALL_RIGHT", "deny_all");
@define("ACCEPT_ALL_RIGHT", "allow_all");



/**
 *
 *
 **/
class rUser{

	protected $_db = null;
	protected $_cookie_prefix = '';
	protected $_authed = false;
	protected $_auth_checked = false;
	protected $_ID = 0;
	protected $_data = array();
	protected $_cookie_domain = '';
	protected $_cookie_path = '/';
	protected $_can = array();
	protected $_lastAuthError = '';

	protected $_selectString = 'SELECT * FROM users AS u ';
	
	protected $userpics = array(
		'', '100_', '50_'
	);


	/**
	 * Constructor
	 *
	 * @param object $db_link Link to PEAR database object
	 * @param string $cookie_prefix Prefix for cookies
	 * @param mixed $autoauth if true, authenticate user after object creation
	 **/
	public function __construct($db_link, $cookie_prefix = 'user_', $autoauth = true){
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
	 *
	 * @return bool
	 **/
	public function auth(){
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
		if($this->_data['password'] != $hash)
		{
			$this->_lastAuthError = 'Hash '.$this->_data['password'].' do not match'.$hash;
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
	 *
	 * @param string $login
	 * @param string $password
	 * @param integer $save_time
	 * @return int see LOGIN_* defines to determine login result
	 **/
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

		$hashedPass = $this->hashPassword($password);

		if($hashedPass != $this->_data[PASS_FIELD])
			return LOGIN_PASS_ERR;

		if(DELETED_FLAG && @$this->_data[DELETED_FLAG])
			return LOGIN_NO_USER;

		$this->_ID = (int)$this->_data['id'];

		$_COOKIE[$this->_cookie_prefix.'uid'] =
			$_SESSION[$this->_cookie_prefix.'uid'] = $this->_data['id'];
		$_COOKIE[$this->_cookie_prefix.'hash'] =
			$_SESSION[$this->_cookie_prefix.'hash'] = $this->_data[PASS_FIELD];


			setcookie($this->_cookie_prefix.'uid', $this->_data['id'], $save_time, $this->_cookie_path);
			setcookie($this->_cookie_prefix.'hash', $this->_data[PASS_FIELD], $save_time, $this->_cookie_path);
		// }

		$this->_db->query('UPDATE ?# SET ip = ?, last_login = ? WHERE id = ?',
			USERS_TABLE, $this->getIP(), time(), $this->_ID);

		$this->auth();

		return LOGIN_OK;
	}

	function hashPassword($password){
		switch(PASSWORD_HASH_METHOD){
			case 'md5x2':
				return md5(md5($password));
			case 'md5sha1':
				return sha1(md5($password));
			break;
		}
		return md5($password);
	}
	
	function setPassword($password, $forceLogin = true){
		$p = $this->hashPassword($password);
		$l = $this->authed();
		$this->setField(PASS_FIELD, $p);
		if($l && $forceLogin) $this->login($this->_data[LOGIN_FIELD], $password);
	}

	/**
	 * Clear session and cookie
	 *
	 * @return
	 **/
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
	 *
	 * @param bool $force_reauth if true, user will be reauthed
	 * @return bool authed or not =)
	 **/
	function authed($force_reauth = false)
	{
		if(!$this->_auth_checked || $force_reauth)
				$this->auth();
		return $this->_authed;
	}

	/** in php5 we can use $user->field_name for read any user fields */
	function __get($field)
	{
		/* if(!$this->authed())
			return null; */
		return isset($this->_data[$field]) ? $this->_data[$field] : '';
	}

	function can($action)
	{
		if(!$this->authed())
			return false;
		return (@$this->_can[$action] || @$this->_can[ACCEPT_ALL_RIGHT]) && (!@$this->_can[DENY_ALL_RIGHT]);
	}

	function getID()
	{
		return $this->_ID;
	}

	/**
	 * rUser::getData()
	 *
	 * @return array just returns _data[]. Empty array returns, when user not authed
	 **/
	function getData($key = null)
	{
		return  $key == null ? $this->_data : $this->_data[$key];
	}

	function changePassword($new_pass){
		$this->_db->query('UPDATE ?# SET password = ? WHERE id = ?d', USERS_TABLE, $this->hashPassword($new_pass), $this->_ID);
		if($this->_authed) $this->login($this->_data[LOGIN_FIELD], $new_pass);
		return true;
	}




	/**
	 * rUser::_fetchUserData()
	 * Fetching user data from database to _data array
	 *
	 * @return bool true if all ok, or false on any error.
	 **/
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

		return true;
	}

	/**
	* Reloads user data from database
	*
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
     *
     * @return string IP of current user
     **/
    function getIP()
    {
        global $_SERVER;
        if(@$_SERVER['HTTP_X_FORWARDED_FOR'])
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['REMOTE_ADDR'];
    }

	/**
	 * Select user by their login, and fill _data array if user found
	 *
	 * @param mixed $login
	 * @return
	 **/
	function getByLogin($login)
	{
		$this->_data = $this->_db->selectRow($this->_selectString.' WHERE ?# = ?',
			LOGIN_FIELD, $login);

		return $this->_fetchUserData();
	}

	function getByID($id)
	{
		$this->_data = $this->_db->selectRow($this->_selectString.' WHERE u.?# = ?d',
			UID_FIELD, $id);

		return $this->_fetchUserData();
	}

	function getField($field){
		return @$this->_data[$field];
	}

	function setFields($array)
	{
		$res = $this->_db->query('UPDATE ?# SET ?a WHERE id = ?', USERS_TABLE, $array, $this->_ID);
		$this->_data = $this->_data + $array;
	}

	function setField($name, $value)
	{
		$this->setFields(array($name=>$value));
	}

	function doHit(){
		$this->_db->query('UPDATE users 
			SET last_online = ?d, lastpage = ?{, hits = hits + ?d} 
			WHERE id = ?d', 
				time(),	
				$_SERVER['REQUEST_URI'],
				($this->lastpage == $_SERVER['REQUEST_URI'] ? DBSIMPLE_SKIP : 1),
				$this->_ID
		);
	}

	
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
	 *
	 * @return
	 **/
	function _resetState()
	{
		$this->_data = array();
		$this->_authed = false;
		$this->_auth_checked = false;
		$this->_ID = 0;
	}

	function getUserDir($sub_dir = '')
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

	function getUserURL($sub_dir = '')
	{
		return rtrim(USERS_URL . $this->_getUserDir() . '/' . $sub_dir, '/ ?').'/';
	}



	function _getUserDir()
	{
		$dir = $this->_data['login'][0].'/';
		$dir .= $this->_data['login'];
		return $dir;
	}

	function getCookiePrefix(){
		return $this->_cookie_prefix;
	}
	
	/**
	* Добавляет внутреннего рейтинга (за комменты и прочие мелкие шняги)
	* @param int $rating количество баллов
	* @param bool $firstVote добавлять ли количество голосов
	* @return void
	**/
	public function addIntRating($rating, $firstVote){
		$this->_db->query('UPDATE ?# SET int_rating = int_rating + ?d{, int_rating_count = int_rating_count + ?d} WHERE id = ?d', 
		USERS_TABLE, $rating, $firstVote ? 1 : DBSIMPLE_SKIP, $this->_ID);
	}
	
	
	/**
	* Добавляет внутреннего рейтинга (за комменты и прочие мелкие шняги)
	* @param int $rating количество баллов
	* @param bool $firstVote добавлять ли количество голосов
	* @return void
	**/
	public function addRating($rating, $firstVote){
		$this->_db->query('UPDATE ?# SET rating = rating + ?d{, rating_count = rating_count + ?d} WHERE id = ?d', 
			USERS_TABLE, $rating, $firstVote ? 1 : DBSIMPLE_SKIP, $this->_ID);
	}
	
	
	public function incStatField($field){
		$r = $this->_db->query('UPDATE user_stats SET ?# = ?# + 1 WHERE id = ?d', 
			$field, $field, $this->getID());
		
		if(!$r){
			$this->_db->query('INSERT INTO user_stats SET ?a', array(
				'id' => $this->getID(),
				$field => 1
			));
		}
	}
	
 
}

/************* </rUser> ***************************/

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

?>