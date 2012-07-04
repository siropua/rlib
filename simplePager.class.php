<?php


class simplePager{
	
	var $onPage = 20;
	var $curPage = 1;
	var $linkTpl = '?page={%PAGE%}';
	var $visible_pages = 2;
	protected $forceGapOnPages = false;
	var $use_gaps = true;
	var $gap_str = "... <span class='pageDelim'>|</span>";
	var $delimiter = "<span class='pageDelim'>|</span>";
	var $showPrevNext = true;
	var $showFirstLast = false;
	var $markCurPage = true;
	
	
	var $_pagesCount = 0;
	var $_totalItems = 0;

	/**
	* simplePager
	* @param int $onPage
	* @param int $curPage
	* @param string $link
	* @return void
	*/
	function simplePager($onPage = 20, $curPage = 1, $link = '?page={%PAGE%}'){
		$this->setOnPage($onPage);
		$this->setCurPage($curPage);
		$this->setLink($link);
	}
	
	/**
	* Устанавливает количество элементов на странице
	* @param mixed $onPage
	* @return void
	*/
	function setOnPage($onPage){
		$this->onPage = (int)$onPage;
	}
	
	/**
	* Устанавливает текущую страницу
	* @param mixed $curPage
	* @return void
	*/
	function setCurPage($curPage){
		$this->curPage = (int)$curPage;
		if(!$this->curPage) $this->curPage = 1;
	}
	
	/**
	* Устанавливает шаблон ссылки. {%PAGE%} - замениться на текущую страницу
	* @param mixed $link
	* @return void
	*/
	function setLink($link){
		$this->linkTpl = $link;
	}
	
	/**
	* setDelimiter
	* @param string $new_delim
	* @return void
	*/
	function setDelimiter($new_delim = ' <b>|</b> '){
		$this->delimiter = $new_delim;
	}
	
	/**
	* setGap
	* @param string $gap_str
	* @return void
	*/
	function setGap($gap_str = "... <b>|</b> "){
		$this->gap_str = $gap_str;
	}
	
	/**
	* Устанавливает максимальное количество страниц, 
	  после которых включается Gap-режим.
	* @param int $gop Количество страниц
	* @return void
	*/
	public function setForceGapOnPages($gop){
		$this->forceGapOnPages = (int)$gop;
	}
	
	/**
	* Устанавливает количество видимых страниц
	* @param int $gop Количество видимых страниц
	* @return void
	*/
	public function setVisiblePages($pages){
		$this->visible_pages = (int)$pages;
	}
	
	/**
	* setItemsCount
	* @param mixed $count
	* @return void
	*/
	function setItemsCount($count){
		$this->_totalItems = (int)$count;
		if($this->curPage > $this->getPagesCount()) $this->curPage = $this->getPagesCount();
	}
	
	/**
	* Узнать кол-во страниц
	* @return mixed
	*/
	function getPagesCount(){
		if(!$this->_totalItems || !$this->onPage)
			return 1;
		return (int)ceil($this->_totalItems/$this->onPage);
	}
	
	/**
	* needPagesGap
	* @return mixed
	*/
	function needPagesGap(){
		
		if($this->forceGapOnPages) return $this->forceGapOnPages < $this->getPagesCount();
		
		$pages2gap = ($this->visible_pages + 1) * 2; // first and last pages
		$pages2gap += ($this->visible_pages * 2) + 1; // middle pages
		$pages2gap += $this->visible_pages * 2;	// gaps
		return $this->getPagesCount() > $pages2gap;
	}
	
	/**
	* calcGaps
	* @param mixed $page
	* @return mixed
	*/
	function calcGaps($page)
	{
		if (!$this->needPagesGap())
		{
		    return false;
		}
		$page = (int)$page;
		if(!$page) $page = $this->curPage;
		if(!$page) $page = 1;
		$ret = array
		(
			1 => array(
				"start" => $this->visible_pages + 2,
				"length" => $page - $this->visible_pages - ($this->visible_pages+1)
			),
			2 => array(
				"start" => $page + $this->visible_pages + 1,
				"length" => $this->getPagesCount() - ($this->visible_pages+1) - ($page + $this->visible_pages)
			)
		);
		if($ret[1]['length'] <=1 )$ret[1]['length'] = 0;
		if($ret[2]['length'] <=1 )$ret[2]['length'] = 0;

		return $ret;
	}
	
	/**
	* getMySQLLimit
	* @param int $page
	* @return mixed
	*/
	function getMySQLLimit($page = 0)
	{
		if($page)
			$this->setCurPage($page);
		
		$res_str = "";
		$res_str .= (($this->curPage * $this->onPage) - $this->onPage);
		$res_str .= ", ".$this->onPage;
		return $res_str;
	}	
	
	/** для массивов, например */
	
	/**
	* getSliceStart
	* @return mixed
	*/
	function getSliceStart(){
		return (($this->curPage * $this->onPage) - $this->onPage);
	}
	
	/**
	* getSliceLength
	* @return mixed
	*/
	function getSliceLength(){
		return $this->onPage;
	}
	
	/**
	* arraySlice
	* @param mixed $arr
	* @return mixed
	*/
	public function arraySlice($arr){
		return array_slice($arr, $this->getSliceStart(), $this->getSliceLength());
	}
	
	/**
	* arraySlice
	* @param int $itemsCount
	* @return mixed
	*/
	function getPagesStr($itemsCount = 0)
	{
		
		if($itemsCount)
			$this->setItemsCount($itemsCount);
		
		if(!$this->_totalItems) return "";
		
		if($this->_totalItems <= $this->onPage)	return "";

		$pagesCount = $this->getPagesCount();
		
		if($pagesCount < 2) return '';

		$res_str = "";

		// PREV
		if(($this->curPage > 1) && $this->showPrevNext)
		{
			$res_str .= "<a rel='prev' class='prevPage' href=\"".
				str_replace("{%PAGE%}", $this->curPage - 1, $this->linkTpl).
				"\">&larr;</a>" . $this->delimiter;
		}

		$calc_gaps = $this->use_gaps && $this->needPagesGap();
		$gaps = $calc_gaps ? $this->calcGaps($this->curPage) : array();
		$showed_1st_gap = $showed_2nd_gap = false;

		// PAGES
		
		for($i=1; $i <= $pagesCount; $i++)
		{
			if($calc_gaps)
			{
				if(!$showed_1st_gap && $gaps[1]['length'] && $i == $gaps[1]['start'])
				{
					$showed_1st_gap = true;
					$i += $gaps[1]['length'] - 2;
					$res_str .= $this->gap_str;
					continue;
				}
				if(!$showed_2nd_gap && $gaps[2]['length'] && $i == $gaps[2]['start'])
				{
					$showed_2nd_gap = true;
					$i += $gaps[2]['length'] - 1;
					$res_str .= $this->gap_str;
					continue;
				}

			}

			$res_str .= "<a class='".($i == $this->curPage ? 'curPage' : 'pageN')."' href=\"".str_replace("{%PAGE%}", $i, $this->linkTpl)."\">$i</a>".$this->delimiter;
			
		}
		
		// NEXT
		if(($this->curPage < $pagesCount) && $this->showPrevNext)
		{
			$res_str .= "<a rel='next' class='nextPage' href=\"".str_replace("{%PAGE%}", $this->curPage + 1, $this->linkTpl)."\">&rarr;</a>";
		}

		$l = strlen($this->delimiter);
		
		if(substr($res_str, -$l) == $this->delimiter){
			$res_str = substr($res_str, 0, -$l);
		}
		
		
		return $res_str;
	} // getPagesStr
	
}