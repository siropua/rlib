<?php
include_once("xmlrpcs/xmlrpc.inc");

$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

class LJ_Login_Exception extends Exception{};

class  Livejournal {
	protected $login;
	protected $passw;
	
	function __construct($login, $passw){
		
		$this->login = $login;
		$this->passw = $passw;
		if(empty($this->login) or empty($this->passw)){
			throw new LJ_Login_Exception("Проверьте Ваши логин и пароль");
		}
		
	}
	public function delete($toPost){
		$toPost['text'] = "";
		$toPost['subj'] = "";
		return $this->post($toPost);
	}
	public function edit($toPost){
		return $this->post($toPost);
	}

	public function post($toPost){
//print_r($_POST[tags]);
	$date = time();
	if(!isset($toPost['year'])) $toPost['year'] = date("Y", $date);
	if(!isset($toPost['mon'])) $toPost['mon'] = date("m", $date);
	if(!isset($toPost['day'])) $toPost['day'] = date("d", $date);
	if(!isset($toPost['hour'])) $toPost['hour'] = date("G", $date);
	if(!isset($toPost['min'])) $toPost['min'] = date("i", $date);

		
		
		$post = array(
		  "username" => new xmlrpcval($this->login, "string"),
		  "password" => new xmlrpcval($this->passw, "string"),
		  "event" => new xmlrpcval($toPost['text'], "string"),
		  "subject" => new xmlrpcval($toPost['subj'], "string"),
		  "lineendings" => new xmlrpcval("unix", "string"),
		  "year" => new xmlrpcval($toPost['year'], "int"),
		  "mon" => new xmlrpcval($toPost['mon'], "int"),
		  "day" => new xmlrpcval($toPost['day'], "int"),
		  "hour" => new xmlrpcval($toPost['hour'], "int"),
		  "min" => new xmlrpcval($toPost['min'], "int"),
		  "ver" => new xmlrpcval(2, "int")
		);
		if(isset($toPost['tags']) && !empty($toPost['tags'])){
			$tags = array('taglist' => new xmlrpcval($toPost['tags'], "string"));
			$post["props"] = new xmlrpcval($tags, "struct");
		}
		
		// требуется для апдейта, иначе не имеет ни какого влияния
		if(isset($toPost['itemid'])){
			$post['itemid'] = new xmlrpcval($toPost['itemid'], "int"); 
		}
 
		if(isset($toPost['security']) && $toPost['security'] == 'private'){
			$post['security'] = new xmlrpcval("private", "string"); 
		}else{
			$post['security'] = new xmlrpcval("public", "string"); 
		}
		// на основе массива создаем структуру
		$post2 = array(
		  new xmlrpcval($post, "struct")
		);
		
		// создаем XML сообщение для сервера
		if(isset($toPost['itemid'])){
			$f = new xmlrpcmsg('LJ.XMLRPC.editevent', $post2); // апдейт поста
		}else{
			$f = new xmlrpcmsg('LJ.XMLRPC.postevent', $post2); //публикация поста
		}
		
		// описываем сервер
		$c = new xmlrpc_client("/interface/xmlrpc", "www.livejournal.com", 80);
		$c->request_charset_encoding = "UTF-8";
		
		// отправляем XML сообщение на сервер
		$r = $c->send($f);
		
		// анализируем результат
		if(!$r->faultCode()) {
			
			  // сообщение принято успешно и вернулся XML-результат
			  $v = php_xmlrpc_decode($r->value());
			 // print_r($v);
			  return $v;
			  
		} else {
			  // сервер вернул ошибку
			  throw new LJ_Login_Exception(htmlspecialchars($r->faultString()));
		}
	}
	
}
?>