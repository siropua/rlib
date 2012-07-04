<?php

class rUserpics{

	public $user = null;
	public $db = null;
	
	protected $table = 'users_userpics';
	
	protected $images = array(
		0 => array('prefix' => '', 'w' => 300, 'h' => 300),
		100 => array('prefix' => '100-', 'w' => 100, 'h' => 100, 'assign_as_next' => true, 'method' => 'crop'),
		50 => array('prefix' => '50-', 'w' => 50, 'h' => 50, 'method' => 'crop'),
	);
	
	public function __construct($db, $user){
		$this->db = $db;
		$this->user = $user;
	}
	
	
	public function setImageSizes($sizes, $section = false, $autoCreate = false){
		if($section){
			if(!empty($this->images[$section]) || $autoCreate)
				$this->images[$section] = $sizes;
		}else{
			$this->images = $sizes;
		}
	}
	
	public function setDefaultUserpic($id){
		if($pic = $this->getByID($id)){
			$this->user->setField('userpic', $pic['filename']);
		}
		return $pic;
	}
	
	
	/******************************************************************
	* загружаем юзерпик
	*******************************************************************/
	public function upload($file, $name = ''){
		require_once('rlib/Imager.php');
		$imager = new Imager($file);
		if($imager && ($imager->getIMGType()!=IMT_NONE)){
			$filename = false;
			if($imager->getIMGType() == IMT_GIF){
				// check gif 
				if($imager->getWidth() <= $this->images[100]['w'] 
						&& $imager->getHeight() <= $this->images[100]['h']){
					// upload
					$filename = $imager->prepareDestination($this->getBasePath().'/');
					if($filename){
						if(!move_uploaded_file($file, $filename)){
							$filename = false;
						}else{
							chmod($filename, 0666);
						}
					}
					
				}
			}else{
				// resize image
				$result = $imager->saveCropped($this->getBasePath().'/', 
					$this->images[100]['w'], $this->images[100]['h']);
				if($result['destination'])
					$filename = $result['destination'];
			}
			
			if($filename){
				/*************************** ВОТ ТУТ ВСЁ ОК **************************/
				$id = $this->db->query('INSERT INTO ?# SET ?a', $this->table, array(
					'owner_id' => $this->user->getID(),
					'filename' => basename($filename),
					'size' => filesize($filename),
					'name' => htmlspecialchars($name),
					'dateadd'=>time()
				));
				
				return $id;
			}
		}
		
		return false;
			
	}
	
	public function count(){
		return $this->db->selectCell('SELECT COUNT(*) FROM ?# WHERE user_id = ?d', 
			$this->table, $this->user->getID()
		);
	}
	
	/******************************************************************
	* удаляем юзерпик юзера по ID
	*******************************************************************/
	public function delete($id){
		if($pic = $this->getByID($id)){			
			$this->db->query("DELETE FROM ?# WHERE id = ?d", $this->table, $id);
			
			foreach($this->images as $img)
				@unlink($this->getBasePath().'/'.$img['prefix'].$pic['filename']);
			
			if($this->user->userpic == $pic['filename']){
				$this->user->setField('userpic', '');
			}
			
			return true; // удалили ОК
		}
		return false;
	}
	
	/******************************************************************
	* получаем юзерпик юзера по ID
	*******************************************************************/
	public function getByID($id, $checkOwner = true){
		$pic = $this->db->selectRow("SELECT * FROM ?# WHERE id = ?d", $this->table, $id);
		if($checkOwner && $pic)
			if($pic['owner_id'] != $this->user->getID()) return false;
		
		return $pic;
	}
	
	/******************************************************************
	* получаем список юзерпиков
	*******************************************************************/
	public function getList(){
		return $this->db->select("SELECT u.filename AS ARRAY_KEY, u.* 
			FROM ?# u WHERE u.owner_id = ?d", 
				$this->table, $this->user->getID()
		);
	}
	
	public function getBasePath(){
		return $this->user->getUserDir('userpics');
	}
	
	public function getBaseURL(){
		return $this->user->getUserURL('userpics');
	}
	
}