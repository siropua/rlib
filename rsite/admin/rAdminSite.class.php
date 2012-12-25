<?php


require_once 'rlib/rsite/rSite.class.php';
require_once 'rlib/rsite/admin/RACStruct.php';

if(!defined('ADMIN_JS_URL'))
	define('ADMIN_JS_URL', ADMIN_URL.'js/');

if(!defined('ADMIN_DESIGN'))
	define('ADMIN_DESIGN', ADMIN_URL.'templates/');

abstract class rAdminSite extends rSite{

	protected $menu = array();

	public $curModule = '';
	public $curSection = '';
	public $curTab = '';
	
	protected $titlePathSeparator = ' / ';

	function __construct($app){
		$app->tpl->template_dir = ADMIN_PATH .'/templates/';
		parent::__construct($app);
		
		$this->menu = $this->getUserMenu();

		$this->assign('_ADMIN_MENU', $this->menu);

		$this->tpl->template_dir = ADMIN_PATH .'/templates/';
		$this->assign('ADMIN_URL', ADMIN_URL);
		$this->assign('ADMIN_DESIGN', ADMIN_DESIGN);
		$this->assign('ADMIN_IMG', ADMIN_DESIGN.'img/');
		$this->setTemplatesFolder(ADMIN_PATH .'/templates');
		$this->assign('ADMIN_JS', ADMIN_JS_URL);

		$this->setContainer(ENGINE_PATH.'/admin/index-topmenu.tpl');
	}

	/******** SELECTORS *****************************/
	public function selectSection($section){

		$section = $this->rURL->URLize($section);

		if(empty($this->menu[$section])){
			$this->renderError($this->lang->Section_not_found);
		}

		$this->curSection = $section;

		$this->assign('_SECTION_INFO', $this->menu[$this->curSection]);
		$this->assign('_SECTION', $this->curSection);

		$this->assign('_SECTION_URL', ADMIN_URL.'module/'.$section.'/');

		$this->addNavPath($this->menu[$this->curSection]['name'], '');

		return true;
	}

	public function selectModule($section, $module = ''){

		if(!$module){
			$module = $section;
			$section = $this->curSection;
		}else $this->selectSection($section);

		$module = $this->rURL->URLize($module);

		if(empty($this->menu[$section]['modules'][$module])){
			$this->renderError($this->lang->Module_not_found, $this->lang->No_module_in_this_section);
		}

		$this->moduleInfo =& $this->menu[$section]['modules'][$module];

		$this->assign("_MODULE_INFO", $this->moduleInfo);

		$this->addNavPath($this->moduleInfo['name'], ADMIN_URL.'module/'.$this->curSection.'/'.$module.'/');
		
		$this->setTitle($this->moduleInfo['name'].
			$this->titlePathSeparator.
			$this->menu[$section]['name']);

		$this->assign('_MODULE_URL', ADMIN_URL.'module/'.$this->curSection.'/'.$module.'/');
		$this->assign('_MODULE_BASE', ADMIN_URL.'modules/'.$this->curSection.'/'.$module.'/');
		$this->assign('_MODULE_ROOT', ADMIN_URL.'module/'.$this->curSection.'/'.$module.'/');


		if(!empty($this->moduleInfo['tabs'])){
			$this->assign('_MODULE_TABS', $this->moduleInfo['tabs']);
			$k = array_keys($this->moduleInfo['tabs']);
			$this->selectTab($k[0]);
		}



		$this->curModule = $module;

		$module_tpl = false;
		if(@$this->moduleInfo['output'] != "only_php")
			if(@$this->moduleInfo['tpl'])
			{
				$module_tpl = $this->moduleInfo['tpl'];
			}else
			{
				if(file_exists($this->getModulePath()."/index.tpl"))
				{
					$module_tpl = "index.tpl";
				}else
				{
					$module_tpl = $module.".tpl";
				}
			}

		if($module_tpl){
			$this->assign('template', $module_tpl);
			$this->curTemplate = $module_tpl;
		}

		if(file_exists($addLang = $this->getModulePath().'/lang.'.DEF_LANG.'.ini'))
			$this->lang->addModuleLang($addLang);

		if(@$this->moduleInfo['js'])
			$this->addModuleJS($this->moduleInfo['js']);

		if(@$this->moduleInfo['style'])
			$this->addModuleCSS($this->moduleInfo['style']);

		$this->assign('_MODULE', $this->curModule);
		$this->assign('_MODULE_AJAX', ADMIN_URL."ajax/$section/$module/");


		$this->tpl->template_dir = $this->getModulePath() .'/';

		if(!is_dir(COMPILED_PATH."/admin/m")) @mkdir(COMPILED_PATH."/admin/m", 0777, true);
		if(!is_dir(COMPILED_PATH."/admin/m/$section/$module")){
			@mkdir(COMPILED_PATH."/admin/m/$section", 0777, true);
			@mkdir(COMPILED_PATH."/admin/m/$section/$module", 0777, true);
		}

		$this->tpl->compile_dir = COMPILED_PATH."/admin/m/$section/$module/";

		return true;
	}

	function selectTab($tab){

		if(empty($this->moduleInfo['tabs'])){
			$this->curTab = NULL;
			return NULL;
		}

		if(empty($this->moduleInfo['tabs'][$tab])){
			$k = array_keys($this->moduleInfo['tabs']);
			$this->curTab = $k[0];
		}else{
			$this->curTab = $tab;
		}
		
		$this->setTitle(
			$this->moduleInfo['tabs'][$tab]['name'].
			$this->titlePathSeparator.
			$this->moduleInfo['name'].
			$this->titlePathSeparator.
			$this->menu[$this->curSection]['name']
		);
		
		$this->assign('_MODULE_URL', ADMIN_URL.'module/'.$this->curSection.'/'.$this->curModule.'/'.$this->curTab.'/');
		$this->assign('_TAB', $this->curTab);

		return $this->curTab;
	}

	function getTab(){
		return $this->curTab;
	}

	/**
	* Returns user menu depending on user rights
	*/
	function getUserMenu(){
		$menu = new RACMenuWorker;
		return $menu->getUserMenu($this->user);
	}
	
	// interface
	protected function updateMenu(&$menu){
		return;
	}

	/**
	 * Возвращает адрес файла, который необходимо проинклудить для работы модуля
	 *
	 * @return
	 **/
	function need2Require(){
		if(@$this->menu[$this->curSection]['modules'][$this->curModule]['output']=="only_tpl")
			return false;

		// finding script index
		$module_index = $this->getModulePath();
		if(!$module_index) return false;
		$module_index .= "/";

		if(@$this->moduleInfo['php'])
		{
			$module_index .= $this->moduleInfo['php'];
		}else
		{
			if($this->curTab && @$this->moduleInfo['autotabs'])
				if(file_exists($module_index.$this->curTab.'.php'))
					return $module_index.$this->curTab.'.php';

			if(file_exists($module_index."index.php"))
			{
				$module_index .= "index.php";
			}else $module_index .= $this->curModule.".php";
		}

		return $module_index;

	}
	
	function getPreIncludes(){
		$inc = array();
		$p = MODULES_PATH."/".$this->curSection;

		if(file_exists("$p/config.pre.php")) $inc[] = "$p/config.pre.php";
		if(file_exists("$p/init.php")) $inc[] = "$p/init.php";
		if(file_exists("$p/config.php")) $inc[] = "$p/config.php";
		
		$p .= "/".$this->curModule;

		if(file_exists("$p/config.pre.php")) $inc[] = "$p/config.pre.php";
		if(file_exists("$p/init.php")) $inc[] = "$p/init.php";
		if(file_exists("$p/config.php")) $inc[] = "$p/config.php";
		
		return $inc;
		
	}

	/************************ Manage resources *******/
	/******* CSS *******/

	function addModuleCSS($file){
		$this->cssFiles[] = MODULES_URL . $this->curSection.'/'.$this->curModule.'/'.$file;

	}

	function initWindowEngine(){
		$this->addJS('jquery.blockUI.js');
		$this->addJS('rWindow.js');
		$this->addCSS('windows.styles.css');
	}

	/****** JAVASCRIPT ******/

	function addModuleJS($file){
		$this->jsFiles[] = MODULES_URL . $this->curSection.'/'.$this->curModule.'/'.$file;
	}
	
	function addAdminJS($file){
		$this->jsFiles[] = ADMIN_JS_URL . $file;
	}

	function addAdminCSS($file){
		$this->cssFiles[] = ADMIN_DESIGN . $file;
	}

	function getModulePath(){
		if(!$this->curModule) return false;
		return MODULES_PATH."/".$this->curSection."/".$this->curModule;
	}



	/**
		MENU WORKING
	**/

	public function parseModules($forceReload = false){
		$menu = new RACMenuWorker;
		return $menu->parseModules($forceReload);
	}



}

class FormNotValid extends Exception{}
	
class EngineError extends Exception{}


/** класс для тикера **/
abstract class RACTicker{
	
	/** интервал обновления **/
	public $interval = 10;
	protected $site = null;

	public function __construct(rSite $site){
		$this->site = $site;
	}

	/** получаем данные тикера **/
	abstract public function getData();

}