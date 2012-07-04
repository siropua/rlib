<?php

/**
 * Structore of RAC
 *
 * @version $Id: RACStruct.php,v 1.1 2007/11/26 17:45:57 steel Exp $
 * @copyright 2007
 **/


if(!class_exists('Config_File'))
	require_once("rlib/quicky/Config_File.class.php");

@define('MODULES_DIR', 'modules');

/**
 * Управление структурой модулей админки
 *
 **/
class RACStruct{

	var $_basePath = ''	;
	var $_configReader = null;

	var $_sections = array();
	var $_struct = array();

	var $_modified = false;

	/**
     * Constructor
     * @access protected
     */
	function RACStruct($admin_path = '')
	{
		$this->_basePath = realpath($admin_path ? $admin_path : ROOT.'/admin');
		$this->_configReader = new Config_File();
	}

	##############################################################################

	/**
	 * Get sections list
	 * @access public
	 * @return void
	 **/
	function getSections($force_refresh = false){
		// chek cache
		if(count($this->_sections) && !$force_refresh && !$this->_modified)
			return $this->_sections;

		$this->_sections = array();
		foreach(glob($this->_basePath.'/modules/*', GLOB_ONLYDIR) as $s)
		{
			###### cvs fix #########
			if(basename($s) == 'CVS') continue;

			##### no config file ##
			if(!file_exists($s.'/config.ini')) continue;
			
			/** section disabled **/
			if(file_exists($s.'/disabled')) continue;

			$conf = $this->_configReader->get($s.'/config.ini', '');
			if(!$conf || !@$conf['name']) continue;

			$this->_sections[basename($s)] = $conf['name'];
		}
		$this->_modified = false;
		return $this->_sections;
	}

	/**
	 * Create new section
	 * @access public
	 * @return void
	 **/
	function createSection($params)
	{
		if(!is_array($params))return false;
		$params = array_map('trim', $params);
		$url = $params['url']; $name = $params['name'];
		if(!$url || !$name) return false;

		if(!count($this->_sections))
			$this->getSections(true);

		if(@$this->_sections[$url])
			return true;

		$section_path = $this->_basePath.'/'.MODULES_DIR.'/'.$url;

		@mkdir($section_path, 0777);

		if(!is_dir($section_path))
			return false;
		chmod($section_path, 0777);

		$content = "name = $name\n";

		// section icon ####################################
		if(@$icon = $params['icon'])
		{
			if(@$im = getimagesize($icon))
			{
				$icon_fn = $url;
				$exts = array(1=>'.gif', 2=>'.jpg', 3=>'.png');
				if(@$exts[$im[2]])
					$icon_fn .= $exts[$im[2]];
				move_uploaded_file($icon, $section_path.'/'.$icon_fn);
				$content .= "icon = $icon_fn\n";
			}
		}
		####################################################


		file_put_contents($section_path.'/config.ini', $content);
		chmod($section_path.'/config.ini', 0666);
		$this->_modified = true;
		return true;
	}

	##############################################################################

	/**
	 * Get modules list of any sections
	 * @access public
	 * @return void
	 **/
	function getModules($section, $force_refresh = false)
	{
		$section = trim($section);

		if(!count($this->_sections) && !$this->getSections(true))
			return false;  // Some errors with sections

		if(@!$this->_sections[$section])
			return false; // section not present in current configuration

		if(count(@$this->_struct[$section]) && !$force_refresh && !$this->_modified)
			return $this->_struct[$section]; // cached info


		// begin scanning sections dir
		$this->_struct[$section] = array();
		foreach(glob($this->_basePath.'/modules/'.$section.'/*', GLOB_ONLYDIR) as $s)
		{
			###### cvs fix #########
			if(basename($s) == 'CVS') continue;

			$config_file = $this->getFilePath($s, 'config.ini');

			##### no config file ##
			if(!$config_file) continue;
			
			/** module disabled **/
			if(file_exists($s.'/.disabled')) continue;

			$conf = $this->_configReader->get($config_file, 'main');
			if(!$conf || !@$conf['name']) continue;
			
			$conf['rights'] = $this->_configReader->get($config_file, 'rights');

			$this->_struct[$section][basename($s)] = $conf['name'];
		}

		return $this->_struct[$section];
	}

	/**
	 * Get all modules of all sections
	 * @access public
	 * @return void
	 **/
	function getAllModules()
	{
		$this->getSections(true);
		foreach($this->_sections as $s)
		{
			$this->getModules($s, true);
		}
		return $this->_struct;
	}



	/**
	 *
	 * @access public
	 * @return void
	 **/
	function createModule($params)
	{
		if(!is_array($params))return false;
		$params = array_map('trim', $params);
		$url = $params['url']; $name = $params['name']; $section = $params['section'];
		if(!$url || !$name || !$section)
		{
			trigger_error("Name, url or section not specified", E_USER_WARNING);
			return false;
		}

		$this->getSections(true);
		if(@!$this->_sections[$section])
		{
			trigger_error("Section $section not exists", E_USER_WARNING);
			return false;
		}

		$this->getModules($section, true);
		if(@$this->_struct[$section][$url])
			return true;

		$module_path = $this->_basePath.'/'.MODULES_DIR.'/'.$section.'/'.$url;

		@mkdir($module_path, 0777);

		if(!is_dir($module_path))
			return false;
		chmod($module_path, 0777);

		$content = "[main]\nname = $name\n";

		// section icon ####################################
		if(@$icon = $params['icon'])
		{
			if(@$im = getimagesize($icon))
			{
				$icon_fn = $url;
				$exts = array(1=>'.gif', 2=>'.jpg', 3=>'.png');
				if(@$exts[$im[2]])
					$icon_fn .= $exts[$im[2]];
				move_uploaded_file($icon, $module_path.'/'.$icon_fn);
				$content .= "icon = $icon_fn\n";
			}
		}
		####################################################


		file_put_contents($module_path.'/config.ini', $content);

		if(@$params['create_files'])
		{
			file_put_contents($module_path.'/'.$url.'.php', "<?php\n");
			@chmod($module_path.'/'.$url.'.php', 0666);

			file_put_contents($module_path.'/'.$url.'.tpl', '');
			@chmod($module_path.'/'.$url.'.tpl', 0666);
		}

		$this->_modified = true;
		return true;
	}



	##############################################################################
	/**
	 * Get any file from section or module by default name
	 * @access public
	 * @return void
	 **/
	function getFilePath($dir, $def_name, $add_ext = array())
	{
		$ext = strrchr($def_name, ".");

		if(file_exists(realpath($dir).'/'.basename($dir).$ext))
			return realpath($dir).'/'.basename($dir).$ext; // like {module_name}.ini

		if(file_exists(realpath($dir).'/'.$def_name))
			return realpath($dir).'/'.$def_name;

		if(count($add_ext))
		{
			foreach($add_ext as $aext)
			{
				if(file_exists(realpath($dir).'/'.$dir.$aext))
					return realpath($dir).'/'.$dir.$aext;
			}
		}

		return false;
	}


}
