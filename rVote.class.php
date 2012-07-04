<?php

require_once('rlib/rTable.class.php');

define('QUESTIONS_TABLE', 'vote_questions');
define('ANSWERS_TABLE', 'vote_answers');
define('VARIANTS_TABLE', 'vote_variants');
define('USER_HASH_VAR', 'vus');

class rVote
{
	var $db = null;
	
	var $qTable = null;
	var $aTable = null;
	var $vTable = null;
	
	var $qID = 0;
	
	var $maxPx = 100;
	
	private $forcedHash = false;
	
	function rVote($dbLink, $question = 0)
	{
		$this->db = &$dbLink;
		$this->qTable = new rTableClass($dbLink, QUESTIONS_TABLE);
		$this->aTable = new rTableClass($dbLink, ANSWERS_TABLE);
		$this->vTable = new rTableClass($dbLink, VARIANTS_TABLE);
		
		if($question) $this->setQuestion($question);
	}
	
	function setQuestion($question)
	{
		$this->qID = (int)$question;
	}
	
	function setMaxPx($px){
		$this->maxPx = (int)$px;
	}
	
	function addQuestion($data)
	{
		$data['dateadd'] = time();
		
		// add question
		$id = $this->qTable->add($data, array(
			'question', 'dateadd', 'type'
		));
		
		// add answers
		if(is_array($data['answers'])){
			foreach($data['answers'] as $answer){
				$this->vTable->add(array(
					'name' => htmlspecialchars($answer),
					'question_id' => $id
				));
			}
		}
		
		return $id;
	}
	
	function addVariant($data)
	{
		if(!$this->qID) return false;
		
		$data['question_id'] = $this->qID;
		
	}
	
	function setForcedHash($hash){
		$this->forcedHash = md5($hash);
	}
	
	function getUserHash()
	{
		if($this->forcedHash) return $this->forcedHash;
		
		if(@$_COOKIE[USER_HASH_VAR]) return $_COOKIE[USER_HASH_VAR];
		
		$hash = md5($_SERVER['HTTP_HOST'].$_SERVER['HTTP_USER_AGENT']);
		setcookie(USER_HASH_VAR, $hash, time()+(60*60*24*30));
		return $hash;
	}
	
	function getList()
	{
		return $this->qTable->getList('', 'id DESC');
	}
	
	function getQuestion($id = 0)
	{
		if($id)
			$this->setQuestion($id);
		if(!$this->qID) return false;
		
		$q = $this->db->selectRow('SELECT * FROM ?# WHERE id = ?d', QUESTIONS_TABLE, $this->qID);
		if(!$q) return false;
		
		return $this->processQuestion($q);	
	}
	
	function getLastQuestion()
	{
		$q = $this->db->selectRow('SELECT * FROM ?# ORDER BY id DESC', QUESTIONS_TABLE);
		if(!$q) return false;
		$this->qID = (int)$q['id'];
		
		return $this->processQuestion($q);
	}
	
	/**
	* Заполняет массив с вопросом всякими полезными и нужными данными
	* @param $q array Массив с вопросом
	* @return array Массив с обработанным вопросом
	**/
	function processQuestion($q){
		if(!empty($q['id'])) $this->setQuestion($q['id']);
		$q['variants'] = $this->db->select('SELECT id AS ARRAY_KEY, id, name, answers FROM ?# WHERE question_id = ?d', VARIANTS_TABLE, $this->qID);
		$q['answer_max'] = 0;
		foreach($q['variants'] as $v){
			$q['answer_max'] = max($v['answers'], $q['answer_max']);
		}
		foreach($q['variants'] as $n=>$v){
			if($q['answers']){
				$q['variants'][$n]['percent'] = round(($v['answers'] / $q['answers']) * 100, 0);
				$q['variants'][$n]['px'] = floor (($v['answers'] / $q['answer_max']) * $this->maxPx);
			}else{
				$q['variants'][$n]['percent'] = 0;
				$q['variants'][$n]['px'] = 0;
			}
		}
		return $q;
	}
	
	/**
	* Возвращает отданый за выбраное голосование голос юзера.
	* Или FALSE, если голос еще не отдавался
	*/
	function getMyVote($id = 0){
		if($id)
			$this->setQuestion($id);
		if(!$this->qID) return false;
		
		return $this->db->selectCell('SELECT answer_id FROM ?# WHERE question_id = ?d AND user_hash = ?', 
			ANSWERS_TABLE, $this->qID, $this->getUserHash());
	}
	
	
	/**
	* Собственно, отдаем голос за выбранный вопрос
	*/
	function vote($result, $qID = 0)
	{
		if($qID)
			$this->setQuestion($qID);
		if(!$this->qID) return false;
		
		if($this->getMyVote()) return false;
		
		$q = $this->getQuestion();
		
		if(!$q) return false;
		
		if(is_array($result)){
			$answers = 0;
			foreach($result as $r){
				// не даем проголосвать за большее число ответов чем можно
				if($answers > $q['max_versions']) return $answers > 0; 
				
				// пропускаем все несуществующие ответы
				if(@!$q['variants'][$r]) continue; 
				
				$this->aTable->add(array(
					'question_id' => $this->qID, 'answer_id' => $r, 
					'user_hash' => $this->getUserHash(), 'dateadd' => time()
				)); // добавили голос
				
				$this->vTable->inc('answers', (int)$r); // проапдейтили таблицу вариантов, увеличив у варианта значение поля answers
				
				$this->qTable->inc('answers', (int)$this->qID); // сделали тоже самое в таблице вопросов
				
				$answers++;
				
			}
			return $answers > 0;
		}
		
		return false;
		
	}
	
}