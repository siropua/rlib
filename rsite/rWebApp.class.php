<?php


require_once 'rlib/rsite/rApplication.class.php';


class rWebApp extends rApplication{

	protected $templateContainer = 'index.tpl';
	protected $stdTemplatesFolder;
	protected $templateFile = 'main.tpl';


	public function __construct(){
		parent::__construct();

	}



	/** 
	assigments 
	*/
	/**
	* assignDefaults
	* @return void
	*/
	function assignDefaults(){

		$this->assign('_APPRES_cssFiles', $this->cssFiles);
		$this->assign('_APPRES_jsFiles', $this->jsFiles);
		$this->assign('_APPRES_metaLinks', $this->metaLinks);

		if($this->user->authed()){
			$this->assign('user', $this->user->getData());
			$this->assign('authed', true);
		}

		$this->assign('langs_files', $this->lang->getLangFiles());		
		$this->assign('ROOT_LANG', ROOT_URL.$this->lang->getCurLang().'/');

		$this->assign('_APP', $this);
		
		$this->assign('_APPMSG_NOTICE', $this->app->getMessages(APPMSG_NOTICE));
		$this->assign('_APPMSG_ERROR', $this->app->getMessages(APPMSG_ERROR));
		$this->assign('_APPMSG_OK', $this->app->getMessages(APPMSG_OK));

		$this->setTitle($this->app->getSetting('default_title', '', false));
		$this->setKeywords($this->app->getSetting('default_keywords', '', false));
		$this->setDescription($this->app->getSetting('default_descr', '', false));
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
	 BREADCRUMBS 
	**************/

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

	/**
		RENDERS
	**/
	public function render($template = false, $container = false){

		if($template) $this->setTemplate($template);
		if(!$container) $container = $this->templateContainer;

		$this->assignDefaults();


		@session_start();
		if(isset($_SESSION['saved_vars']) && is_array($_SESSION['saved_vars'])){
			foreach($_SESSION['saved_vars'] as $n=>$v) $this->assign($n, $v);
			unset($_SESSION['saved_vars']);
		}
		session_write_close();


		error_reporting(E_ALL ^ E_NOTICE);
		if(($container[0] == '/') || strpos($container, ':'))
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
	function fetch($template = false, $container = false){

		if($template) $this->setTemplate($template);
		if(!$container) $container = $this->templateContainer;

		$this->assignDefaults();


		error_reporting(E_ALL ^ E_NOTICE);

		return $this->tpl->fetch($this->stdTemplatesFolder.'/'.$container);
	}

	/**
		RESOURCES 
	**/


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
	* Добавить CSS
	* @param mixed $file
	* @return void
	*/
	function addCSS($file){
		if(!array_search($file, $this->cssFiles))$this->cssFiles[] = $this->url->uniPath($file, DESIGN);
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
		if(!array_search($file, $this->jsFiles))$this->jsFiles[] = $this->url->uniPath($file, STATIC_JS_URL);
	}

	public function addFWJS($file){
		$this->addJS(ROOT_URL.ENGINE_FOLDER.'/fws/'.$file);
	}

	public function addFWCSS($file){
		$this->addCSS(ROOT_URL.ENGINE_FOLDER.'/fws/'.$file);
	}

	/**
	* getJSFiles
	* @return mixed
	*/
	function getJSFiles(){
		return $this->jsFiles;
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
		Templates
	**/
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
	* Устанавливает контейнер (родительский шаблон который будем рендерить)
	* @param mixed $container
	* @return void
	*/
	function setContainer($container)
	{
		$this->templateContainer = $container;
	}
	

	
	/**
	* устанавливает внутрений темплейт
	* @param mixed $template
	* @return void
	*/
	public function setTemplate($template)
	{		
		$this->templateFile = $template; 
		$this->assign('_APPPAGE_TEMPLATE', $this->curTemplate); 
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


	
}