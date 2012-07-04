<?php

if(!function_exists('json_encode')){
	
	require_once('rlib/FastJSON.class.php');
	
	/**
	* json_encode
	* @param mixed $o
	* @return mixed
	*/
	function json_encode($o){
		return FastJSON::encode($o);
	}
	
	/**
	* json_decode
	* @param mixed $s
	* @return mixed
	*/
	function json_decode($s){
		return FastJSON::decode($s);
	}

	
}


class rJSONSite{
	
	protected $errors = array();
	protected $data = array();
	protected $msg = '';
	
	protected $customFields = array();
	
	protected $status = 200;
	
	protected $wasOutput = false;
	
	/**
	* Конструктор
	* @return void
	*/
	public function __construct(){
		$this->clearErrors();
	}
	
	/**
	* Вывести всё в консоль в виде JSON-строки
	* @param bool $data
	* @param bool $overwriteData
	* @param bool $dupOutput
	* @return bool
	*/
	public function output($data = false, $overwriteData = false, $dupOutput = false){
		
		if($this->wasOutput && !$dupOutput) return false;
		
		if($data)
			if($overwriteData){
				$this->data = $data;
			}else{
				$this->data = array_merge($this->data, $data);
			}

		
		echo json_encode(array_merge(array(
			'data' => $this->data,
			'error' => implode("\n", $this->errors),
			'status' => $this->status,
			'msg' => $this->msg
		), $this->customFields));
			
		return true;
	} #/ output
	
	/**
	* setMsg
	* @param mixed $msg
	* @return void
	*/
	public function setMsg($msg){
		$this->msg = trim($msg);
	}
	
	/**
	* setData
	* @param mixed $data
	* @return void
	*/
	public function setData($data){
		$this->data = $data;
	}
	
	/**
	* addData
	* @param mixed $data
	* @return void
	*/
	public function addData($data){
		$this->data = array_merge($this->data, $data);
	}
	
	/**
	* assign
	* @param mixed $section
	* @param mixed $data
	* @return void
	*/
	public function assign($section, $data){
		$this->data[$section] = $data;
	}
	
	/**********************************************************
	* ERRORS
	* Работа с ошибками
	***********************************************************/
	
	/**
	* addError
	* @param mixed $errorMsg
	* @param int $status
	* @return void
	*/
	public function addError($errorMsg, $status = 503){
		if(!in_array($errorMsg, $this->errors))
			$this->errors[] = $errorMsg;
		$this->status = $status;
	}
	
	/**
	* setErrors
	* @param mixed $error
	* @param int $status
	* @return void
	*/
	public function setErrors($error, $status = 503){
		if(is_array($error)){
			$this->errors = $error;
		}else{
			$this->errors = array($error);
		}
		$this->status = $status;
	}
	
	/**
	* clearErrors
	* @return void
	*/
	public function clearErrors(){
		$this->errors = array();
		$this->status = 200;
	}
	
	/**
	* outputError
	* @param mixed $error
	* @param int $status
	* @return void
	*/
	public function outputError($error, $status = 503){
		$this->addError($error, $status);
		$this->output();
	}


	/**
	* Выводит данные и завершает скрипт
	* @return void
	**/
	public function render(){
		$this->output();
		exit;
	}
	
}


class rAuthException extends Exception{}