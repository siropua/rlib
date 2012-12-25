<?php

class rURLs{

	private $use_iconv = false;
	private $maxLength = 50;
	protected $langsAvaiable = array('ru', 'en');
	protected $curLang = 'ru';

	function __construct($use_iconv = false){
		$this->use_iconv = (bool)$use_iconv;
		$this->parseURI();
	}


	function translit($text){

		if($this->use_iconv) return $this->translit_iconv($text);

		return $this->translit_func($text);
	}

	function translit_iconv($text){
		return iconv("utf-8", "us-ascii//TRANSLIT", $text);
	}

	public static function translit_func($string){
		$string = iconv('UTF-8', 'CP1251', $string);


		static $LettersFrom = "абвгдезиклмнопрстуфыэйхё";
		static $LettersTo   = "abvgdeziklmnoprstufyejxe";
		static $Consonant = "бвгджзйклмнпрстфхцчшщ";
		static $Vowel = "аеёиоуыэюя";

		static $BiLetters = array(
		 "ж" => "zh", "ц"=>"ts", "ч" => "ch",
		 "ш" => "sh", "щ" => "sch", "ю" => "ju", "я" => "ja",
		);

		static $bigToSmall = array(
			"А"	=> "a",	"Б" => "б",	"В" => "в",	"Г" => "г",	"Д" => "д",	"Е" => "е",
			"Ё" => "ё",	"Ж" => "ж",	"З" => "з",	"И" => "и",	"Й" => "й",	"К" => "к",
			"Л" => "л",	"М" => "м",	"Н" => "н",	"О" => "о",	"П" => "п",	"Р" => "р",
			"С" => "с",	"Т" => "т",	"У" => "у",	"Ф" => "ф",	"Х" => "х",	"Ц" => "ц",
			"Ч" => "ч",	"Ш" => "ш",	"Щ" => "щ",	"Ъ" => "ъ",	"Ы" => "ы",	"Ь" => "ь",
			"Э" => "э",	"Ю" => "ю",	"Я" => "я" );



		//here we replace ъ/ь
		$string = preg_replace("/(ь|ъ)([".$Vowel."])/", "j\\2", $string);
		$string = preg_replace("/(ь|ъ)/", "", $string);



		$string = strtr($string, $bigToSmall );
		$string = strtr($string, $LettersFrom, $LettersTo );
		$string = strtr($string, $BiLetters );

		$string = preg_replace("/j{2,}/", "j", $string);
		//$string = preg_replace("/[^".$slash.$reverse."0-9a-z_\-]+/", "", $string);

		$string = iconv('CP1251', 'UTF-8', $string);

		return $string;
	}

	public static function cleanURL($url, $dots = false){
		//$url = preg_replace('~[^\\pL0-9_'.($dots?'':'.').']+~u', '-', $url);
		$url = strtolower($url);
		$url = preg_replace('~[^a-z0-9_'.(!$dots?'':'.').']+~', '-', $url);
		$url = preg_replace('~-{2,}~', '-', $url);
		$url = trim($url, "- ");


		return $url;
	}


	function URLize($title, $dots = false){
		return $this->cleanURL(substr($this->translit($title), 0, $this->maxLength), $dots);
	}


	function Filename($title){
		return $this->cleanURL($this->translit($title), true);
	}

	public function setMaxLen($maxLen){
		$this->maxLength = (int)$maxLen;
	}

	public static function URL($title, $dots = false, $maxLength = 50){
		return self::cleanURL(substr(self::translit_func($title), 0, $maxLength), $dots);
	}

	public function parseURI($URL = false){
		if(!$URL){
			if(defined('SELF_URL_FULL')) 
				$URL = SELF_URL_FULL; 
			else 
				return false;
		}

		if(!$this->pathInfo = parse_url($URL)) return false;

		$this->pathInfo['original_path'] = $this->pathInfo['path'];
		if(strpos($this->pathInfo['path'], ROOT_URL) === 0){
			$this->pathInfo['path'] = '/'.substr($this->pathInfo['path'], strlen(ROOT_URL));
		}

		if(!isset($this->pathInfo['query'])) $this->pathInfo['query'] = '';

		$tmp = preg_replace('~(/|(\.[a-z]{3,4}))?$~iU', '', $this->pathInfo['path']);
		$this->pathInfo['raw_parts'] = explode('/', urldecode($tmp));
		unset($this->pathInfo['raw_parts'][0]);
		$this->pathInfo['parts'] = array_map(array($this, 'URLize'), $this->pathInfo['raw_parts']);


		return $this->pathInfo;
	}


	public function path($part = 0, $raw = false){
		$part = (int)$part;
		if(!$part) return $this->pathInfo['path'];

		$key = $raw ? 'raw_parts' : 'parts';
		
		if(!empty($this->pathInfo[$key][1]) && $this->setCurLang($this->pathInfo[$key][1])){
			$part++;
		}
		
		if(!isset($this->pathInfo[$key][$part])) return false;
		return $this->pathInfo[$key][$part];
	}
	
	/**
	*	Returns safe path
	**/
	public function safePath(){
		return trim(implode('/', $this->pathInfo['parts']), '/ ');
	}
	
	/**
	* Получить Абсолютный URL
	* @param mixed $url
	* @param mixed $base
	* @return mixed
	*/
	public static function getAbsoluteURL($url, $base = SELF_URL){
		if(preg_match('/^http[s]?:\/\//', $url))
			return $url;
        
		if(!$base)
			$base = SELF_URL;

		if(!preg_match('/^http[s]?:\/\//', $base))
			$base = SERVER_URL . ltrim($base, '/ ');

		$url_parts = parse_url($base);
		$new_url = $url_parts['scheme']."://".
				  (@$url_parts['user']?@$url_parts['user'].":".@$url_parts['pass']."@":"").
				  $url_parts['host'];

		if(substr($url, 0, 1) == "/")
		{
			$new_url .= $url;
		}
		else
		{
			if(substr($url_parts['path'], -1) == '/')
				$url_parts['path'] .= 'index.html';
			
			$dir = str_replace("\\", "/", dirname($url_parts['path']));
			$dir = rtrim($dir, "/");
			$new_url .= $dir."/".$url;
		}
		return $new_url;
	}
	
	/**
	* Редиректим на "правильный" URL - либо на URL/ либо на URL.html
	*/
	public static function redirect2RightURL($preferPostfix = '/'){
		if(!empty($_SERVER['REQUEST_METHOD']) && (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET')){
			$uri = parse_url($_SERVER['REQUEST_URI']);
			$path = pathinfo($uri['path']);
			$redirect = false;
			if(!empty($path['extension'])){
				if($path['extension'] == 'htm'){
					$redirect = $path['dirname'].'/'.$path['filename'].'.html';
				}
			}else{
				// убираем точку
				if($path['basename'] != $path['filename']) 
					$uri['path'] = $path['dirname'].'/'.$path['filename'];
				
				if(substr($uri['path'], -1) != '/')
					$redirect = $uri['path'].$preferPostfix;
			}
			if($redirect && !empty($uri['query'])) $redirect .= '?'.$uri['query'];
			if($redirect) self::redirect($redirect);
		}
	}

	
	/**
	* Send redirection header and exit.
	* @param mixed $url
	* @param mixed $base
	* @return void
	*/
	public static function redirect($url, $base = SELF_URL){
        header('Location: '.self::getAbsoluteURL($url, $base));
		exit;
	}
	
	public static function reloadPage(){
		self::redirect(SELF_URL);
		exit;
	}
	

	/**
	* pathCount
	* @return mixed
	*/
	public function pathCount(){
		$c = count($this->pathInfo['parts']);
		if(!empty($this->pathInfo['parts'][1]) && $this->setCurLang($this->pathInfo['parts'][1])){
			$c--;
		}
		return $c;
	}


	public function pathType($part){
	
		if(!empty($this->pathInfo['parts'][1]) && $this->setCurLang($this->pathInfo['parts'][1])){
			$part++;
		}
	
	
		// не является последним параметром? папка!
		if($part < count($this->pathInfo['parts'])){
			return 'folder';
		}

		// вообще не существуеты
		if($part > count($this->pathInfo['parts']))
			return NULL;

		// заканчивается «/»? папка!
		if(substr($this->pathInfo['path'], -1) == '/')
			return 'folder';

		// имеет расширение? файл!
		if(preg_match('~\.[a-z0-9]{2,4}$~i', $this->pathInfo['path']))
			return 'file';

		// папка
		return 'folder';
	}



	/**
	* Устанавливает текущий язык сайта
	* @param mixed $lang
	* @return bool
	*/
	public function setCurLang($lang){
		$lang = trim($lang);
		if($lang == $this->curLang) return true;
		if(!in_array($lang, $this->langsAvaiable)) return false;
		$this->curLang = $lang;
		return true;
	}
	
	/**
	* Определение текущего языка сайта
	* @return mixed
	*/
	public function getCurLang(){
		$this->path(1);
		return $this->curLang;
	}	
	
	/**
	* Определение ссылки языка
	* @param mixed $lang
	* @param bool $link
	* @return mixed
	*/
	public function getLangLink($lang, $link = false){
		if(!$link)
			$link = $_SERVER['REQUEST_URI'];
		
		// если в адресе уже есть признак языка
		if(!empty($this->pathInfo['parts'][1]) && $this->setCurLang($this->pathInfo['parts'][1])){
			return preg_replace('~^/[a-z]{2}/~i', '/'.$lang.'/', $link);
		}
		
		return '/'.$lang.$link;
	}	


	public function getHost(){
		return $this->pathInfo['host'];
	}
	
	
	
	/**
	* Парсим урл статически
	* @return void
	**/
	public static function getURLPart($partN = 1, $URL = NULL){
		if(!$URL){
			if(defined('SELF_URL_FULL')) 
				$URL = SELF_URL_FULL; 
			else 
				return false;
		}

		$urlPath = parse_url($URL);
		$urlPath = explode('/', $urlPath['path']);
		if(empty($urlPath[$partN])) 
			return false;
		else
			return $urlPath[$partN];
	}
	
	
	public function get($url, $default = null){
		return isset($_GET[$url]) ? $_GET[$url] : $default;
	}
	
	/**
	* uniPath
	* @param mixed $path
	* @param mixed $base
	* @return mixed
	*/
	public function uniPath($path, $base){
		if(substr($path, 0, 1) == '/') return $path;
		if(strtolower(substr($path, 0, 7)) == 'http://' || strtolower(substr($path, 0, 8)) == 'https://') return $path;
		return $base.$path;
	}


}