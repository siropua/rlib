<?php

/**
* Пример использования:
$r = new Referer;
if($refData = $r->parse()){
	echo 'Referer parsed: ';
	print_r($refData);
}else{
	echo 'Реферер неправильный';
}
*/


class ref_googleImages{
    static function parse($q){
	@parse_str($q, $query);
	if(!empty($query['prev'])){
	    @parse_str($query['prev'], $prev);
	    $query['prev_parsed'] = $prev;
	    
	    if(!empty($query['prev_parsed']['/images?q'])){
		return array(
		    'page' => 1,
		    'search' => $query['prev_parsed']['/images?q']
		);
	    }
	    
	}
	
	return false;
    }
}

class ref_stdParser{
    static function parse($q, $qParam){    
	@parse_str($q, $query);
	if(empty($query[$qParam])) return false;
	return array(
	    'page' => 1,
	    'search' => $query[$qParam]
	);
    }
}



class Referer{
	
	private $referer = '';
	private $ignoredHosts = array();
	
	private $se = array(
		'yandex'=>array(
			'url'=>'/text=([^&]+)/',
			'name' => 'Yandex',
			'key' => 'text',
//			'page' => 'p'
		),
		'images\.google'=>array(
			'url'=>'/%3Fq%3D([^&]+)%26/U',
			'reurl' => true,
			'name' => 'GoogleImages',
			'class' => 'googleImages'
		),		
		'google\.[a-z]+\/imgres'=>array(
			'url'=>'/%3Fq%3D([^&]+)%26/U',
			'reurl' => true,
			'name' => 'GoogleImages',
			'class' => 'googleImages',
			'full_url_scan' => true
		),		
		
		
		'google\.'=>array(
			'url'=>'/[?&]q=([^&]+)/',
			'resN'=>1,
			'name' => 'Google',
			'key' => 'q'
		),
		'search\.live\.com'=>array(
			'url'=>'/[?&]q=([^&]+)/',
			'resN'=>1,
			'name' => 'Live',
			'key' => 'q'
		),
		'rambler\.ru'=>array(
			'url'=>'/words=([^&]+)/',
			'name' => 'Rambler',
			'iconv' => 'CP1251',
			'key' => 'words'
		),
		'meta\.'=>array( 
		    'url'=>'/q=([^&]+)/',
		    'resN'=>1,
			'name' => 'MetaUA',
			'iconv' => 'CP1251',
		    'key' => 'q'
		),
		'mail\.ru'=>array(
		    'url'=>'/[?&]q=([^&]+)/',
		    'resN'=>1,
			'name' => 'MailRu',
		    'key' => 'q'
		),
		'rsdn\.ru'=>array(
		    'url'=>'/message\/([0-9.]+)\./',
			'part' => 'path',
			'name' => 'RSDN'
		),
		'bigmir\.net'=>array(
		    'url'=>'/[?&]q=([^&]+)/',
		    'resN'=>1,
			'name' => 'Bigmir',
		    'key' => 'q'
		),
		'gorod\.dp\.ua'=>array(
		    'url'=>'/t=([0-9]+)/',
			'name' => 'GorodDpUa',
			'key' => 't'
		),
		'conduit\.com' => array(
		    'name' => 'Conduit',
		    'key' => 'q',
		    'url' => '/q=([^&]+)/'
		),
		'yahoo\.com' => array(
		    'name' => 'Yahoo',
		    'key' => 'p',
		    'url' => '/p=([^&]+)/'
		),
		
	);
	
	function __construct(){
		if(isset($_SERVER['HTTP_REFERER']))
			$this->setReferer($_SERVER['HTTP_REFERER']);
		
		if(isset($_SERVER['HTTP_HOST']))
			$this->ignoreHost($_SERVER['HTTP_HOST']);
	}
	
	// задаем свой реферер (если надо)
	function setReferer($ref){
		$this->referer = $ref;
	}
	
	// добавляет в список игнорируемых хостов
	function ignoreHost($host){
		if(is_array($host)) 
			foreach($host as $h)
				$this->ignoredHosts[] = $this->removeWWW($h);
		else
			$this->ignoredHosts[] = $this->removeWWW($host);
	}
	
	function clearIgnored(){
		$this->ignoredHosts = array();
	}
	
	function parse($ref = ''){
		
		if($ref) $this->setReferer($ref);
		
		// если урл реферера не парсится - выходим
		if(!$this->referer) return false;
		if(!$urlData = parse_url($this->referer)) return false;
		
		// хост в списке игнорируемых?
		if(in_array($urlData['host'], $this->ignoredHosts)) return false;
		
		foreach($this->se as $key => $data){
			// ищем подходящее правила парсинга
			
			$full_url_scan = empty($data['full_url_scan']) ? false : $data['full_url_scan'];
			if(!preg_match("~$key~", $full_url_scan ? $this->referer : $urlData['host'])) continue;
			
			$part = isset($data['part']) ? $data['part'] : 'query';
			

			if(empty($urlData[$part])) continue;
			
			if($part == 'query'){
			
				$query = array();
				if(!empty($data['class']) && class_exists('ref_'.$data['class'])){
				    $class = 'ref_'.$data['class'];
				    $class = new $class;
				    $query = $class->parse($urlData['query']);
				}elseif(!empty($data['key'])){
				    $class = new ref_stdParser;
				    $query = $class->parse($urlData['query'], $data['key']);
				}
				$query['search_engine'] = $data['name'];
				$query['domain'] = $urlData['host'];
				$query['url'] = $this->referer;
				
				
				
				if(!empty($query['search']))
				    return $query;
				
			}
			
			
			if(!preg_match($data['url'], $urlData[$part], $match)) continue;
			
			$resN = isset($data['resN']) ? (int)$data['resN'] : 1;
			
			$r = urldecode($match[$resN]);
			
			if(isset($data['reurl'])){
				$r = urldecode($r);
			}
			
			if(isset($data['iconv']))
				$r = iconv($data['iconv'], 'UTF-8', $r);
			
			return array(
				'search' => $r,
				'search_engine' => $data['name'],
				'url' => $this->referer,
				'domain' => $urlData['host']
			);
			
			
		}
		
		return array(
			'search' => false,
			'search_engine' => '',
			'url' => $this->referer,
			'domain' => $urlData['host']
		);
		
	}
	
	function removeWWW($host){
		return preg_match('~^https?://~i', $host) ? 
			preg_replace('~://www\.~i', '://', $host) : preg_replace('~^www\.~i', '', $host);
	}
	
	
}