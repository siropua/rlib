<?php

require_once 'rlib/rsite/iModulesFactory.class.php';

class rAdminModulesFactory extends iModulesFactory{



	public function __construct(rApplication $app){
		parent::__construct($app);
	}


	public function getModule(){

		if(!$this->app->user->can('admin')){
			throw new rNotFound();
		}

		$this->app->url->redirect2RightURL('/');

		/** ежели у нас индекс - открываем индекс **/
		if(!$this->rURL->path(2)){
			return $this->getIndexModule();
		}

		if(!$this->app->url->path(5)) throw new rNotFound();
		

		if($this->rURL->path(3) == 'module'){
			$s = $this->app->url->path(4); // section
			$m = $this->app->url->path(5); // module
			$t = $this->app->url->path(6); // tab

			if(!$this->app->user->can('admin/'.$s.'/'.$m)) 
				throw new rNotFound();

			$modulePath = SITE_PATH.'/'.ADMIN_FOLDER.'/modules/'.$s.'/'.$m;
			if(!is_dir($modulePath)){
				$modulePath = ENGINE_PATH.'/'.ADMIN_FOLDER.'/modules/'.$s.'/'.$m;
				if(!is_dir($modules)) throw new rNotFound();
			}

			$menu = new RACMenuWorker;
			$userMenu = $menu->getUserMenu($this->user);


				
		}



		throw new rNotFound();
		
	}



	/**
		берем главную админки 
	**/
	public function getIndexModule(){
		if(file_exists(SITE_PATH.'/admin/index.php')){
			require_once SITE_PATH.'/admin/index.php';
		}else{
			require_once ENGINE_PATH.'/admin/index.php';
		}

		return new admin_module_index($this->app);
	}

}