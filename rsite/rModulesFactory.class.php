<?php

require_once 'rlib/rsite/iModulesFactory.class.php';

class rModulesFactory extends iModulesFactory{

	protected $order;


	public function __construct(rApplication $app, array $order){
		parent::__construct($app);
		$this->order = $order;
	}

	public function getModule(){

		/** ежели у нас индекс - открываем индекс **/
		if(!$this->rURL->path(1)){
			return $this->getIndexModule();
		}

		// пробуем найти метод, создающий модуль
		foreach ($this->order as $method) {
			$method = 'get_'.$method;
			// все не сущестующие методы не вызываем
			if(!method_exists(__CLASS__, $method)) continue; 

			if($module = self::$method()) return $module;
		}


		$module = new rMySite($this->app);
		$module->notFound();

	}

	/** 
		берем модуль из каталога 
	**/
	protected function get_module_at_dir($dir){
		$moduleName = $this->rURL->path(1);
		
		if($moduleName == 'index') return false; // пока лучше не придумал

		if(is_dir($dir.'/'.$moduleName))
			$fn = $dir.'/'.$moduleName.'/'.$moduleName.'.php';
		else
			$fn = $dir.'/'.$moduleName.'.php';

		if(!file_exists($fn)) return false;

		include_once $fn;

		$moduleClass = 'module_'.$moduleName;

		if(!class_exists($moduleClass)) throw new Exception("Wrong module file");
		

		return new $moduleClass($this->app);
	}

	/**
		берем модуль из сайта
	**/
	public function get_site(){
		return $this->get_module_at_dir(MODULES_PATH);
	}

	/** 
		берем модуль из движка
	**/
	public function get_engine(){
		return $this->get_module_at_dir(ENGINE_MODULES_PATH);
	}


	/**
		пытаемся подхватить tpl-модуль из static-папки
	**/
	public function get_tpl(){
		$filename = TEMPLATES_PATH.'/'.STATIC_TPL_FOLDER.'/'.$this->rURL->safePath().'.tpl';
		if(!file_exists($filename)){
			return false;
		}

		$this->app->url->redirect2RightURL('.html');
		
		$module = new rMySite($this->app);
		$module->setTemplate(STATIC_TPL_FOLDER.'/'.$this->rURL->safePath().'.tpl');

		return $module;
	}

	/**
		пытаемся найти страницу в таблице статических страниц
	**/
	public function get_page(){

		if($text = @$this->db->selectRow('SELECT * FROM static_pages WHERE url = ?', $this->rURL->safePath())){


			$this->app->url->redirect2RightURL();

			$module = new rMySite($this->app);
			$module->assign('customText', $text);
			$module->setTemplate('staticPage.tpl');
			if($text['title']) $module->setTitle($text['title']);


			return $module;
		}else
			return false;
	}

	/**
		пытаемся найти блог 
	**/

	public function get_blog(){
		$blog = new rMyBlog($this->app);

		if(defined('SIMPLE_BLOG_MODE') && SIMPLE_BLOG_MODE){
			// блог всего один и посты открываются по адресу site.com/post-url.html
			if($post = $blog->getByURL($this->app->url->path(1))){
				$module = new rMySiteBlog($app, $blog);
				$module->assignPost($post);
				
				return $module;
			}
		}else{
			// блогов несколько, вначале ищем блог в таблице
			if($blogSection = $blog->selectBlog($this->app->url->path(1))){
				$module = new rMySiteBlog($app, $blog);
				$module->routeURL();
				return $module;
			}
		}

		return false; // ничего не нашли
	}


	/**
		берем индексный модуль 
	**/
	public function getIndexModule(){
		if(file_exists(MODULES_PATH.'/index.php')){
			include_once MODULES_PATH.'/index.php';
			return new module_index($this->app);
		}else{
			include_once ENGINE_MODULES_PATH.'/index.php';
			return new module_engine_index($this->app);
		}
	}

}