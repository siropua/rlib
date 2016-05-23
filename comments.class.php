<?php
/**




* @version $Id: comments.class.php,v 1.5 2008/07/27 18:58:33 steel Exp $
* @copyright 2007
* @autor Steel Ice
* @package rLib


TABLE Structure

CREATE TABLE `files_comments` (
                  `id` int(10) unsigned NOT NULL auto_increment,
                  `owner_id` int(10) unsigned NOT NULL default '0',
                  `entry_id` int(10) unsigned NOT NULL default '0',
                  `approved` tinyint(4) unsigned NOT NULL default '1',
                  `datepost` int(10) unsigned NOT NULL default '0',
                  `sort` int(11) NOT NULL default '0',
                  `level` int(11) NOT NULL default '0',
                  `parent_id` int(10) unsigned NOT NULL default '0',
                  `deleted` tinyint(4) NOT NULL default '0',
                  `blocked` tinyint(4) NOT NULL default '0',
                  `ip` varchar(15) NOT NULL default '',
                  `username` varchar(50) NOT NULL default '',
                  `text` text NOT NULL,
                  PRIMARY KEY  (`id`),
                  KEY `entry_id` (`entry_id`,`deleted`,`approved`),
                  KEY `sort` (`sort`),
                  KEY `level` (`level`),
                  FULLTEXT KEY `text` (`text`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8

Optional table, for provide "unreaded comments" service

CREATE TABLE `comments_t` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `viewed` int(10) unsigned NOT NULL default '0',
  `viewed_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

Supported fields in items

comments - Comments count
allow_comments
last_comment

*/


class comments{
	var $_db = null;
	var $_prefix = '';
	var $_table = 'comments';
	var $_entryID = 0;

	var $_entryFields = array();

	var $_entryStat = false;

	var $_approveMode = false;
	
	protected $parserName = '';
	
	protected $attachWidth = 500;
	protected $attachHeight = 800;
	

	/** constructor
	* @param object $db link to SimpleDB object
	* @param string $prefix table prefix ({$prefix}_comments)
	* @param int $entryID id of entry wich will be commented
	*/
	function __construct($db, $prefix = '', $entryID = 0)
	{
		global $smarty;
		$this->_db = $db;
		if($prefix){
			$this->_prefix = trim($prefix);
			$this->_table = $prefix . '_' . $this->_table;
		}

		if($entryID)
			$this->setEntryID($entryID);
		if($smarty)
			$smarty->assign('comments_prefix', $prefix);

		$this->setEntryFields();
	}

	function setEntryFields()
	{
		$this->_entryFields = array(
			'id'=>'id',
			'last_comment'=>'last_comment',
			'comments'=>'comments',
			'allow_comments'=>'allow_comments'
		);
	}

	function setApprove($s = true){
		$this->_approveMode = (bool)$s;
	}

	/** set entry id
	* @param int $entryID entry id to set
	*/
	function setEntryID($entryID)
	{
		$this->_entryID = (int)$entryID;
	}

	/**
	* returns entry id
	* @return int entry id
	*/
	function getEntryID()
	{
		return $this->_entryID;
	}

	/**
	* @return string table with comments
	*/
	function getTable()
	{
		return $this->_table;
	}

	/**
	* set table for comments
	* usable for non-standart table names
	*/
	function setTable($table)
	{
		$this->_table = $table;
	}

	public function setParser($parser){
		if(!class_exists($parser)) throw new Exception('Comment parser not found');
		$this->parserName = $parser;
	}

	/**
	* Enables statistics for entry. Disabled by default.
	* @param string $stat Status. False - disables, true - enables depended on prefix, string - set entry table manually
	* @return vodid
	*/
	function enableEntryStats($stat = true)
	{
		if($stat === false)
			$this->_entryStat = false;
		else
			$this->_entryStat = $stat === true ? $this->_prefix : $stat;
	}
	
	/**
	* Получаем комментарий
	* @param int $commentID ID комментария
	* @return array Массив с данными комментария
	**/
	public function getCommentByID($commentID){
		return $this->_db->selectRow('SELECT * FROM ?# WHERE id = ?d', $this->_table, $commentID);
	}

	/**
	* adding comment
	*
	* @param string $text comment text
	* @param array $user_data array with user data of comment owner (used these keys: id, ip)
	* @param int $replyTo id of parent comment (0 if first level comment )
	*
	* @return array array(id=>ID of added comment, comments=> current comments count of specified entry). If fail to add comment - return FALSE
	*/
	function addComment($text, $user_data, $replyTo = 0)
	{

		$user_data['id'] = @(int)$user_data['id'];
		$replyTo = (int)$replyTo;

		$this->_db->query('lock table '.$this->_table.' write'); // ********************************


		$reply_owner = 0;

		if($replyTo)
		{
			$reply = $this->_db->selectRow('select * from '.$this->_table.' where id = '.$replyTo);


			if($reply)
			{
				$level = (int)$reply['level'] + 1;
			}else
				$level = 1;


			$sort = $this->_getSort($replyTo, 0);

		}else
		{
			$level = 1;
			$sort = $this->_db->selectCell('select max(sort) from '.$this->_table.
				' where entry_id = '.$this->_entryID);

		}
		$sort = (int)$sort + 1;

		$res = $this->_db->query('update '.$this->_table.' set sort = sort + 1 where entry_id = '.$this->_entryID.' and sort >= '.$sort);
		

		$r_text = $this->replaceText($text);
		
		
		/** аттач картинки **/
		if(!empty($_FILES['pic_attach']['tmp_name']) && is_uploaded_file($_FILES['pic_attach']['tmp_name'])){
			require_once('rlib/Imager.php');
			$imager = new Imager;
			$preDir = rand(1, 60000);
			$dir = 'comments/'.$preDir.'/';
			if($imager->setImage($_FILES['pic_attach']['tmp_name']) && $imager->prepareDir(USERS_PATH.'/'.$dir)){
				$result = $imager->saveResized(USERS_PATH.'/'.$dir, $this->attachWidth, $this->attachHeight);
				if($result['destination']){
					$r_text .= '<div class="comment_attach"><img src="http://'.$_SERVER['HTTP_HOST'].USERS_URL.$dir.basename($result['destination']).'" width="'.$result['width'].'" height="'.$result['height'].'" alt=""></div>';
				}
			}
		}

		if(@!$user_data['ip'])
			$user_data['ip'] = trim(@$_SERVER['REMOTE_ADDR']);

		$post_arr = array(
			'owner_id' => $user_data['id'],
			'entry_id' => $this->_entryID,
			'datepost' => time(),
			'sort' => $sort,
			'level' => $level,
			'parent_id' => $replyTo,
			'ip' => $user_data['ip'],
			'text' => $r_text
		);

		if(empty($user_data['id']) && isset($user_data['username'])){
			$post_arr['username'] = htmlspecialchars($user_data['username']);
		}

		if($this->_approveMode) $post_arr['approved'] = 0;

		$id = $this->_db->query('INSERT INTO ?# SET ?a', $this->_table, $post_arr);


		$post_arr['comments'] = $this->commentsCount();
		$post_arr['id'] = $id;

		if(@$reply)
			$post_arr['replyTo'] = $reply;


		$this->_db->query("unlock tables"); // *****************************************************

		if($this->_entryStat && !$this->_approveMode){
			$res = $this->_db->query('update '.$this->_entryStat.' SET '.
				$this->_entryFields['comments'].' = '.$this->_entryFields['comments'].' + 1, '.
				' last_comment_uid = '.( (int)$user_data['id'] ).', '.
				$this->_entryFields['last_comment'].' = '.time().
				' where '.$this->_entryFields['id'].' = '.$this->_entryID);

		}

		return $post_arr;

	}

	/**
	* Количество комментариев в entry
	* @return int comments count of specified entry
	*/
	function commentsCount($entryID = 0)
	{
		if($entryID)
			$this->setEntryID($entryID);

		$count = $this->_db->selectCell('SELECT COUNT(id) FROM ?#
			WHERE (NOT deleted) AND entry_id = ?d{ AND approved = ?d}', $this->_table, $this->_entryID,
		$this->_approveMode ? 1 : DBSIMPLE_SKIP);

		return (int)$count;
	}

	/**
	* recursive function, returns sort order for comment
	*
	*/
	function _getSort($id, $order)
	{
		$order = (int)$order;
	    $comm = $this->_db->getRow('select * from '.$this->_table.' where parent_id = '.$id.
			' order by sort desc limit 1');


		//print_r($comm); echo '-'. $order . '-';
		if($comm)
		{
	        if($comm['sort'] > $order)
			{
	            $res = $this->_getSort($comm['id'], $comm['sort']);
	        }else{
	            $res = $order;
	        }
	    }else{
			$res = $this->_db->selectCell('select sort from '.$this->_table.' where id = '.$id);

	    }

	    return $res;
	}

	function updateEntry($entryID = 0, $entryTable = '')
	{
		if($this->_prefix && !$entryTable)
			$entryTable = $this->_prefix;

		$entryID = (int)$entryID;
		if(!$entryID && $this->_entryID)
			$entryID = $this->_entryID;



		if(!$entryTable) return false;
		$entryStatus = $this->_db->selectRow('SELECT
			COUNT(id) as '.$this->_entryFields['comments'].',
			MAX(datepost) as '.$this->_entryFields['last_comment'].'
			FROM ?# WHERE (NOT deleted) AND entry_id = ?d{ AND approved = ?d}', $this->_table, $entryID,
		$this->_approveMode ? 1 : DBSIMPLE_SKIP);

		$res = $this->_db->query('UPDATE ?# SET ?a WHERE ?# = ?', $entryTable, $entryStatus, $this->_entryFields['id'], $entryID);



	}

	/**
	* mark comment as deleted
	*/
	function deleteComment($id, $check_owner=0,$checkEntry = true)
	{
		$comment = $this->getOneComment($id);
		if($comment && !$checkEntry) $this->_entryID = $comment['entry_id'];
		if($comment && ($comment['entry_id'] == $this->_entryID)){
			$this->_db->query('update '.$this->_table.' set deleted = 1 where id = '.((int)$id).
				($check_owner?' and owner_id = '.$check_owner:''));

			if($this->_entryStat)
				$this->updateEntry(0, $this->_entryStat);
		}
	}

	public function voteComment($commentID, $vote, $uid){
		if(!$uid) return false;
		
		$comment = $this->getCommentByID($commentID);
		if(!$comment) return false;
		if($comment['owner_id'] == $uid) return false;
		
		if($comment['cant_rate']) return false;

		$myVote = $this->_db->selectCell('SELECT vote FROM ?# WHERE comment_id = ?d AND user_id = ?d', $this->_table.'_votes', $commentID, $uid);
		if($myVote){
			if($myVote == $vote){
				$vote = 0;
			}else{
				$this->_db->query('UPDATE ?# SET vote = ?d, dateadd = ?d WHERE comment_id = ?d AND user_id = ?d', $this->_table.'_votes',
					$vote, time(), $commentID, $uid);
				$vote = $vote - $myVote;
				$vc = 0;
			}
		}else{
			$this->_db->query('INSERT INTO ?# SET ?a', $this->_table.'_votes', array(
				'vote' => $vote, 'dateadd' => time(),
				'comment_id' => $commentID, 'user_id' => $uid
			));
			$vc = 1;
		}
		if($vote)$this->_db->query('UPDATE ?# SET rating = rating + ?d{, rating_count = rating_count + ?d} WHERE id = ?d',
			$this->_table, $vote, $vc ? $vc : DBSIMPLE_SKIP, $commentID);

		$data = $this->_db->selectRow('SELECT owner_id, rating, rating_count FROM ?# WHERE id = ?d', $this->_table, $commentID);
		$data['voted'] = $vote;
		$data['first_vote'] = $myVote ? false : true;
		return $data;
	}

	function blockThread($id){

	}

	function markEntryViewed($user_id)
	{
		$user_id = (int)$user_id;
		if(!$user_id || !$this->_entryID)
			return false;

		$v = $this->commentsCount();
		$id = (int)$this->_db->selectCell('select max(id) from '.$this->_table.' where entry_id = '.$this->_entryID);
		$res = $this->_db->query('insert into '.$this->_table.
			'_t (user_id, entry_id, viewed, viewed_id) values ('.
			((int)$user_id).', '.$this->_entryID.', '.$v.', '.$id.') on duplicate key update viewed = '.$v.', viewed_id = '.$id);

	}

	function getViewed($user_id)
	{
		$user_id = (int)$user_id;
		if(!$user_id)
			return 0;
		$res = $this->_db->selectCell('select viewed from '.$this->_table.'_t where entry_id = '.$this->_entryID.' and user_id = '.$user_id);

		return $res;
	}

	function getViewedID($user_id)
	{
		$user_id = (int)$user_id;
		if(!$user_id)
			return 0;
		$res = $this->_db->selectCell('select viewed_id from '.$this->_table.'_t where entry_id = '.$this->_entryID.' and user_id = '.$user_id);

		return $res;
	}

	/**
	* this function u must override in child class
	*/
	function getComments()
	{
		$comments = $this->_db->select('SELECT * FROM '.$this->_table.' WHERE entry_id = '.$this->_entryID.
		    ' and deleted=0{ AND approved = ?d} ORDER BY sort',
			$this->_approveMode ? 1 : DBSIMPLE_SKIP
		);
		return $comments;
	}
	
	public function getAllComments(){
		$this->_db->select('SELECT c.* FROM ?# WHERE deleted = 0 ORDER BY id DESC');
	}

	public function getAllLast($limit){
		return $this->_db->select('SELECT c.* FROM ?# c WHERE c.deleted = 0 ORDER BY c.id DESC LIMIT ?d', $this->_table, $limit);
	}

	function getOneComment($id)
	{
		return $this->_db->selectRow('select * from '.$this->_table.' where id = '.$id);
	}

	function approveComment($id){

		$c = $this->getOneComment($id);
		$this->_db->query('UPDATE ?# SET approved = 1 WHERE id = ?d', $this->_table, $id);
		$this->updateEntry($c['entry_id']);
	}

    /**
    * Replace text
    *
    * @param string $text input text
    * @access public
    * @return string replaced text
    */
    function replaceText($text)
    {
		
		if($this->parserName){
			$parser = new $this->parserName;
			return $parser->parse($text);
		}

		if (!class_exists('safehtml'))
    	require_once('rlib/safehtml.php');
		
        // br
        $safehtml = new safehtml();
        $text = str_replace("\n", "<br/>", str_replace("\r", "", $text));
        $text = $safehtml->parse($text);

        return $text;
    }
}

?>