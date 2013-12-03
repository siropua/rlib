<?php

/**
 * jevix parser for rBlog with default settings
 *
 * @version $Id$
 * @copyright 2008
 */

require_once('rlib/jevix.class.php');

class jevixParser{

	protected $j = null;

	function __construct(){
		$this->j = new Jevix;
		$this->init();
	}

	protected function init(){
		$this->j->cfgAllowTags(array(
			'b', 'i', 'u', 's', 'ul', 'li', 'p', 'img', 'code', 'div', 'a', 'pre', 'strong', 'br'
		));
		$this->j->cfgSetTagShort(array(
			'img', 'br'
		));
		$this->j->cfgSetXHTMLMode(false);
		$this->j->cfgSetTagNoTypography('pre');
		$this->j->cfgSetTagPreformatted('code');

		$this->j->cfgSetTagParamsRequired('a', array('href'));
		$this->j->cfgSetTagParamsRequired('img', array('src'));
		$this->j->cfgAllowTagParams('img', array(
			'width', 'height', 'alt', 'src'
		));
		$this->j->cfgAllowTagParams('a', array(
			'href'
		));
		
		$this->j->cfgAllowTagParams('code', array(
			'class'
		));
	}

	public function parse($text){
		$e = '';
		return $this->j->parse($text, $e);
	}
}