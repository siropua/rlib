<?php

class emailWorker {
	
	public $box;
	
    function __construct($login, $passw) {
		$this->login = $login;
		$this->passw = $passw;
		$this->box = imap_open("{".POP_SERVER.":".MAIL_PORT."/pop3/notls}INBOX", $this->login, $this->passw) 
				or die('Cannot connect : ' . imap_last_error());
    }
	
	/*
	* получить сообщение.
	*/
    public function getNewMessage($i){
    	
    		$header = imap_headerinfo($this->box, $i);
    		$from = $header->from;
    	    $overview = imap_fetch_overview($this->box, $i);
    
		    $postArr['email'] = imap_utf8($overview[0]->from);
		    $postArr['email'] = substr($postArr['email'], (strpos($postArr['email'], '<')+1), -1);
			$postArr['email'] = trim($postArr['email']);
			
			$postArr['title'] = imap_utf8($overview[0]->subject);
			
			$postArr['text'] = imap_fetchbody($this->box, $i, 1.2);

			if(!strlen($postArr['text'])>0){
			    $postArr['text'] = imap_fetchbody($this->box, $i, 1);
			}
			
			if(preg_match("/koi8\-r/i",$header->subject)){
				
				if(base64_decode($postArr['text'], true)){
					$postArr['text'] = imap_base64($postArr['text']);
				}
				$postArr['text'] = iconv('KOI8-r','utf-8',$postArr['text']);
				
			}
			
		return $postArr;
    }
    
    public function deleteMessage($i){
    	imap_delete($this->box, $i);
    }
    
	public function totalMessages(){
		return imap_num_msg($this->box); //количество писем
	}
	
	/*Получить все вложения письма*/
	public function getAttachments($message_number) {
	    //http://www.electrictoolbox.com/function-extract-email-attachments-php-imap/
	   	$connection = $this->box;
	    $attachments = array();
	    $structure = imap_fetchstructure($connection, $message_number);
	
	    if(isset($structure->parts) && count($structure->parts)) {
	   
	        for($i = 0; $i < count($structure->parts); $i++) {
	   
	            $attachments[$i] = array(
	                'is_attachment' => false,
	                'filename' => '',
	                'name' => '',
	                'attachment' => ''
	            );
	           
	            if($structure->parts[$i]->ifdparameters) {
	                foreach($structure->parts[$i]->dparameters as $object) {
	                    if(strtolower($object->attribute) == 'filename') {
	                        $attachments[$i]['is_attachment'] = true;
	                        $attachments[$i]['filename'] = $object->value;
	                    }
	                }
	            }
	           
	            if($structure->parts[$i]->ifparameters) {
	                foreach($structure->parts[$i]->parameters as $object) {
	                    if(strtolower($object->attribute) == 'name') {
	                        $attachments[$i]['is_attachment'] = true;
	                        $attachments[$i]['name'] = $object->value;
	                    }
	                }
	            }
	           
	            if($attachments[$i]['is_attachment']) {
	                $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1);
	                if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
	                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
	                }
	                elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
	                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
	                }
	            }
	           
	        }
	       
	    }
	   
		   	foreach($attachments as $k=>$v){
		   			if(empty($attachments[$k]['attachment'])){
		   				unset($attachments[$k]);
		   			}
		   	}
	   
	    return $attachments;
   
	}	

    public function toClose(){
    	//imap_expunge($this->box);
    	imap_close($this->box, CL_EXPUNGE);
    }
    function __destruct(){
    	$this->toClose();
    }
    
}
?>