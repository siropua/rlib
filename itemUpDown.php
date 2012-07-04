<?php

/*** Пример использования:
*

// Создаем объект таблицы:
$t = new rTableClass(&$db, 'items');
// Создаем объект для манипулирования строками, и указываем поле, по которому будет сортировка
$UpDown = new itemUpDown($t, 'ordr');


*****************************/


require_once('rlib/rTable.class.php');

class itemUpDown
{
	
	protected $table;
	protected $field;
	protected $filter = array();
	
	/**
	* Конструктор
	* @var $tableObject - объект rTableClass
	* @var $orderField - поле по которому происходит сортировка
	*/
	function __construct(&$tableObject, $orderField, $filterFields = null)
	{
		if(!is_object($tableObject)){
			trigger_error('First argument is not object');
			return false;
		}
		if(@!$tableObject->db){
			trigger_error('First argument must be a rTableClass object!');
			return false;
		}
		if(!$orderField){
			trigger_error('Second argument must be a name for order field');
			return false;			
		}
		$this->table = &$tableObject;
		$this->field = $orderField;
		if((is_string($filterFields) || is_array($filterFields)) && !empty($filterFields))
			$this->filter = $filterFields;
	}
	
	/**
	* moveUp
	* @param mixed $id
	* @return mixed
	*/
	function moveUp($id)
	{
		return $this->move($id, 'up');
	}
	
	/**
	* moveDown
	* @param mixed $id
	* @return mixed
	*/
	function moveDown($id)
	{
		return $this->move($id, 'down');
	}
	
	/**
	* move
	* @param mixed $id
	* @param mixed $direction
	* @return bool
	*/
	function move($id, $direction)
	{
		$direction = trim($direction);
		if($direction != 'up') $direction = 'down';
		$id = (int)$id;
		if(!$id) return false;
		
		$selected = $this->table->get($id);
		
		if(!$selected) return false;

		if(!$selected[$this->field]){
			$max = $this->getNextVal();
			$this->table->put($id, array($this->field=>$max));
			return true;
		}
		
		$where = '';
		if($this->filter){
			foreach($this->filter as $f => $v){
				if($v === null){
					$where .= " ISNULL(`$f`) AND ";
				}else
				$where .= $f." = '".mysql_escape_string($v)."' AND ";
			}
		}		

		$next = $this->table->db->selectRow('SELECT id, ?# FROM ?# WHERE '.$where.'?# '.
			($direction == 'down' ? '> ?d ORDER BY ?#' : '< ?d ORDER BY ?# DESC').' LIMIT 1', 
				$this->field, $this->table->getTable(), $this->field, $selected[$this->field], $this->field);

		if(!$next) return false;
		
		$this->table->put($next['id'], array($this->field => $selected[$this->field]));
		$this->table->put($selected['id'], array($this->field => $next[$this->field]));
		
		return true;		
	}
	
	/**
	* insertAfter
	* @param mixed $id
	* @return mixed
	*/
	function insertAfter($id){
		$id = (int)$id;
		$ordr = $this->table->getCell($this->field, $id);
		$where = '';
		if($this->filter){
			foreach($this->filter as $f => $v){
				if($v === null){
					$where .= " ISNULL(`$f`) AND ";
				}else
				$where .= $f." = '".mysql_escape_string($v)."' AND ";
			}
		}
		$this->table->db->query('UPDATE ?# SET ?# = ?# +1 WHERE ?# > ?d'.$where, $this->table->getTable(),
			$this->field, $this->field, $this->field, $ordr);
		$ordr++;
		return $ordr;
	}
	
	/**
	* getNextVal
	* @return mixed
	*/
	function getNextVal()
	{
		$max = $this->table->max($this->field, $this->filter);
		$max++;
		return $max;
	}
	
	/**
	* getNextVal
	* @param string $where
	* @param string $limit
	* @return mixed
	*/
	function getList($where = '', $limit = '')
	{
		return $this->table->getList($where, $this->field, $limit);
	}
	
	/**
	* пересчитывает все ordr-индексы по порядку
	**/
	public function recountAll($filter = false){
		if($filter !== false) $this->filter = $filter;
		$list = $this->table->getList($this->filter, $this->field);
		$n = 1;
		foreach($list as $item){
			$this->table->put($item['id'], array(
				$this->field => $n++
			));
		}
	}
}