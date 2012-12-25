<?php


/**
	Приложение.
	По сути просто содержит набор всех объектов, чтобы удобно передавать в модули
**/

define('APPMSG_NOTICE', 0);
define('APPMSG_OK', 1);
define('APPMSG_ERROR', -1);
define('APPMSG_USER_NOTICE', 10);
define('APPMSG_USER_OK', 100);
define('APPMSG_USER_ERROR', -100);

abstract class rApplication{
	public $db;
	public $user;
	public $tpl;
	public $url;
	public $lang;

	protected $settings = NULL; // массив с кешем настроек

	/** сообщения **/
	protected $messages = array();
	protected $messagesGlue = ', ';


	public function __construct(){
		$this->initDB();
		$this->initUser();
		$this->initTPL();
		$this->initURL();
		$this->initLang();
	}

	protected function initDB(){
		require_once "rlib/rDBSimple.php";
		$this->db = rDBSimple::connect('mysql://'.DB_USER.':'.DB_PASS.'@'.DB_HOST.'/'.DB_NAME);
		$this->db->setErrorHandler('stdDBErrorHandler');
		if(defined('DB_SET_NAMES') && DB_SET_NAMES)
			$this->db->query('SET NAMES '.DB_SET_NAMES);

		if(function_exists('cache_Memcache'))
			$this->db->setCacher('cache_Memcache');
	}

	protected function initUser(){
		$this->user = new rMyUser($this->db);
	}

	protected function initTPL(){
		$this->tpl = new rMyTPL;
	}

	protected function initURL(){
		require_once 'rlib/rURLs.class.php';
		$this->url = new rURLs;
	}

	protected function initLang(){
		require_once('rlib/rLang.class.php');
		$this->lang = new rLang(LANG_PATH, $this->tpl);
	}

	/**
		Работа с сообщениями
	**/
	public function addMessage($message, $level = APPMSG_NOTICE){
		$this->messages[$level][] = $message;
	}


	public function getMessages($level){
		if(empty($this->messages[$level])) return '';
		return implode($this->messagesGlue, $this->messages[$level]);
	}

	/**
		Работа с языковой частью
	**/
	/**
	* Добавить язык
	* @param mixed $file
	* @return void
	*/
	public function addLangFile($file){
		$this->lang->addLang($file);
	}
	
	/**
	* getLang
	* @param mixed $str
	* @return mixed
	*/
	public function getLang($str){
		return $this->lang->__get($str);
	}

	/**
		Работа с настройками SETTINGS
	**/
	/**
	* Запуск настроек
	* @param string $folder
	* @return void
	*/
	public function initSettings($table = 'site_settings'){
		if($this->settings == null)
			$this->settings = new siteSettings($table, $this->db);

		$this->settings->loadAll();

		$this->setTitle($this->getSetting('default_title', '', false));
		$this->setKeywords($this->getSetting('default_keywords', '', false));
		$this->setDescription($this->getSetting('default_descr', '', false));		
		
	}
	
	/**
	* Считывание настроек
	* @param mixed $key
	* @param string $default
	* @param bool $autoCreate
	* @return mixed
	*/
	public function getSetting($key, $default = '', $autoCreate = false){
		if($this->settings === NULL)
			$this->initSettings();
		return $this->settings->getValue($key, $default, $autoCreate);
	}

	/**
		Всякое вспомогательное
	**/

	/**
	* rApplication::assign() Добавляет в шаблонизатор переменную
	* @param string $var Имя переменной
	* @param mixed $value Значение переменной
	* @return void
	*/
	public function assign($var, $value){
		$this->app->tpl->assign($var, $value);
	}	

	public function assignDefaults(){
		
	}	

}