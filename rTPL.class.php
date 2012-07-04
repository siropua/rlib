<?php

require_once('rlib/quicky/Quicky.class.php');
class rTPL extends Quicky{
	public $timer;

	function __construct(){
		$this->caching 	        = false;
		$this->template_dir     = TEMPLATES_PATH.'/';							
		$this->compile_dir	    = COMPILED_PATH.'/';	
		$this->config_dir       = LANG_PATH.'/'.DEF_LANG;			
		$this->cache_dir        = CACHE_PATH;		
		$this->config_booleanize= false;

		$this->cache_modified_check = true;

		$this->startTiming();
		
		//$this->lang = DEF_LANG;
		
		parent::__construct();
	}
	
	function display($template,$cacheid = NULL,$compileid = NULL, $parent = NULL){
		$this->assignDefaults();
		parent::display($template,$cacheid);
	}


	function preFetch($template,$cacheid = NULL){
		$this->assignDefaults();
		return parent::fetch($template,$cacheid);
	}

	function assignDefaults(){
		$this->assign("CACHING", $this->caching);
		$this->assign("DESIGN", DESIGN);
		$this->assign("IMG", IMAGES_URL);
		$this->assign("SERVER_ABSOLUTE", SERVER_URL);
		$this->assign("SERVER_ROOT", ROOT_URL);
		$this->assign("ROOT", ROOT_URL);
		$this->assign("CODETIME", $this->stopTiming());
		$this->assign("SELF", SELF_URL);
		$this->assign("USERS_URL", USERS_URL);
		$this->assign("STATIC", STATIC_URL);
	}


	function startTiming(){
		$microtime = microtime();
		$microsecs = substr($microtime, 2, 8);
		$secs = substr($microtime, 11);
		$this->timer = "$secs.$microsecs";
	}


	function stopTiming(){
		$microtime = microtime();
		$microsecs = substr($microtime, 2, 8);
		$secs = substr($microtime, 11);
		$endTime = "$secs.$microsecs";
		return round(($endTime - $this->timer),4);
	}
}
