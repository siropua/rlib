<?php
if(!defined('MYSQL_NAMES'))
	define('MYSQL_NAMES', 'utf8');
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
require_once('webstats.class.php');

abstract class rSite
{
	/** TPL object */
	var $tpl = null;

	/** User object */
	var $user = null;

	/** Simple DB object */
	var $db = null;

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
	function __construct($db){
		if(!headers_sent())
			@header("Content-Type: text/html; charset=".SITE_CODEPAGE);

		if(defined('DEF_LANG')) $this->curLang = strtolower(DEF_LANG);

		$this->db = $db;
		$this->db->query('SET NAMES '.MYSQL_NAMES);
		$this->tpl = new myTPL;
		$this->user = new myUser($db);
		$this->lang = new rLang(LANG_PATH, $this->tpl);

		$this->rURL = new rURLs;
		$this->rURL->setMaxLen(255);

		$this->parseURI();

		$this->stats = new webStats($db);

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
	* Запуск настроек
	* @param string $folder
	* @return void
	*/
	function initSettings($table = 'site_settings'){
		if($this->settings == null)
			$this->settings = new siteSettings($table, $this->db);

		$this->settings->loadAll();

		$this->setTitle($this->settings->getValue('default_title', '', false));
		$this->setKeywords($this->settings->getValue('default_keywords', '', false));
		$this->setDescription($this->settings->getValue('default_descr', '', false));
		
		if($_mainMenu = $this->settings->getValue('main_menu', '', false)){
			$_mainMenu = explode("\n", $_mainMenu);
			foreach($_mainMenu as $n=>$v) {
				$_mainMenu[$n] = explode('||', trim($v));
				$_mainMenu[$n]['class'] = empty($_mainMenu[$n][2]) ? '' : $_mainMenu[$n][2];
				if(empty($_mainMenu[$n][1])) $_mainMenu[$n]['class'] .= ' noLink';
				elseif(trim($_mainMenu[$n][1], '/') == $this->path(1)) $_mainMenu[$n]['class'] .= ' cuMenuItem';
				$_mainMenu[$n]['class'] = trim($_mainMenu[$n]['class']);
			}
			$this->assign('_mainMenu', $_mainMenu);
		}

	}
	
	/**
	* Считывание настроек
	* @param mixed $key
	* @param string $default
	* @param bool $autoCreate
	* @return mixed
	*/
	function getSetting($key, $default = '', $autoCreate = false){
		if(!$this->settings)
			$this->initSettings();
		return $this->settings->getValue($key, $default, $autoCreate);
	}

	/**
	* Парсим урл статически
	* @return void
	**/
	public static function getURLPart($partN = 1, $URL = NULL){
		if(!$URL){
			if(defined('SELF_URL_FULL')) 
				$URL = SELF_URL_FULL; 
			else 
				return false;
		}

		$urlPath = parse_url($URL);
		$urlPath = explode('/', $urlPath['path']);
		if(empty($urlPath[$partN])) 
			return false;
		else
			return $urlPath[$partN];
	}

	/**
	* Парсим REQUEST_URI на предмет адреса, части адреса и GET-запроса.
	* @return void
	*/
	protected function parseURI(){
		$p = explode('?', $_SERVER['REQUEST_URI'], 2);
		$p[0] = '/'.substr($p[0], strlen(ROOT_URL));
		$this->pathInfo['path'] = $p[0];
		
		$this->pathInfo['q'] = empty($p[1]) ? false : $p[1];
		$p[0] = preg_replace('~^/(.*)(/|(\.html|\.xml|\.htm))?$~iU', '$1', $p[0]);
		$p = explode('/', $p[0]);
		foreach($p as $n=>$v){
			$this->pathInfo['part'][$n+1] = $this->rURL->URLize($v, true);
		}
	}
	
	/**
	* Устанавливает текущий язык сайта
	* @param mixed $lang
	* @return bool
	*/
	public function setCurLang($lang){
		$lang = trim($lang);
		if($lang == $this->curLang) return true;
		if(!in_array($lang, $this->langsAvaiable)) return false;
		$this->curLang = $lang;
		$this->lang->selectLang($lang);
		return true;
	}
	
	/**
	* Определение текущего языка сайта
	* @return mixed
	*/
	public function getCurLang(){
		$this->path(1);
		return $this->curLang;
	}
	
	/**
	* Определение ссылки языка
	* @param mixed $lang
	* @param bool $link
	* @return mixed
	*/
	public function getLangLink($lang, $link = false){
		if(!$link)
			$link = $_SERVER['REQUEST_URI'];
		
		// если в адресе уже есть признак языка
		if(!empty($this->pathInfo['part'][1]) && $this->setCurLang($this->pathInfo['part'][1])){
			return preg_replace('~^/[a-z]{2}/~i', '/'.$lang.'/', $link);
		}
		
		return '/'.$lang.$link;
	}

	/**
	* Возвращает часть адреса
	* @param integer $part номер части адреса. Начинается с 1. Если 0 - отдаётся весь адрес. Если части адреса не существует, отдаётся false.
	* @return string
	*/
	public function path($part = 0){
		$part = (int)$part;
		if(!$part) return $this->pathInfo['path'];
		
		if(!empty($this->pathInfo['part'][1]) && $this->setCurLang($this->pathInfo['part'][1])){
			$part++;
		}
		
		if(!isset($this->pathInfo['part'][$part])) return false;
		return $this->pathInfo['part'][$part];
	}

	/**
	* Определяет тип части адреса. Может быть папкой или файлом
	* @param int $part
	* @return string file или folder. Если не существует - NULL
	*/
	public function pathType($part){
	
		if(!empty($this->pathInfo['part'][1]) && $this->setCurLang($this->pathInfo['part'][1])){
			$part++;
		}
	
	
		// не является последним параметром? папка!
		if($part < count($this->pathInfo['part'])){
			return 'folder';
		}

		// вообще не существуеты
		if($part > count($this->pathInfo['part']))
			return NULL;

		// заканчивается «/»? папка!
		if(substr($this->pathInfo['path'], -1) == '/')
			return 'folder';

		// имеет расширение? файл!
		if(preg_match('~\.[a-z0-9]{2,4}$~i', $this->pathInfo['path']))
			return 'file';

		// папка
		return 'folder';
	}

	/**
	* pathCount
	* @return mixed
	*/
	public function pathCount(){
		$c = count($this->pathInfo['part']);
		if(!empty($this->pathInfo['part'][1]) && $this->setCurLang($this->pathInfo['part'][1])){
			$c--;
		}
		return $c;
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
		$this->tpl->assign($var, $value);
	}

	/**
	* Добавить язык
	* @param mixed $file
	* @return void
	*/
	function addLang($file){
		$this->lang->addLang($file);
	}
	
	/**
	* getLang
	* @param mixed $str
	* @return mixed
	*/
	function getLang($str){
		return $this->lang->__get($str);
	}
	
	/**
	* login
	* @param mixed $l
	* @param mixed $p
	* @param integer $s
	* @return mixed
	*/
	function login($l, $p, $s = 0){
		$this->lang->addLang('enter.txt');
		if($s && ($s < 2)) $s = time() + 15552000; // half year
		switch($this->user->login($l, $p, $s))
		{
			case LOGIN_OK:

				return true;

			case LOGIN_NO_USER:
				return $this->lang->No_user_with_this_login;
			case LOGIN_ERR_LOGIN:
				return $this->lang->Login_error;
			case LOGIN_BLOCKED_USER:
				return $this->lang->You_are_blocked;

			case LOGIN_PASS_ERR:
				return $this->lang->Password_incorrect;
		}
		return $this->lang->Unknown_error;
	}

	/**
	* Установить Title
	* @param mixed $title
	* @return void
	*/
	function setTitle($title){
		if(defined('DEFAULT_TITLE_POSTFIX') && DEFAULT_TITLE_POSTFIX){
			$t = $this->getSetting('default_title');
			if($t != $title)
				$title .= ' '.$t;
		}
		$this->assign('page_title', $title); 
	}
	
	/**
	* Установить Keywords
	* @param mixed $k
	* @return void
	*/
	function setKeywords($k){
		$this->assign('page_kws', $k);
	}
	
	/**
	* Установить Description
	* @param mixed $d
	* @return void
	*/
	function setDescription($d){
		$this->assign('page_descr', $d);
	}
	
	/**
	* logout
	* @return void
	*/
	function logout(){
		$this->user->logout();
	}

	/**
	* Добавить CSS
	* @param mixed $file
	* @return void
	*/
	function addCSS($file){
		if(!array_search($file, $this->cssFiles))$this->cssFiles[] = $this->uniPath($file, DESIGN);
	}
	
	/**
	* Добавляет CSS файл только если он существует
	* @param mixed $file
	* @return void
	*/
	public function addCSSIfExists($file){
		if(file_exists(TEMPLATES_PATH.'/'.$file))
			$this->addCSS($file);
	}
	
	/**
	* Добавляет JS файл только если он существует
	* @param mixed $file
	* @return void
	*/
	public function addJSIfExists($file){
		if(file_exists(ROOT.'/js/'.$file))
			$this->addJS($file);
	}
	
	/**
	* getCSSFiles
	* @return mixed
	*/
	function getCSSFiles(){
		return $this->cssFiles;
	}
	
	/**
	* Добавить JS файл
	* @param mixed $file
	* @return void
	*/
	function addJS($file){
		if(!array_search($file, $this->jsFiles))$this->jsFiles[] = $this->uniPath($file, STATIC_JS_URL);
	}

	/**
	* getJSFiles
	* @return mixed
	*/
	function getJSFiles(){
		return $this->jsFiles;
	}

	/**
	* uniPath
	* @param mixed $path
	* @param mixed $base
	* @return mixed
	*/
	public function uniPath($path, $base){
		if(substr($path, 0, 1) == '/') return $path;
		if(strtolower(substr($path, 0, 7)) == 'http://' || strtolower(substr($path, 0, 8)) == 'https://') return $path;
		return $base.$path;
	}

	/**
	* Добавляет MetaLink
	* @param mixed $data
	* @return void
	*/
	public function addMetaLink($data){
		$this->metaLinks[] = $data;
	}

	/**
	* initDatePicker
	* Инициализирует (просто добавляет скрипты) календарика
	* @return void
	*/
	function initDatePicker()
	{
		$this->addJS('date.js');
		$this->addJS('jquery.datePicker.js');
		$this->addCSS('datePicker.css');
	}
	
	/**
	* initTinyMCE
	* Добавляет скрипты редактора
	* @param string $initFile
	* @return void
	*/
	function initTinyMCE($initFile = 'init_mce')
	{
		$this->addJS('mce/tiny_mce_gzip.js?v='.STATIC_VERSION);
		$this->addJS($initFile.'_gz.js?v='.STATIC_VERSION);
		$this->addJS($initFile.'.js?v='.STATIC_VERSION);
	}
	
	/**
	* инициируем библиотеку сортировщика таблиц
	* Таблица должна иметь класс tablesorter
	* @param string $js
	* @param string $style
	* @return void
	*/
	public function initTableSorter($js = 'init-tablesorter.js', $style = 'ts-blue'){
		$this->addJS('jquery.tablesorter.min.js');
		$this->addJS('jquery.metadata.js');
		$this->addJS($js);
		$this->addCSS($style.'/style.css');
	}	
	
	/**
	* assignSession
	* Добавляет переменную в сессию, при следующей инициализации сайта эта переменная будет добавлена в шаблонизатор
	* @param mixed $var
	* @param mixed $value
	* @return void
	*/
	function assignSession($var, $value){
		@session_start();
		$_SESSION['saved_vars'][$var] = $value;
		session_write_close();
	}

	/**
	* Send redirection header and exit.
	* @param mixed $url
	* @param mixed $base
	* @return void
	*/
	function redirect($url, $base = SELF_URL){
        header('Location: '.$this->getAbsoluteURL($url, $base));
		exit;
	}
	
	/**
	* Получить Абсолютный URL
	* @param mixed $url
	* @param mixed $base
	* @return mixed
	*/
	function getAbsoluteURL($url, $base = SELF_URL){
		if(preg_match('/^http[s]?:\/\//', $url))
			return $url;
        
		if(!$base)
			$base = SELF_URL;

		if(!preg_match('/^http[s]?:\/\//', $base))
			$base = SERVER_URL . ltrim($base, '/ ');

		$url_parts = parse_url($base);
		$new_url = $url_parts['scheme']."://".
				  (@$url_parts['user']?@$url_parts['user'].":".@$url_parts['pass']."@":"").
				  $url_parts['host'];

		if(substr($url, 0, 1) == "/")
		{
			$new_url .= $url;
		}
		else
		{
			if(substr($url_parts['path'], -1) == '/')
				$url_parts['path'] .= 'index.html';
			
			$dir = str_replace("\\", "/", dirname($url_parts['path']));
			$dir = rtrim($dir, "/");
			$new_url .= $dir."/".$url;
		}
		return $new_url;
	}

	/******* Renderers ********/

	/**
	* Render page
	* @param string $template
	* @param string $base
	* @return void
	*/
	function render($template = '', $container = '')
	{
		if(!$container)
			$container = $this->curContainer;

		if($template){
			$this->curTemplate = $template;
			$this->assign('template', $this->curTemplate);
		}

		$this->assignDefaults();


		@session_start();
		if(isset($_SESSION['saved_vars']) && is_array($_SESSION['saved_vars'])){
			foreach($_SESSION['saved_vars'] as $n=>$v) $this->assign($n, $v);
			unset($_SESSION['saved_vars']);
		}
		session_write_close();


		error_reporting(E_ALL ^ E_NOTICE);
		if($container[0] == '/')
		    $this->tpl->display($container);
		else
			$this->tpl->display($this->stdTemplatesFolder.'/'.$container);

		exit;
	}
	
	/**
	* Fetch page
	* Рендерит шаблон и возвращает его, а не выводит на экран
	* @param string $template Шаблон, который парсить
	* @param string $container Контейнер, который парсить
	* @return mixed
	*/
	function fetch($template = '', $container = '')
	{
		if(!$container)
			$container = $this->curContainer;

		if($template){
			$this->curTemplate = $template;
			$this->assign('template', $this->curTemplate);
		}

		$this->assignDefaults();

		error_reporting(E_ALL ^ E_NOTICE);

		return $this->tpl->fetch($this->stdTemplatesFolder.'/'.$container);
	}

	/**
	* assignDefaults
	* @return void
	*/
	function assignDefaults(){
		$this->assign('cssFiles', $this->cssFiles);
		$this->assign('jsFiles', $this->jsFiles);
		$this->assign('metaLinks', $this->metaLinks);

		if($this->user->authed()){
			$this->assign('user', $this->user->getData());
			$this->assign('authed', true);
		}
		$this->assign('langs', $this->lang->getLangFiles());

		$this->assign('SECTION', $this->curSection);
		
		$this->assign('ROOT_LANG', ROOT_URL.$this->getCurLang().'/');

		$this->assign('_SITE', $this);
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

	/******** NAVIGATION PATH **************/

	/**
	* Получить Навигационый путь
	* @return mixed
	*/
	function getNavPath(){
		if(!$this->navPath) return '';
		$path = array();
		foreach($this->navPath as $n => $v){
			
			if(!empty($v['pre_title'])){
				$pre = $v['pre_title'];
			}else{
				$pre = '';
			}
			
			if($v['link'])
				$path[] = $pre.'<a href="'.$v['link'].'">'.$v['title'].'</a>';
			else
				$path[] = $pre.$v['title'];
		}
		return implode($this->navSeparator, $path);
	}

	/**
	* Добавить Навигационый путь
	* @param mixed $title
	* @param mixed $link
	* @param mixed $additional
	* @return void
	*/
	function addNavPath($title, $link = SELF_URL, $additional = array()){
		$this->navPath[] = array_merge(array('link' => $link, 'title' => $title), $additional);
	}

	/**
	* Убрать Навигационый путь
	* @param mixed $link
	* @return void
	*/
	function removeNavPath($link){
		unset($this->navPath[$link]);
	}

	/**
	* Установить Навигационый разделитель
	* @param mixed $separator
	* @return void
	*/
	function setNavSeparator($separator){
		$this->navSeparator = $separator;
	}
	
	/****** MAIL ***************************/
	
	/**
	* Отправить Email
	* @param mixed $to
	* @param mixed $subj
	* @param mixed $template
	* @param bool $from
	* @return void
	*/
	function sendEmail($to, $subj, $template, $from = false){
		global $_SERVER;
		if(!$from)
			$from = $this->getSetting('robot_email', ucwords($_SERVER['HTTP_HOST']).' <noreply@'.$_SERVER['HTTP_HOST'].'>');
		
		
		return mail($to, '=?UTF-8?B?'.base64_encode($subj).'?=', file_exists(TEMPLATES_PATH.'/'.$template) ? $this->tpl->fetch($template) : $template, 
			"From: $from\r\nContent-Type: text/html; charset=utf-8", "-f$from");
	}
	
	/**
	* Ищет нужный текст в таблице pages и присваивает его в шаблон под именем customText
	* @param mixed $url
	* @return void
	*/
	public function assignPageText($url){
		$this->assign('customText',
			$this->db->selectRow('SELECT * FROM pages WHERE url = ?', $url));
	}


	public abstract function Run();

}