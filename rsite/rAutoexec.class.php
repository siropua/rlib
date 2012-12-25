<?php

interface irAutoexec{
	// перед созданием APP
	public function __construct();

	// перед созданием модуля rSite
	public function beforeCreate( $app,  $factory);

	// перед rSite::Run()
	public function beforeRun( $app,  $module);

	// после rSite::Run()
	public function afterRun( $app,  $module);
}

abstract class rAutoexec implements irAutoexec{
	// перед созданием APP
	public function __construct(){}

	// перед созданием модуля rSite
	public function beforeCreate( $app,  $factory){}

	// перед rSite::Run()
	public function beforeRun( $app,  $module){}

	// после rSite::Run()
	public function afterRun( $app,  $module){}
}