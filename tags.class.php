<?php

/**

Я тут приведу примеры всех нужных таблиц.
Но на русском. Ибо воистену.

Вот структура таблицы, в которой содержится список всех тегов.
-----------------------------------------------------
CREATE TABLE `tags` (
          `id` int(10) unsigned NOT NULL auto_increment,
          `name` varchar(255) NOT NULL default '',
          `url` varchar(255) NOT NULL default '',
          `datepost` int(10) unsigned NOT NULL default '0',
          `creator_id` int(10) unsigned NOT NULL default '0',
		  `used` int(11) NOT NULL default '0',
          PRIMARY KEY  (`id`),
          UNIQUE KEY `url` (`url`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8
-----------------------------------------------------


Ну и для каждой entry нам нужна еще подобная таблица (заместо *):
-----------------------------------------------------
CREATE TABLE `*_tags_map` (
                     `entry_id` int(10) unsigned NOT NULL default '0',
                     `tag_id` int(10) unsigned NOT NULL default '0',
                     `datepost` int(10) unsigned NOT NULL default '0',
                     PRIMARY KEY  (`entry_id`,`tag_id`),
                     KEY `article` (`entry_id`),
                     KEY `tag_id` (`tag_id`)
                   ) ENGINE=MyISAM DEFAULT CHARSET=utf8
-----------------------------------------------------


Ну и не забываем сделать tags_cache у entry. AddTags вот например как примерная функия возвращает.
Возвращает засериализированный массив в виде array(tagURL=>tagName, tagURL2=>tagName2). Что, мне кажется, удобным. Эту строку и можно писать в кеш чтобы потом все было быстро и всё такое.

*/

require_once('rlib/rURLs.class.php');

class tags{
	/** link to PEAR database */
	var $_db = null;

	/** entry name */
	var $_entryName = '';

	var $_entryID = 0;

	var $_filterID = DBSIMPLE_SKIP;

	var $_tagsDelim = ',';

	var $_tagsTable = 'tags';
	var $_tagsMapSuffix = '_tags_map';

	var $rURL = null;


	function tags($dbLink, $entryName, $entryID = 0, $filterID = DBSIMPLE_SKIP){
		$this->_db = $dbLink;
		$this->_entryName = $entryName;
		$this->_entryID = (int)$entryID;
		if($filterID != DBSIMPLE_SKIP)$this->setFilterID($filterID);
		$this->rURL = new rURLs(false);
	}

	function setEntryID($entryID = 0){ $this->_entryID = (int)$entryID; }
	function setFilterID($filterID = 0){ $this->_filterID = $filterID; }
	function clearFilterID(){ $this->_filterID = DBSIMPLE_SKIP; }
	function setTagsTable($table){ $this->_tagsTable = $table; }

	function getEntryID(){ return $this->_entryID;}

	function setTagsDelim($newDelim){$this->_tagsDelim = $newDelim;}
	
	public function setEntryName($name){ $this->_entryName = $name; }


	/**
		@param $tags string tags list separated by $_tagsDelim
		@param $ownerID int owner ID
		@param $entryID int entry ID
	*/
	function addTags($tags, $ownerID, $entryID = 0){
		$tags = explode($this->_tagsDelim, $tags);
		$entryID = (int)$entryID;
		if($entryID) $this->setEntryID($entryID);
		$entryTable = $this->_entryName.$this->_tagsMapSuffix;
		foreach($tags as $tag){
			$tag = trim($tag);
			if(!$tag)continue;
			$tag = $this->getTagData($tag, true, (int)$ownerID);
			if(!$tag) continue;
			$already = $this->_db->selectCell('SELECT tag_id FROM ?# WHERE tag_id = ?d AND entry_id = ?d',
				$entryTable, $tag['id'], $this->getEntryID());
			if($already) continue;

			$insert = array(
				'tag_id' => $tag['id'],
				'entry_id' => $this->getEntryID(),
				'datepost' => time()
			);

			if($this->_filterID.' ' != DBSIMPLE_SKIP.' '){
				$insert['filter_id'] = $this->_filterID;
			}

			@$this->_db->query('INSERT INTO ?# SET ?a', $entryTable, $insert);
		}

		$this->_db->query('UPDATE '.$this->_tagsTable.' SET used = used + 1 WHERE id IN (SELECT tag_id FROM '.$entryTable.' WHERE entry_id = '.$this->getEntryID().')');


		return serialize($this->getTagsCache());
	}
	
	/**
	* РІРѕР·РІСЂР°С‰Р°РµС‚ true РµСЃР»Рё СЃРїРёСЃРєРё С‚СЌРіРѕРІ РёРґРµРЅС‚РёС‡РЅС‹
	**/
	public function isTagsEq($t1, $t2){
		if(!is_array($t1))
			$t1 = array_map('trim', explode($this->_tagsDelim, $t1));
		foreach($t1 as $n=>$v)if(!$v) unset($t1[$n]);
		sort($t1);
		$t1 = implode($this->_tagsDelim, $t1);
		
		if(!is_array($t2))
			$t2 = array_map('trim', explode($this->_tagsDelim, $t2));
		foreach($t2 as $n=>$v)if(!$v) unset($t2[$n]);
		sort($t2);
		$t2 = implode($this->_tagsDelim, $t2);
		
		return $t1 == $t2;
		
	}

	/**
	* Remove one tag
	*/
	function removeTag($tag){
		$tag = $this->getTagData($tag);
		if(!$tag) return false;
		$entryTable = $this->_entryName.$this->_tagsMapSuffix;
		$this->_db->query('DELETE FROM ?# WHERE tag_id = ?d AND entry_id = ?d',
			$entryTable, $tag['id'], $this->getEntryID());
		$this->_db->query('UPDATE ?# SET used = used - 1 WHERE id = ?d', $this->_tagsTable, $tag['id']);
		return serialize($this->getTagsCache());
	}

	function getTagsCache($entryID = 0){
		if($entryID)
			$this->setEntryID($entryID);
		if(!$this->_entryID) return false;

		$tagsCache = $this->_db->selectCol('SELECT
		t.url as ARRAY_KEY, t.name FROM '.$this->_entryName.$this->_tagsMapSuffix.' m
		LEFT JOIN '.$this->_tagsTable.' t ON t.id = m.tag_id WHERE m.entry_id = '.$this->getEntryID());


		return $tagsCache;
	}


	function clear($entryID = 0){
		if($entryID)
			$this->setEntryID($entryID);
		if(!$this->_entryID) return false;

		$entryTable = $this->_entryName.$this->_tagsMapSuffix;

		$this->_db->query('UPDATE ?# SET used = used - 1 WHERE id IN (SELECT tag_id FROM ?# WHERE entry_id = ?d)',
			$this->_tagsTable, $entryTable, $this->getEntryID());

		$this->_db->query('DELETE FROM ?# WHERE entry_id = ?d', $entryTable, $this->_entryID);
	}

	function getTagData($tag, $autoCreate = false, $creator_id = 0){
		$tag = trim($tag);
		$tagData = $this->_db->selectRow("SELECT * FROM ?# WHERE url = ?", $this->_tagsTable, $this->name2url($tag));
		if(!$tagData) $tagData = $this->_db->selectRow("SELECT * FROM ?# WHERE name = ?", $this->_tagsTable, $tag);

		if(!$tagData){
			if(!$autoCreate) return false;
			$res = $this->_db->query('INSERT INTO ?# SET ?a', $this->_tagsTable, array(
				'name'=>$tag,
				'url'=>$this->name2url($tag),
				'datepost'=>time(),
				'creator_id'=>(int)$creator_id
			));


			$tagData = $this->_db->selectRow("select * from ".$this->_tagsTable." where url = '".$this->name2url($tag)."'");

			if(!$tagData){
				trigger_error('Tag creation error', E_USER_WARNING);
				return false;
			}

		}

		return $tagData;
	}

	function getPopularTags($limit = 20, $maxSize = 40, $minSize = 8){
		$tags = $this->_db->select("SELECT tm.tag_id, t.url, t.name, count(tm.entry_id) AS cnt
			FROM ?# tm
			LEFT JOIN ?# t ON t.id = tm.tag_id
			{WHERE filter_id = ?d}
			GROUP BY tm.tag_id
			ORDER BY cnt DESC LIMIT ".$limit,
		$this->_entryName.$this->_tagsMapSuffix, $this->_tagsTable, $this->_filterID);

		// есть у нас вообще теги?
		if(!$tags) return array();

		$max = $min = 0;
		foreach($tags as $n=>$v){
			$max = max($max, $v['cnt']);
			$min = min($min, $v['cnt']);
		}
		$k = $maxSize / log($max - $min + 1);
		foreach($tags as $n=>$v) $tags[$n]['logSize'] = (int)max($minSize, ( log($v['cnt']+1 - $min) * $k ));
		shuffle($tags);
		return $tags;
	}
	
	function getAllPopularTags($limit = 20, $maxSize = 40, $minSize = 8){
		$tags = $this->_db->select("SELECT t.id, t.url, t.name, t.used AS cnt
			FROM ?# t
			WHERE used > 0
			LIMIT ".$limit,
		$this->_tagsTable);

		// есть у нас вообще теги?
		if(!$tags) return array();

		$max = $min = 0;
		foreach($tags as $n=>$v){
			$max = max($max, $v['cnt']);
			$min = min($min, $v['cnt']);
		}
		$k = $maxSize / log($max - $min + 1);
		foreach($tags as $n=>$v) $tags[$n]['logSize'] = (int)max($minSize, ( log($v['cnt']+1 - $min) * $k ));
		shuffle($tags);
		return $tags;
	}
	
	

	function getTagsList(){
		$maxUsed = $this->_db->selectCell('SELECT MAX(used) FROM ?#', $this->_tagsTable);
		if(!$maxUsed) $maxUsed = 1;
		$list = $this->_db->select('SELECT *, used/?d as percent FROM ?# ORDER BY name', $maxUsed, $this->_tagsTable);
		return $list;
	}
	
	function filterTags($f){
		$f = trim($f);
		return $this->_db->select('SELECT * FROM ?# {WHERE name LIKE ?} ORDER BY name', $this->_tagsTable, $f ? '%'.$f.'%' : DBSIMPLE_SKIP);
	}

	/*
	Бля! Я щас смарю Преводсходство Борна и сцуко сопережываю.
	Метт деймон! МАЧИ КОЗЛОВ!
	*/

	function name2url($tag){
		return $this->rURL->URLize($tag);
	}

	//~ Сцуко, да он их убивает!!!1
}