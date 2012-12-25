<?php

if(!defined('SITE_CODEPAGE'))
	define('SITE_CODEPAGE', 'utf-8');
if(!defined('LC_SITE_LOCALE')){
	define('LC_SITE_LOCALE', 'ru_RU.UTF-8');
}
if(!defined('STATIC_JS_URL'))
	define('STATIC_JS_URL', ROOT_URL.'js/');
if(!defined('STATIC_VERSION'))
	define('STATIC_VERSION', 1);

if(!defined('FAVICON_FILE_TYPE')) 
	define('FAVICON_FILE_TYPE', false);

// setlocale(LC_ALL, LC_SITE_LOCALE);  // рушит яваскрипт (4.55 становится 4,55)
if(function_exists('mb_internal_encoding')) mb_internal_encoding(SITE_CODEPAGE);

require_once 'rlib/settings.class.php';
require_once 'rlib/rURLs.class.php';
require_once 'rlib/rException.class.php';
require_once 'rlib/webstats.class.php';

abstract class rSite
{
	/** TPL object */
	var $tpl = null;

	/** User object */
	var $user = null;

	/** Simple DB object */
	var $db = null;
	var $app = null;

	var $lang = null;

	public $rURL = null;

	var $curContainer = 'index.tpl';
	var $curTemplate = 'main.tpl';

	var $curSection = 'main';

	var $stdTemplatesFolder = TEMPLATES_PATH;

	var $jsFiles = array();
	var $cssFiles = array();
	var $metaLinks = array();
	private $navPath = array();
	private $navSeparator = ' | ';

	private $pathInfo = array();

	public $settings = null;

	public $stats = null;
	
	protected $langsAvaiable = array('ru', 'en', 'ua', 'es', 'fr', 'de', 'it');
	protected $curLang = 'ru';
	
	protected $notFoundTemplate = '404.tpl';


	/**
		Конструктор
	**/
	function __construct(rApplication $app){
		if(!headers_sent())
			@header("Content-Type: text/html; charset=".SITE_CODEPAGE);

		if(defined('DEF_LANG')) $this->curLang = strtolower(DEF_LANG);

		$this->db = $app->db;
		$this->tpl = $app->tpl;
		$this->user = $app->user;
		$this->lang = new rLang(LANG_PATH, $this->tpl);

		$this->rURL = $app->url;
		$this->app = $app;


		$this->stats = new webStats($app->db);

		$this->setTemplate($this->curTemplate);
	}
	
	/**
	* Устанавливает папку шаблонов
	* @param mixed $folder
	* @return void
	*/
	public function setTemplatesFolder($folder){
		$this->stdTemplatesFolder = $folder;
	}
	
	/**
	* Запуск настроек модуля
	* @param string $folder
	* @return void
	*/
	public function initSettings(){
		
	}


	


	/**
	* Выводит дамп переменной
	* @param mixed $var Переменная для дампа
	* @param bool $exit Выйти по завершению дампа
	* @return void
	*/
	function dump($var, $exit = false){
		if($exit){
			$this->assign('var', print_r($var,1));
			$this->render(null, 'dump.tpl');
		}else{
			echo "<pre>";
				print_r($var);
			echo "</pre>";
		}
	}

	/**
	* Устанавливает папку по умолчанию для темплейтов
	* @param mixed $folder папка
	* @return bool true если папка установлена успешно. иначе false
	*/
	function setStdTemplatesFolder($folder){
		if(substr($folder, 0, 1)!= '/')
			$folder = TEMPLATES_PATH . '/' . $folder;
		$folder = realpath($folder);
		if(!$folder) return false;
		$this->stdTemplatesFolder = $folder;
		return true;
	}
	
	/**
	* Устанавливает контейнер (родительский шаблон)
	* @param mixed $container
	* @return void
	*/
	function setContainer($container){
		$this->curContainer = $container;
	}
	

	
	/**
	* устанавливает внутрений темплейт
	* @param mixed $template
	* @return void
	*/
	public function setTemplate($template){		
		$this->curTemplate = $template; 
		$this->assign('template', $this->curTemplate); 
	}

	/**
	* Проверяет, есть ли шаблон с заданным именем (расширение .tpl добавляется автоматически при надобности).
	* Если есть - устанавливает его и возвращает TRUE. Иначе просто возвращает FALSE
	* @param mixed $template
	* @return bool
	*/
	function checkTemplate($template)
	{
		if(!preg_match('/\.tpl$/', $template)) $template .= '.tpl';
		if(file_exists(TEMPLATES_PATH.'/'.$template))
		{
			$this->setTemplate($template);
			return true;
		}
		return false;
	}

	/**
	* setSection
	* @param mixed $section
	* @return void
	*/
	function setSection($section){
		$this->curSection = $section;
		if(!$this->checkTemplate($section)){
			if(!$this->checkTemplate('section/'.$section))
				$this->setTemplate('404.tpl');
		}
	}

	/**
	* rSite::assign() Добавляет в шаблонизатор переменную
	* @param string $var Имя переменной
	* @param mixed $value Значение переменной
	* @return void
	*/
	function assign($var, $value){
		$this->app->tpl->assign($var, $value);
	}

	





	/******* Renderers ********/

	/**
	* Render page
	* @param string $template
	* @param string $base
	* @return void
	*/
	function render($template = '', $container = ''){
		$this->app->render($template, $container);
	}
	
	

	/**
	* assignDefaults
	* @return void
	*/
	function assignDefaults(){

	}
	
	/**
	* notFound (страница не найдена)
	* @param bool $template
	* @return void
	*/
	public function notFound($template = false){
		if(!$template)
			$template = $this->notFoundTemplate;
		header("HTTP/1.0 404 Not Found");
		header("HTTP/1.1 404 Not Found");
		header("Status: 404 Not Found");
		
		$this->render($template);
	}

	/**
	* Render error
	* @param mixed $title
	* @param string $message
	* @return void
	*/
	function renderError($title, $message = '')
	{
		if(!$message){
			$message = $title;
			$title = '';
		}

		$this->assign('title', $title);
		$this->assign('message', $message);

		$this->addCSS('error.css');

		$this->render(TEMPLATES_PATH.'/stdError.tpl');
	}

	
	


	public abstract function Run();

}