<?php

/**

rBlog - Класс для работы с блогами.

Переработанный под bl-engine2


**/

require_once('rlib/rTable.class.php');
require_once('rlib/rURLs.class.php');
if (!class_exists('tags'))
    require_once('rlib/tags.class.php');
if (!class_exists('simplePager'))
    require_once('rlib/simplePager.class.php');

require_once('rlib/simpleRSS.class.php');

require_once('rlib/comments.class.php');

require_once('rlib/Imager.php');

class rBlog{

	/** ссылка на объект rSite. */
	public $app = null;
	public $db = null;

	public $comments = null;

	public $postsDB = null;
	public $blogsDB = null;

	protected $settings = array(); // массив с настройками
	
	protected $mainpicSizes = array(
		'base' => array('prefix' => '', 'w' => 500, 'h' => 400),
		'original' => array('prefix' => 'original_', 'w' => 1280, 'h' => 1024, 'assign_as_next' => true),
		'mini' => array('prefix' => 'mini_', 'w' => 300, 'h' => 300, 'assign_as_next' => true),
		'thumb' => array('prefix' => 'thumb_', 'w' => 150, 'h' => 150),
		'square' => array('prefix' => 'sq_', 'w' => 100, 'h' => 100, 'method' => 'crop'),
		'minisquare' => array('prefix' => 'sqm_', 'w' => 50, 'h' => 50, 'method' => 'crop'),
	
	);

	protected $blogID = 0;
	protected $userID = 0;

	protected $URL = null;

	protected $tagsObj = null;
	
	protected $prefix = 'blog';
	
	protected $hasModeration = false;
	
	/**
	* __construct
	* @param mixed $site
	* @param mixed $settings
	* @return void
	*/
	function __construct($app, $settings = array()){
		$this->app = $app;
		$this->db = $app->db;
		$this->assignDefaultSettings();
		$this->setSettings($settings);

		$this->postsDB = new rTableClass($this->app->db, $this->settings['posts_table']);
		$this->blogsDB = new rTableClass($this->app->db, $this->settings['blogs_table']);
		$this->URL = new rURLs(false);
		$this->tagsObj = new Tags($this->db, 'blog');
		if(!empty($this->settings['tags_table']))
			$this->tagsObj->setTagsTable($this->settings['tags_table']);
	}

	/**
	* setMainpicSizes
	* @param mixed $sizes
	* @param mixed $section
	* @param mixed $autoCreate
	* @return void
	*/
	public function setMainpicSizes($sizes, $section = false, $autoCreate = false){
		if($section){
			if(!empty($this->mainpicSizes[$section]) || $autoCreate)
				$this->mainpicSizes[$section] = $sizes;
		}else{
			$this->mainpicSizes = $sizes;
		}
	}

	/**
	* постим
	* @param mixed $data
	* @return mixed
	*/
	public function post($data){

		if(!empty($data['blog_id'])) $this->blogID = (int)$data['blog_id'];
		
		if(empty($data['title']))
			$data['title'] = date('d-m-Y H:i');

		$post_data = array(
			'title' => htmlspecialchars($data['title']),
			'url' => $this->getURL($data['title']),
			'blog_id' => $this->blogID,
			'owner_id' => $this->app->user->getID(),
			'dateadd' => time(),
			'datepost' => time(),
			'lastmodified' => time(),
			'preview' => $data['preview'],
			'text' => $this->process_text($data['text']),
		);
		if(strip_tags($data['preview'])) $post_data['have_cut'] = 1;
		
		if(isset($data['visible']))
			$post_data['visible'] = (int)$data['visible'];
		
		$post_data['allow_comments'] = isset($data['allow_comments']) ? (int)$data['allow_comments'] : 1;
		if(!empty($data['disable_comments'])) $post_data['allow_comments'] = 0;
		
		if(isset($data['source_url'])) $post_data['source_url'] = trim($data['source_url']);
		
		if(!empty($data['video_link'])){
			if($r = $this->parseVideoLink($data['video_link']))
				$post_data += $r;
			$post_data['video_link'] = $data['video_link'];
		}

		foreach($this->settings['customFields'] as $postKey => $dbKey){
			if(is_numeric($postKey)) $postKey = $dbKey;
			if(isset($data[$postKey])) $post_data[$dbKey] = $data[$postKey];
		}

		
		if(!empty($data['status']) && ($data['status'] != 'draft'))
			$post_data['status'] = 'posted';
		else{
			$post_data['status'] = 'draft';
		}

		if(isset($data['datepost'])){
			if(is_numeric($data['datepost'])){
				$post_data['datepost'] = (int)$data['datepost'];
			}else{
				$post_data['datepost'] = strtotime($data['datepost']);
			}
			if($post_data['datepost'] > time()) $post_data['status'] = 'deferred';
		}
		
		if($this->hasModeration) $post_data['status'] = 'in_moderation';


		$postID = $this->postsDB->add($post_data);


        if(!empty($data['tags'])){
            $this->tagsObj->setEntryID($postID);


			$this->updatePost($postID, array(
				'tags_cache' => $this->tagsObj->addTags($data['tags'], $post_data['owner_id'])
			));
			$this->tagsObj->setEntryID(0);
        }

        $this->blogsDB->inc('posts', $this->blogID);



		return new rBlogPost($this, $postID);
	}
	
	/**
	* РЕДАКТИРУЕМ ПОСТ
	* @param mixed $postID
	* @param mixed $data
	* @return mixed
	*/
	function editPost($postID, $data){
		
		$post = $this->getByID($postID);
		
		$post_data = array(
			'title' => htmlspecialchars($data['title']),
			'lastmodified' => time(),
			'text' => $data['text']
		);
		
		if(isset($data['visible']))
			$post_data['visible'] = (int)$data['visible'];

		if(!empty($data['blog_id'])){
			if($blogI = $this->selectBlog((int)$data['blog_id']))
				$post_data['blog_id'] = $blogI['id'];
		}
		
		if(isset($data['video_link'])){
			if($r = $this->parseVideoLink($data['video_link']))
				$post_data += $r;
			else
				$post_data += array('video_type' => '', 'video_id' => '');
			$post_data['video_link'] = $data['video_link'];
		}

		foreach($this->settings['customFields'] as $postKey => $dbKey){
			if(is_numeric($postKey)) $postKey = $dbKey;
			if(isset($data[$postKey])) $post_data[$dbKey] = $data[$postKey];
		}

		$post_data['allow_comments'] = isset($data['allow_comments']) ? (int)$data['allow_comments'] : 1;
		if(!empty($data['disable_comments'])) $post_data['allow_comments'] = 0;
		
		if(isset($data['source_url'])) $post_data['source_url'] = trim($data['source_url']);
		
		$cutPos = strpos($data['text'], $this->getSetting('cutTag'));
		if($cutPos !== FALSE){
			/*// есть кат. растовошиваем тексты
			$data['text'] = preg_replace('~<[pdiv]+[^>]*>\s*'.preg_quote($this->getSetting('cutTag'), '~').'\s*</[pdiv]+>~', '', $data['text']);
			$post_data['text'] = $this->process_text(rtrim(substr($data['text'], 0, $cutPos), ' <'));
			$post_data['full_text'] = $this->process_text(str_replace($this->getSetting('cutTag'), '', $data['text']));
			$post_data['have_cut'] = 1;*/
		}else{
			// без ката
			
			//print_r(array_map('htmlspecialchars', $post_data)); exit;
			
			$post_data['text'] = $this->process_text($data['text']);
		}
		
		if(!empty($data['preview'])){
			$data['preview'] = preg_replace('~^<[^>]+>(\s*|<br[^>]+>|&nbsp;)*</[^>]+>$~i', '', trim($data['preview']));
			
			if($data['preview']){			
				$post_data['preview'] = $this->process_text($data['preview']);
				$post_data['have_cut'] = 1;
			}
		}
		
		//$post_data['status'] = 'posted';
		if(!empty($data['status']))
			$post_data['status'] = $data['status'];

		if(isset($data['datepost'])){
			if(is_numeric($data['datepost'])){
				$post_data['datepost'] = (int)$data['datepost'];
			}else{
				$post_data['datepost'] = strtotime($data['datepost']);
			}
			if($post_data['datepost'] > time()) $post_data['status'] = 'deferred';
		}

        if(isset($data['tags']) && !$this->tagsObj->isTagsEq($data['tags'], $post['tags_cache'])){	
            $this->tagsObj->setEntryID($postID);
			$this->tagsObj->clear();
			$post_data['tags_cache'] = $this->tagsObj->addTags($data['tags'], $this->app->user->getID());
        }

		$this->postsDB->put($postID, $post_data);

		return new rBlogPost($this, $postID);
	}
	
	/**
	* parseVideoLink
	* @param mixed $link
	* @return bool
	*/
	public function parseVideoLink($link){
		if(strstr($link, 'youtube') !== FALSE){
			if(preg_match('~v=([^&#]+)~', $link, $m)){
				return array(
					'video_type' => 'youtube',
					'video_id' => $m[1]
				);
			}
		}
		
		return false;
	}

	/**
	* updatePost
	* @param mixed $postID
	* @param mixed $values
	* @return void
	*/
	function updatePost($postID, $values){
		if(!is_array($values)) return false;
		$values['lastmodified'] = time();
		$this->postsDB->put($postID, $values);
	}
	
	/**
	* movePost2Blog
	* @param mixed $post
	* @param integer $newBlogID
	* @return mixed
	*/
	public function movePost2Blog($post, $newBlogID = 0){
		
		// Если $post не массив с данными поста, то это наверняка ID поста, и мы получаем массив сами
		if(!is_array($post))
			$post = $this->getByID($post);
		
		if(empty($post['id']))
			return false;
		
		// Если новый блог не задан, выбираем первый попавшийся
		if(!$newBlogID)
			if(!$newBlogID = $this->db->selectCell('SELECT id FROM ?# LIMIT 1', $this->blogsDB->table))
				return false;
		
		// Апдейтим количества постов
		$this->db->query('UPDATE ?# SET posts = posts - 1 WHERE id = ?d', $this->blogsDB->table, $post['blog_id']);
		$this->db->query('UPDATE ?# SET posts = posts + 1 WHERE id = ?d', $this->blogsDB->table, $newBlogID);
		
		// Апдейтим сам пост
		$this->updatePost($post['id'], array(
			'blog_id' => $newBlogID
		));
		
		return $newBlogID;
	}

	/**
	* process_text
	* @param mixed $text
	* @return mixed
	*/
	function process_text($text){
		if($this->getSetting('skipTextCheck')) return $text;

		if($parserName = $this->getSetting('textParserClass')){
			$parser = new $parserName;
			return $parser->parse($text);
		}else{
			
			$text = htmlspecialchars($text);
			$text = str_replace(' - ', ' &mdash; ', $text);
			$text = preg_replace('/\r?\n/', '<br>', $text);
			return $text;
		}

		return $text; // на всякий пожарный
	}

	/**
	* Получить пост по его ID
	* @param integer $postID
	* @return mixed
	*/
	function getByID($postID){
		$post = $this->db->selectRow($this->getSetting('selectPosts').' WHERE p.id = ?d', $postID);
		$post = $this->proceedPost($post);
		return $post;
	}

	/**
	* Получить пост по его URL
	* @param string $url
	* @return mixed
	*/
	function getByURL($url){
		$post = $this->db->selectRow($this->getSetting('selectPosts').' WHERE p.url = ?', $url);
		$post = $this->proceedPost($post);
		return $post;
	}


	/**
	* Добавляет визит к посту
	* @param mixed $id
	* @param integer $visitID
	* @return void
	*/
	function viewPost($id, $visitID = 0){
		@session_start();
		if(isset($_SESSION['last_blog_id']) && ($_SESSION['last_blog_id'] == $id)) return false;

		$this->db->query('UPDATE ?# SET views = views + 1{, ref_clicks = ref_clicks + ?d} WHERE id = ?d', $this->postsDB->table,
		empty($visitID) ? DBSIMPLE_SKIP : 1
		, $id);
		$_SESSION['last_blog_id'] = $id;
		session_write_close();
		
		if($visitID)
			$this->db->query('INSERT INTO ?# SET ?a 
				ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)', 
			$this->settings['posts_visits_table'],
			array(
				'visit_id' => $visitID,
				'item_id' => $id
		));
	}

	/**
	* getReferers
	* @param mixed $id
	* @return void
	*/
	function getReferers($id){
		return $this->db->select('SELECT bv.source_id, bs.*
			FROM ?# bv
			LEFT JOIN ?# bs ON bs.id = bv.source_id
			WHERE bv.post_id = ?d
			GROUP BY bv.source_id
			ORDER BY bs.total_visits DESC',
			$this->settings['ref_visits'], $this->settings['ref_source'], $id);
	}

	/**
	* getLastPost
	* @return mixed
	*/
	function getLastPost(){
		$post = $this->db->selectRow($this->getSetting('selectPosts').'
			WHERE p.status = "posted" AND visible = 1{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d}
			ORDER BY p.datepost DESC LIMIT 1',
			$this->getSetting('ratingLimit') !== false ? $this->getSetting('ratingLimit') : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit') !== false ? $this->getSetting('ratingTopLimit') : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP
		);
		$post = $this->proceedPost($post);
		return $post;
	}

	/**
	* proceedPost
	* @param mixed $post
	* @param string $picPrefix
	* @return mixed
	*/
	function proceedPost($post, $picPrefix = ''){
		if(!$post) return false;
		if(!empty($post['tags_cache'])) $post['tags_cache'] = @unserialize($post['tags_cache']);
		$this->getPostURL($post);
		$this->getPostMainpic($post, $picPrefix);
		return $post;
	}

	/**
	* proceedPost
	* @param mixed $post
	* @return void
	*/
	public function getPostURL(&$post){
		$post['post_url'] = ROOT_URL;
		
		if(defined('SINGLE_BLOG_MODE') && SINGLE_BLOG_MODE ){
			$post['post_url'] .= $post['id'] . '-';
		}elseif(!empty($post['blog_url'])) 
			$post['post_url'] .= $post['blog_url'] .'/';
		$post['post_url'] .= $post['url'] . '.html';
	}
	
	/** КАРТИНКИ *******************************/
	
	/**
	* proceedPost
	* @param mixed $post
	* @param string $prefix
	* @return void
	*/
	public function getPostMainpic(&$post, $prefix = ''){
		if(empty($post['has_mainpic']) || empty($post['mainpic']))
			return false;
		$post['mainpic_url'] = USERS_URL . $this->getMainpicDir($post['id']) .'/'.$prefix.$post['mainpic'];
	}
	
	/**
	* getMainpicDir
	* @param mixed $id
	* @return mixed
	* @todo REMOVE
	*/
	public function getMainpicDir($id){
		return 'pics/' . ceil($id / 1000) .'/' . $id ;
	}

	/**
	* removeMainPic
	* @param mixed $id
	* @return bool
	*/
	public function removeMainPic($id){
		$post = $this->getByID($id);
		if(!$post) return false;
		
		if(!$post['mainpic']) return true;
		
		// убираем флажок из базы
		$this->updatePost($id, array(
			'mainpic' => '', 'has_mainpic' => 0
		));		
		
		// физически удаляем картинки
		$dir = $this->getMainpicDir($id);
		foreach($this->mainpicSizes as $size){
			@unlink(USERS_PATH.'/'.$dir.'/'.(@$size['prefix']).$post['mainpic']);
		}
		
		return true;
	}
	
	/**
	* загрузка главного рисунка
	* @param mixed $id
	* @param mixed $url
	* @param mixed $pic
	* @param string $oldPicURL
	* @return mixed
	*/
	public function uploadMainPic($id, $url, $pic, $oldPicURL = ''){
		$imager = new Imager;
		
		if(!$pic || !file_exists($pic)) return false;
		
		if(!$imager->setImage($pic)){
			return false;
		}
		
		$dir = USERS_PATH.'/'.$this->getMainpicDir($id);
		if(!$imager->prepareDir($dir)){
			return false;
		}
		
		$url .= '-'.substr(uniqid(''), -4);
		
		if(!$base_name = $imager->packetResize($dir, $this->mainpicSizes, $url, $oldPicURL)){
			return false;
		}
		
		@unlink($pic);
		
		if(@filesize($dir.'/'.$base_name) == @filesize($dir.'/original_'.$base_name))
			@unlink($dir.'/original_'.$base_name);
		
		$this->updatePost($id, array(
			'has_mainpic' => 1, 'mainpic' => $base_name
		));
		
		return $base_name;
	}
	
	/**
	* asGallery
	* @param mixed $page
	* @return void
	*/
	public function asGallery($page){
		$total = 9;
		$pager = new simplePager($this->getSetting('gallery.onPage', 36), $page);
		$r['posts'] = $this->db->selectPage($total, 'SELECT p.url, p.id, p.mainpic, p.title, p.tags_cache, p.has_mainpic
			FROM ?# p
			WHERE p.has_mainpic = 1 AND p.status = "posted"
			ORDER BY p.datepost DESC 
			LIMIT '.$pager->getMySQLLimit(),
				$this->getSetting('posts_table'));
		foreach($r['posts'] as $n=>$v){
			$this->getPostURL($r['posts'][$n]);
			$this->getPostMainpic($r['posts'][$n], 'sq_');
			$r['posts'][$n]['tags'] = unserialize($v['tags_cache']);
		}
		$r['pages'] = $pager->getPagesStr($total);
		return $r;
	}
	
	/**
	* selectPosts
	* @param integer $page
	* @param boolean $main // если false то флаг visible не учитывается. На главной надо вызыват с $main=true и у которых visible=0 не будут показаны
	* @return mixed
	*/
	function selectPosts($page = 1, $main = false){
		$total = 0;
		$p = new simplePager($this->getSetting('onPage'), $page);
		$r['posts'] = $this->db->selectPage($total, $this->getSetting('selectPosts').' WHERE p.status = "posted"{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d}{ AND p.visible = ?d} ORDER BY p.datepost DESC LIMIT '.$p->getMySQLLimit(),
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit', 0) : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit', 0) : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP,
			$main ? 1 : DBSIMPLE_SKIP
		);
		
		foreach($r['posts'] as $n=>$v){
			$r['posts'][$n] = $this->proceedPost($v);
			$r['posts'][$n] = rBlogPost::proceedPost($v);
		}
		
		$r['total'] = $total;
		$r['pages'] = $p->getPagesStr($total);

		return $r;
	}


	/**
	* Выбираем несколько последних постов
	* @param bool $limit
	* @param string $picPrefix
	* @return mixed
	*/
	function selectLastPosts($limit = false, $picPrefix = ''){
		
		if(!$limit)
			$limit = $this->getSetting('onPage');
		
		$r = $this->db->select($this->getSetting('selectPosts').' WHERE p.status = "posted" AND visible = 1{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d} ORDER BY p.datepost DESC LIMIT '.$limit,
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit', 0) : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit', 0) : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP
		);
		
		foreach($r as $n=>$v){
			$r[$n] = $this->proceedPost($v, $picPrefix);
		}

		return $r;
	}

	/**
	* searchPosts
	* @param mixed $q
	* @param integer $page
	* @return mixed
	*/
	function searchPosts($q, $page = 1){
		$total = 0;
		$p = new simplePager($this->getSetting('onPage'), $page);
		$r['posts'] = $this->db->selectPage($total, $this->getSetting('selectPosts').' WHERE 1{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d} AND (p.title LIKE ? OR p.full_text LIKE ?) ORDER BY p.datepost DESC LIMIT '.$p->getMySQLLimit(),
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit', 0) : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit', 0) : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP,
			"%$q%", "%$q%"
		);
		
		foreach($r['posts'] as $n=>$v){
			$r['posts'][$n] = $this->proceedPost($v);
		}
		
		$r['total'] = $total;
		$r['pages'] = $p->getPagesStr($total);

		return $r;
	}

	/**
	* TAGS
	* @param mixed $tag
	* @param integer $page
	* @return mixed
	*/
	function selectByTag($tag, $page = 1){
        $tagI = $this->tagsObj->getTagData($tag);
        if(!$tagI) return false;

		$total = 0;
		$p = new simplePager($this->getSetting('onPage'), $page);
		$r['posts'] = $this->db->selectPage($total, $this->getSetting('selectByTag').' WHERE tmap.tag_id = ?d AND  p.status = "posted"{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d} ORDER BY p.datepost DESC LIMIT '.$p->getMySQLLimit(),
			$tagI['id'],
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit', false) : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit', false) : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP
		);
		foreach($r['posts'] as $n=>$v){
			$r['posts'][$n] = $this->proceedPost($v);
		}
		$r['total'] = $total;
		$r['pages'] = $p->getPagesStr($total);
		$r['tagData'] = $tagI;

		return $r;
	}

	/**
	* tagCloud
	* @param integer $limit
	* @param integer $maxSize
	* @param integer $minSize
	* @return mixed
	*/
	function tagCloud($limit = 30, $maxSize = 40, $minSize = 8){
		if($this->blogID) $this->tagsObj->setFilterID($this->blogID);
		return $this->tagsObj->getPopularTags($limit, $maxSize, $minSize);
	}
	
	/**
	* tagCloud
	* @param string $filter
	* @return mixed
	*/
	public function getTagsList($filter = ''){
		if($this->blogID) $this->tagsObj->setFilterID($this->blogID);
		return $this->tagsObj->filterTags($filter);
	}
	
	/**
	* tagCloud
	* @param integer $page
	* @return mixed
	*/
	function selectFavorited($page = 1){

		$total = 0;
		$p = new simplePager($this->getSetting('onPage'), $page);
		$r['posts'] = $this->db->selectPage($total, $this->getSetting('selectFavorited').' WHERE fav.user_id = ?d AND p.status = "posted"{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d} ORDER BY p.datepost DESC LIMIT '.$p->getMySQLLimit(),
			$this->userID,
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit') : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit') : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP

		);

		foreach($r['posts'] as $n=>$v){
			$r['posts'][$n] = $this->proceedPost($v);
		}

		$r['total'] = $total;
		$r['pages'] = $p->getPagesStr($total);

		return $r;
	}

	/**
	* Получить следующий пост
	* @param mixed $postID
	* @return mixed
	*/
    function getNextPost($postID){
        return $this->getNextPrev($postID, '>');
    }

	/**
	* Получить предыдущий пост
	* @param mixed $postID
	* @return mixed
	*/
    function getPrevPost($postID){
       return $this->getNextPrev($postID, '<');
    }

	/**
	* getNextPrev
	* @param mixed $postID
	* @param mixed $dir
	* @return mixed
	*/
	function getNextPrev($postID, $dir){
		if($dir != '>') $dir = '<';
		$ordr = $dir == '<' ? 'DESC' : '';


        $post = $this->db->selectRow(
			$this->getSetting('selectPosts'). " WHERE p.id $dir ?d AND p.status = 'posted'{ AND p.rating >= ?}{ AND p.rating <= ?}{ AND p.blog_id = ?d}{ AND p.owner_id = ?d} ORDER BY p.datepost $ordr LIMIT 1", $postID,
			$this->getSetting('ratingLimit', false) !== false ? $this->getSetting('ratingLimit') : DBSIMPLE_SKIP,
			$this->getSetting('ratingTopLimit', false) !== false ? $this->getSetting('ratingTopLimit') : DBSIMPLE_SKIP,
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP,
			$this->userID ? $this->userID : DBSIMPLE_SKIP
		);
        if (!$post)
            return false;
		$post = $this->proceedPost($post);
        return ($post);
	}

	/**
	* DELETE POST
	* @param mixed $postID
	* @param bool $checkOwner
	* @return bool
	*/
	function deletePost($postID, $checkOwner = false){
		$post = $this->getByID($postID);
		if(!$post) return false;
		if( $checkOwner && ($post['owner_id'] != $this->app->user->getID())) return false;

		if(!empty($post['tags_cache'])){
			$this->tagsObj->setEntryID($postID);
			$this->tagsObj->clear();
			$this->tagsObj->setEntryID(0);
		}

		$this->postsDB->remove($postID);
		$this->blogsDB->dec('posts', $post['blog_id']);

		return true;
	}


	/**
	* Ищем свободный URL для поста
	* @param mixed $start
	* @param integer $curID
	* @return mixed
	*/
	function searchURL($start, $curID = 0){
        $end = $start;
        $i = 1;
        if(!$curID) $curID = DBSIMPLE_SKIP;
        $blogID = $this->blogID;
        if(!$blogID) $blogID = DBSIMPLE_SKIP;

        while( $this->db->selectCell('SELECT id FROM ?#
                WHERE url = ? {AND blog_id = ?d} {AND owner_id =?d} {AND id <> ?d} LIMIT 1',
                $this->settings['posts_table'], $end, $blogID,
                $this->userID ? $this->userID : DBSIMPLE_SKIP,
                $curID ) ){
                    $end = $start . '-' . ($i++);
        } // while
        return $end;
    }

	/**
	* getURL
	* @param mixed $title
	* @return mixed
	*/
	function getURL($title){
		$url = $this->URL->URLize($title);
		if(!$url || $url == '-') $url = str_replace('.', '-', uniqid('post', true));
		return $this->searchURL($url);
	}

	/**
	* Блоги
	*/

	/**
	* Получаем список блогов
	* @param mixed $listID ID списка
	* @return
	*/
	function getBlogsList($listID = null){
		if($listID === NULL){
			$where = '';
		}else{
			$where = 'list_id = ' . ((int)$listID);
		}
		return @$this->blogsDB->getList($where);
	}

	function selectBlog($url){
		$blog = is_numeric($url) ? $this->blogsDB->get($url) : $this->blogsDB->get(array('url'=>$url));
		if(!$blog){
			$this->blogID = 0;
			return false;
		}
		$this->blogID = $blog['id'];
		$this->tagsObj->setFilterID($blog['id']);
		return $blog;
	}
	
	/** удаляем блог
	* пока только пустые блоги
	* @param mixed $id
	* @return void
	*/
	public function deleteBlog($id){
		$this->blogsDB->remove($id);
	}

	/**
	* Юзеры
	* @param mixed $id
	* @return void
	*/
	function selectUser($id){
		$this->userID = (int)$id;
	}

	/**
	* votePost
	* @param mixed $postID
	* @param mixed $vote
	* @param integer $uid
	* @return mixed
	*/
	function votePost($postID, $vote, $uid = 0){
		if(!$uid) $uid = $this->app->user->getID();
		if(!$uid) return false;
		// if(abs($vote) > 1) $vote = 1;
		

		$myVote = $this->db->selectCell('SELECT vote FROM ?# WHERE post_id = ?d AND user_id = ?d', $this->getSetting('votes_table'), $postID, $uid);
		if($myVote){
			if($myVote == $vote){
				$vote = 0;
			}else{
				$this->db->query('UPDATE ?# SET vote = ?d, dateadd = ?d WHERE post_id = ?d AND user_id = ?d', $this->getSetting('votes_table'),
					$vote, time(), $postID, $uid);
				$vote = $vote - $myVote;
				$vc = 0;
			}
		}else{
			$this->db->query('INSERT INTO ?# SET ?a', $this->getSetting('votes_table'), array(
				'vote' => $vote, 'dateadd' => time(),
				'post_id' => $postID, 'user_id' => $uid
			));
			$vc = 1;
		}
		if($vote)$this->db->query('UPDATE ?# SET rating = rating + ?d{, rating_count = rating_count + ?d} WHERE id = ?d',
			$this->getSetting('posts_table'), $vote, $vc ? $vc : DBSIMPLE_SKIP, $postID);

		$data = $this->db->selectRow('SELECT rating, owner_id FROM ?# WHERE id = ?d', $this->getSetting('posts_table'), $postID);
		$data['voted'] = $vote;
		return $data;
	}

	/**
	* Избранное
	* @param mixed $postID
	* @return mixed
	*/
	function toggleFavorite($postID){
		if(!$this->app->user->authed()) return false;
		$uid = $this->app->user->getID();
		$inFav = $this->db->selectCell('SELECT * FROM ?# WHERE post_id = ?d AND user_id = ?d', $this->getSetting('favorites_table'), $postID, $uid);

		if($inFav){
			$this->db->query('DELETE FROM ?# WHERE post_id = ?d AND user_id = ?d', $this->getSetting('favorites_table'), $postID, $uid);
			return -1;
		}else{
			$this->db->query('INSERT INTO ?# SET ?a', $this->getSetting('favorites_table'), array(
				'post_id' => $postID,
				'user_id' => $uid,
				'dateadd' => time()
			));
			return 1;
		}

		return false; // это если девид блейн пошутит
	}
	
	/**
	* Настройки
	* @return void
	*/
	function assignDefaultSettings(){
		$this->settings = array(
			'cutTag' => '<cut>',
			'onPage' => 20,
			'customFields' => array(),
			'ratingLimit' => false,
			'ratingTopLimit' => false,
			'posts_table' => 'blog_posts',
			'blogs_table' => 'blogs',
			'votes_table' => 'blog_posts_votes',
			'favorites_table' => 'blog_favorites',
			'ref_source' => 'blog_sources',
			'ref_visits' => 'blog_visits',
			'posts_visits_table' => 'blog_posts_visits_map',
			'comments_tables_prefix' => 'blog',
			'comments_class' => 'comments',
			'minPostVote' => -1,
			'maxPostVote' => 1,
			'skipTextCheck' => false,
			'textParserClass' => null,
			'selectPosts' =>
				'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, fav.dateadd AS in_favorites,
					img.filename as mainpic_filename
					FROM blog_posts p
					LEFT JOIN blogs b ON b.id = p.blog_id
					LEFT JOIN users u ON u.id = p.owner_id
					LEFT JOIN blog_images img ON img.id = p.mainpic_id
					LEFT JOIN blog_favorites fav ON (fav.post_id = p.id AND fav.user_id = '.$this->app->user->getID().')',

			'selectByTag' =>
				'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, fav.dateadd AS in_favorites
					FROM blog_tags_map tmap
					LEFT JOIN blog_posts p ON p.id = tmap.entry_id
					LEFT JOIN blogs b ON b.id = p.blog_id
					LEFT JOIN users u ON u.id = p.owner_id
					LEFT JOIN blog_favorites fav ON (fav.post_id = p.id AND fav.user_id = '.$this->app->user->getID().')',

			'selectFavorited' =>
				'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, 1 AS in_favorites
					FROM blog_favorites fav
					LEFT JOIN blog_posts p ON p.id = fav.post_id
					LEFT JOIN blogs b ON b.id = p.blog_id
					LEFT JOIN users u ON u.id = p.owner_id',
		);
	}

	/**
	* Переформирует SELECTы для всех нужных выборок.
	* @return void
	*/
	protected function _reformSelects(){
		$this->settings['selectPosts'] =
			'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, fav.dateadd AS in_favorites
				FROM `'.$this->settings['posts_table'].'` p
				LEFT JOIN `'.$this->settings['blogs_table'].'` b ON b.id = p.blog_id
				LEFT JOIN users u ON u.id = p.owner_id
				LEFT JOIN `'.$this->settings['favorites_table'].'` fav ON (fav.post_id = p.id AND fav.user_id = '.$this->app->user->getID().')';

		$this->settings['selectByTag'] =
			'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, fav.dateadd AS in_favorites
				FROM `'.$this->settings['blog_tags_map'].'` tmap
				LEFT JOIN `'.$this->settings['posts_table'].'` p ON p.id = tmap.entry_id
				LEFT JOIN `'.$this->settings['blogs_table'].'` b ON b.id = p.blog_id
				LEFT JOIN users u ON u.id = p.owner_id
				LEFT JOIN `'.$this->settings['favorites_table'].'` fav ON (fav.post_id = p.id AND fav.user_id = '.$this->app->user->getID().')';

		$this->settings['selectFavorited'] =
			'SELECT p.*, b.name AS blog_name, b.url AS blog_url, u.full_name AS author_name, 1 AS in_favorites
				FROM `'.$this->settings['favorites_table'].'` fav
				LEFT JOIN `'.$this->settings['posts_table'].'` p ON p.id = fav.post_id
				LEFT JOIN `'.$this->settings['blogs_table'].'` b ON b.id = p.blog_id
				LEFT JOIN users u ON u.id = p.owner_id';
	}



	/**
	* Устанавливает префикс для всех таблиц.
	* Например, если префикс «blog», то таблицы будут именоваться «blogs», «blog_posts», итд
	* @param string $prefix Префикс
	* @return void
	*/
	function setTablesPrefix($prefix){
		$this->prefix = $prefix;
		$this->settings['posts_table'] = $prefix . '_posts';
		$this->settings['blogs_table'] = $prefix . 's';
		$this->settings['votes_table'] = $prefix . '_posts_votes';
		$this->settings['favorites_table'] = $prefix . '_favorites';
		$this->settings['ref_source'] = $prefix . '_sources';
		$this->settings['ref_visits'] = $prefix . '_visits';
		$this->settings['posts_visits_table'] = $prefix . '_posts_visits_map';
		$this->settings['blog_tags_map'] = $prefix . '_tags_map';
		$this->settings['comments_tables_prefix'] = $prefix ;

		$this->blogsDB->setTable($prefix . 's');
		$this->postsDB->setTable($prefix . '_posts');

		$this->tagsObj->setEntryName($prefix);

		$this->_reformSelects();
	}
	
	/**
	* getTablesPrefix
	* @return mixed
	*/
	function getTablesPrefix(){
		return $this->prefix;
	}

	/**
	* setSetting
	* @param mixed $id
	* @param mixed $val
	* @return void
	*/
	function setSetting($id, $val){
		$this->settings[$id] = $val;
	}

	/**
	* setSettings
	* @param mixed $settings
	* @return void
	*/
	function setSettings($settings){
		$this->settings = array_merge($this->settings, $settings);
	}

	/**
	* getSettings
	* @return mixed
	*/
	function getSettings(){
		return $this->settings;
	}

	/**
	* getSetting
	* @param mixed $id
	* @param bool $default
	* @return mixed
	*/
	function getSetting($id, $default = false){
		return empty($this->settings[$id]) ? $default : $this->settings[$id];
	}


	/**
	* Дополнительные поля
	* @param mixed $name
	* @param string $db_name
	* @return void
	*/
	function addCustomField($name, $db_name = ''){
		if(!$db_name) $db_name = $name;
		$this->settings['customFields'][$name] = $db_name;
	}

	/**
	* setCustomFields
	* @param mixed $customFields
	* @return void
	*/
	function setCustomFields($customFields){
		$this->settings['customFields'] = (array)$customFields;
	}

	/**
	* clearCustomFieds
	* @param mixed $customFields
	* @return void
	*/
	function clearCustomFieds($customFields){
		$this->setCustomFields(array());
	}

	/**
	* getCustomFields
	* @return void
	*/
	function getCustomFields(){
		return $this->settings['customFields'];
	}


	/**
	* Работа с RSS
	* @param mixed $file
	* @param mixed $title
	* @param mixed $description
	* @param mixed $host
	* @return void
	*/
	function saveRSS($file, $title, $description, $host){
		$posts = $this->selectPosts();
		$rss = new simpleRSS($title, $host, $description, array('lastBuildDate' => date('r', time())));
		foreach($posts['posts'] as $p){
			$rss->addItem(
				$p['title'],
				$p['post_url'],
				(empty($p['mainpic']) ? '' : '<div align=center><a href="'.$p['post_url'].'"><img src="'.$host.substr(USERS_URL, 1).'pics/'.ceil($p['id']/1000).'/'.$p['id'].'/'.$p['mainpic'].'" alt=""></a></div>').
				str_replace("\r", "", str_replace("\n"," ",$p['main_text'])),
				array('guid'=>$p['url'].'.html')
			);
		}
		$rss->create($file, '', 'utf-8');
	}

	/**
	* Получаем список последних постов из всех блогов
	* @param integer $limit Ограничение количества возвращаемых постов
	* @param mixed $listID Получает только блоги с заданным list_id. Если null - то все посты.
	* @return array список постов
	*/
	function getAllPosts($limit = 20, $listID = NULL){
		$limit = (int)$limit;
		$allLastPosts = $this->db->select('SELECT p.url, p.title, p.id, b.name as blog_name, b.url as blog_url, p.main_text, p.datepost
			FROM ?# p
			LEFT JOIN ?# b ON b.id = p.blog_id
			WHERE p.status = "posted"{ AND b.list_id = ?d}
			ORDER BY p.datepost DESC
			LIMIT '.$limit,
				$this->settings['posts_table'], $this->settings['blogs_table'],
				$listID === NULL ? DBSIMPLE_SKIP : $listID);
		return $allLastPosts;
	}
	
	/**
	* getLastCommented
	* @param integer $limit
	* @return array список постов
	*/
	function getLastCommented($limit = 20){
		$limit = (int)$limit;
		$allLastPosts = $this->db->select('SELECT p.url, p.title, p.id, b.name as blog_name, b.url as blog_url, p.last_comment, p.comments
			FROM ?# p
			LEFT JOIN ?# b ON b.id = p.blog_id
			WHERE p.status = "posted"{ AND p.blog_id = ?d}
			ORDER BY p.last_comment DESC
			LIMIT '.$limit,
				$this->settings['posts_table'], $this->settings['blogs_table'],
				$this->blogID ? $this->blogID : DBSIMPLE_SKIP
				);
		return $allLastPosts;		
	}

	/*** КОММЕНТАРИИ ******************************************************************************/
	
	/**
	* initComments
	* @return bool
	*/
	public function initComments(){
		if(is_object($this->comments)) return true;
		
		$this->comments = new $this->settings['comments_class']($this->db,
			$this->settings['comments_tables_prefix']);

		$this->comments->enableEntryStats($this->settings['posts_table']);


		$this->app->addJS('comments.js');
		$this->app->addCSS('comments/comments.css');

		return true;
	}

	/**
	* getComments
	* @param mixed $postID
	* @return mixed
	*/
	public function getComments($postID){
		if(!$this->initComments()) return false;
		$this->comments->setEntryID($postID);
		return $this->comments->getComments();
	}

	/**
	* addComment
	* @param mixed $postID
	* @param mixed $cData
	* @param mixed $userData
	* @return mixed
	*/
	public function addComment($postID, $cData, $userData){

	}


	/**
	* публикует все отложенные посты
	* @return mixed
	* теперь возвращает массив с ID опубликованых постов. Надо для публикации в Твитер, да и вообще полезно знать )
	*/
	public function publicDeferred(){
		$_posts =  $this->db->selectCol('SELECT id FROM ?# FORCE INDEX(datepost) WHERE status = "deferred" AND datepost <= ?d', $this->postsDB->table, time());
		if ($_posts) $this->db->query('UPDATE ?# FORCE INDEX(datepost) SET status = "posted" WHERE id IN (?a)', $this->postsDB->table, $_posts);
		return $_posts;
	}
	
	/**
	* listDeferred
	* @return mixed
	*/
	public function listDeferred(){
		$posts = $this->db->select('SELECT id, title, url, datepost FROM ?# WHERE status = "deferred" {AND blog_id = ?d} ORDER BY datepost', $this->postsDB->table, 
			$this->blogID ? $this->blogID : DBSIMPLE_SKIP);
		return $posts;
	}
	
	
	/**
	* Sitemap **************
	* @return mixed
	*/
	function getSitemap(){
		return $this->db->select('SELECT p.id, p.url, b.url as blog_url, p.lastmodified, p.last_comment 
			FROM ?# AS p
			LEFT JOIN ?# as b ON b.id = p.blog_id
		WHERE p.status = "posted"
		LIMIT 5000', 
			$this->settings['posts_table'], $this->settings['blogs_table']);
	}
	
	/**
	 * Получаем список последних постов из ЖЖ
	 * **/
	public function getPostsLJ($num = 10){
			
		include_once(ROOT.'/config/livejournal.blog.php');
		include_once("rlib/livejournal.class.php");
		
		$lj = new Livejournal(LJ_LOGIN, LJ_PASSW);
		return $lj->getPosts($num);
	}
	
	/**
	 * Кросс постинг постов в LiveJournal, активируется только при наличие файла livejournal.blog.php в папке config
	 * @param $post - новая инфа
	 * если будет ['postID'] - если добавить этот элемент, будет обновлять на удаленном сервере
	 * @param $postId  - тоже id локального поста
	 *  
	 * **/
	public function pos2LJ($post, $postId){

		if(isset($post['ljPost']) && isset($post['todo'])){
			
		include_once(ROOT.'/config/livejournal.blog.php');
		include_once("rlib/livejournal.class.php");
		

		if(isset($post['postID'])){ //если обновление поста добовляем id обновляемого поста 
			$lj_post_row = $this->db->selectRow("SELECT id, ext_id FROM blog_posts_ext WHERE type_id = 2 AND post_id = ?d", $postId);
			//$lj_post['itemid'] = $lj_post_row['ext_id'];
			if(!empty($lj_post_row)) $lj_post['itemid'] = $lj_post_row['ext_id'];

		}
		
		try{
			$lj = new Livejournal(LJ_LOGIN, LJ_PASSW);
			$blogItem = $this->getById($postId);
		//print_r($blogItem);
		//print_r($_POST);
		/**Если запись публикуется или обновляется*/
		if($post['todo'] == 'publish'){
			
			if(!isset($post['text'])) $post['text'] = $post['full_text'];
			
			$post['text'] = preg_replace("/href=\"\//i", "href=\"".SERVER_URL, $post['text'] );
			$post['text'] = preg_replace("/src=\"\//i", "src=\"".SERVER_URL, $post['text'] );
			
			/**Если у нашего поста есть main pic то добовяем его в начало поста*/
			if($blogItem['has_mainpic'] == 1){
				$post['text'] = "<img src='".SERVER_URL.USERS_URL."/".
					$this->getMainpicDir($postId) .'/'.
					$blogItem['mainpic']."'> ".$post['text'];
			}
			if(LJ_SHOW_SOURCE){
				$tmp_url = parse_url(SERVER_URL);
				$tmp_url = $tmp_url['host'];
				$post['text'] = $post['text']. "<br><a href='".rtrim(SERVER_URL, "/").$blogItem['post_url']."'>Отправлено из ".$tmp_url."</a>";
			}
			$myTime = time();
			if(!isset($post['is_datepost'])){
				$post['dp_time'] = date("G:i", $myTime);
				$post['dp_date'] = date("d.m.Y", $myTime);
			}
			
			$lj_time = explode(":", $post['dp_time']);
			$lj_date = explode(".", $post['dp_date']);
			$lj_post = array(
				'text' => $post['text'],
				'subj' => $post['title'],
				'year' => $lj_date[2],
				'mon'  => $lj_date[1],
				'day'  => $lj_date[0],
				'hour' => $lj_time[0],
				'min'  => $lj_time[1]
			);
				
			if(!empty($blogItem['tags_cache'])){
				$lj_post['tags'] = implode(", ",$blogItem['tags_cache']);
			}
				
			if(!empty($lj_post_row)) $lj_post['itemid'] = $lj_post_row['ext_id'];
				
				$post_date =  mktime($lj_post['hour'], $lj_post['min'], 0, $lj_post['mon'], $lj_post['day'], $lj_post['year']); 
				/**проверяем что у нас датой постинга*/
				
				if($post_date > $myTime && isset($lj_post['itemid'])){
					  $lj_post['security'] = 'private';
					 $lj_res = $lj->post($lj_post);
					
				}elseif($post_date <= $myTime){ /**Проверяем время постинга если оно меньше или равно текущего то публикуем*/
					$lj_res = $lj->post($lj_post);
				
				}	
			
				//print_r($lj_res); //== Array ( [itemid] => 104 [url] => http://****.livejournal.com/26682.html [anum] => 58 [ditemid] => 26682 ) 
				/**Вставка в blog_posts_ext для работы с кросспостингом*/
				if(isset($lj_res['itemid'])){
					$lj_post_arr = array('type_id'=> 2, 'ext_id'=> $lj_res['itemid'], 'post_id' => $postId);
				}
				if(isset($lj_res['ditemid'])){
					$lj_post_arr['url_id'] = $lj_res['ditemid'];
				}elseif(isset($lj_res['url'])){
					$lj_post_arr['url_id'] = parse_url($lj_res['url']);
					$lj_post_arr['url_id'] = preg_replace("|[^0-9]|i", "", $lj_post_arr['url_id']['path']);
				} 
				
				if(!isset($lj_post_row['ext_id']) && isset($lj_post_arr)){
					
					$this->db->query("INSERT INTO blog_posts_ext SET ?a", $lj_post_arr);

				}
				
				/**Конец - Вставка в blog_posts_ext для работы с кросспостингом*/

			/**Если запись удаляется*/
			}elseif($post['todo'] == 'delete' && isset($lj_post['itemid'])){
					$lj->delete($lj_post);
					$this->db->query("DELETE FROM blog_posts_ext WHERE id = ?d", $lj_post_row['id']);
						$this->db->query("DELETE FROM blog_posts_ext WHERE post_id = ?d", $blogItem['id']); //Удаляем все записи в blog_posts_ext относящиеся к этому посту
			}
		}catch(Exception $e){
				echo $e->getMessage();
		}
		
		}

	}
	

}




/**
* Blog Post
*/
class rBlogPost
{

	protected $id = 0;
	protected $blog = NULL;
	protected $data = array();
	protected $db = NULL;


	protected $picSizes = array(
		'original' => array('prefix' => 'o-', 'w' => 1280, 'h' => 20000, 'assign_as_next' => true),
		'base' => array('prefix' => '', 'w' => 560, 'h' => 10000),
		'thumb' => array('prefix' => 't-', 'w' => 140, 'h' => 100, 'method' => 'crop'),
	
	);
	
	function __construct(rBlog $rBlog, $postID)
	{
		$this->blog = $rBlog;
		$this->db = $rBlog->db;
		if(!$this->getByID($postID))
			throw new Exception('Cant get post '.$postID);
	}


	/**
	* Получить пост по ID
	* @var int $id ID поста
	* @return
	**/
	public function getByID($id)
	{
		$this->data = $this->db->selectRow('SELECT p.* 
			FROM blog_posts p 
			WHERE p.id = ?d', $id);
		$this->id = $this->data['id'];
		$this->data = $this->proceedPost($this->data);
			
		return $this->data;	

	}


	public function getData()
	{
		return $this->data;
	}

	static public function getDir($post = false)
	{
		$id = $post ? $post['id'] : $this->id;
		return 'posts/'.ceil($id / 100000).'/'.ceil($id / 1000).'/'.$id;
	}

	/**
	* Получить путь к папке поста на сервере
	**/
	static public function getPath($post = false)
	{
		$path = $post ? USERS_PATH.'/'.self::getDir($post) : USERS_PATH.'/'.$this->getDir();
		@mkdir($path, 0777, true);
		return $path;
	}

	/**
	* Получить URL к папке поста через веб
	**/
	static public function getURL($post = false)
	{
		return USERS_URL .self::getDir($post).'/';
	}

	static public function proceedPost($post)
	{
		$post['res_url'] = self::getURL($post);
		$post['res_path'] = self::getPath($post);
		if(!empty($post['tags_cache'])){
			$post['tags'] = @unserialize($post['tags_cache']);
		}
		$post['post_url'] = ROOT_URL.$post['blog_url'].'/'.$post['url'].'.html';

		return $post;
	}



	/**
	* Прикрепляет картинку к посту
	**/
	public function attachPic($file)
	{
		require_once 'rlib/Imager.php';
		$imager = new Imager;

		if(!$imager->setImage($file['tmp_name'])){
			return false;
		}

		
		$picName = uniqid(substr($this->blog->app->url->URLize(preg_replace('~\.[a-z0-9]{2,4}$~i', '', $file['name'])).'-', 0, 20));
		

		if(!$base_name = $imager->packetResize($this->data['res_path'], $this->picSizes, $picName)){
			return false;
		}
		
		@unlink($file['tmp_name']);

		$attachID = $this->db->query('INSERT INTO blog_images SET ?a', array(
			'post_Id' => $this->id,
			'dateadd' => time(),
			'filename' => $base_name,
			'original_name' => $file['name'],
		));

		if(!$this->data['mainpic_id'])
			$this->setField('mainpic_id', $attachID);

		return array(
			'basename' => $base_name,
		);

	}


	public function setField($field, $value)
	{
		$this->db->query('UPDATE blog_posts SET ?# = ? WHERE id = ?d', $field, $value, $this->id);
	}


}



