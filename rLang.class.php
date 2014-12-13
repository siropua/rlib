<?php

@define('AUTOLANG_FILE', 'main.ini');

if(!class_exists('Config_File'))
	require_once('rlib/quicky/Config_File.class.php');


if (!defined('DEF_LANG'))
	define('DEF_LANG', 'ru');

class rLang{
	/** @var $_lang array Storage for langs */
	var $_lang = array();
	/** @var $_langReader object Smarty ini reader */
	var $_langReader = null;

	/** @var $_langFiles array list of lang files */
	var $_langFiles = array();

	var $_langPath;

	var $smarty = null;

	protected $curLang = DEF_LANG;
	
	protected $baseFiles = array('all.ini');
	protected $db = NULL;
	
	/**
	 * Constructor
	 * @access protected
	 */
	function __construct($lang_dir, $smarty = null, $db = null)
	{
		$this->_langPath = rtrim($lang_dir, '\ /').'/';
		$this->_langReader = new Config_File();
        if(AUTOLANG_FILE)
			$this->addLang(AUTOLANG_FILE);
		$this->db = $db;
	}

	public function setDB($db)
	{
		$this->db = $db;
	}

	public function loadIDs()
	{
		$this->IDs = @$this->db->selectCol('SELECT code AS ARRAY_KEY, id FROM langs');
	}

	public function getID($langName)
	{
		return empty($this->IDs[$langName]) ? false : $this->IDs[$langName];
	}
	
	
	public function getIDs()
	{
	    return $this->IDs;
	}
	
	public function getSysLangs()
	{
	    return $this->db->select('SELECT *, code AS ARRAY_KEY FROM langs');
	}


	/**
	 * Adding language file to language pool
	 *
	 * @param string $file Path to file name
	 * @param string $section Section in INI file
	 * @return bool Boolean result
	 **/
	function addLang($file, $section=''){
		if(!strpos($file, '.')){
			// need to find extension
			if(file_exists($this->_langPath.$file.'.txt'))
				$file .= '.txt';
			elseif(file_exists($this->_langPath.$file.'.ini'))
				$file .= '.ini';
		}

		if(!file_exists($this->_langPath.$file)) return false;
		
		$this->_lang = $this->_lang + $this->_langReader->get($this->_langPath.$file, $section);
		if(@$this->smarty)
			$this->smarty->assign('secondary_lang', basename($file));
		$this->_langFiles[] = $this->_langPath.$file;
		$this->baseFiles[] = $file;
	}
	
	public function selectLang($lang){
	    $this->_lang = array();
	    $baseFiles = $this->baseFiles;
	    $this->baseFiles = $this->_langFiles = array();
	    $this->_langPath = LANG_PATH .'/'. $lang.'/';
	    foreach($baseFiles as $file) $this->addLang($file);
	    $this->curLang = $lang;
	}


	function getLangFiles()
	{
		return $this->_langFiles;
	}

	function __get($lang)
	{
		$idx = strtolower($lang);
		if(!array_key_exists($idx, $this->_lang))
		{
			$lang = str_replace('_', ' ', $lang);
			return $lang;
		}else
		{
			return $this->_lang[$idx];
		}
	}


	public function getCurLang(){
		return $this->curLang;
	}
	
	public function getCurID(){
	    return $this->getID($this->getCurLang());
	}
	
	public function getEditLang(){
		return !empty($_GET['lang']) ? $_GET['lang'] : $this->curLang;
	}
	
	public function getEditID(){
	    return $this->getID($this->getEditLang());
	}
	
	public function setCurLang($langStr){
	    $this->curLang = $langStr;
	}
	
	public function setEditLang($langStr){
	    $_GET['lang'] = $langStr;
	}
	
	
}