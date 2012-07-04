<?php

class rURLs{

	private $use_iconv = false;
	private $maxLength = 50; // ìàêñèìàëüíàÿ äëèííà URLà

	function __construct($use_iconv = false){
		$this->use_iconv = (bool)$use_iconv;
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


		static $LettersFrom = "àáâãäåçèêëìíîïðñòóôûýéõ¸";
		static $LettersTo   = "abvgdeziklmnoprstufyejxe";
		static $Consonant = "áâãäæçéêëìíïðñòôõö÷øù";
		static $Vowel = "àå¸èîóûýþÿ";

		static $BiLetters = array(
		 "æ" => "zh", "ö"=>"ts", "÷" => "ch",
		 "ø" => "sh", "ù" => "sch", "þ" => "ju", "ÿ" => "ja",
		);

		static $bigToSmall = array(
			"À"	=> "a",	"Á" => "á",	"Â" => "â",	"Ã" => "ã",	"Ä" => "ä",	"Å" => "å",
			"¨" => "¸",	"Æ" => "æ",	"Ç" => "ç",	"È" => "è",	"É" => "é",	"Ê" => "ê",
			"Ë" => "ë",	"Ì" => "ì",	"Í" => "í",	"Î" => "î",	"Ï" => "ï",	"Ð" => "ð",
			"Ñ" => "ñ",	"Ò" => "ò",	"Ó" => "ó",	"Ô" => "ô",	"Õ" => "õ",	"Ö" => "ö",
			"×" => "÷",	"Ø" => "ø",	"Ù" => "ù",	"Ú" => "ú",	"Û" => "û",	"Ü" => "ü",
			"Ý" => "ý",	"Þ" => "þ",	"ß" => "ÿ" );



		//here we replace ú/ü
		$string = preg_replace("/(ü|ú)([".$Vowel."])/", "j\\2", $string);
		$string = preg_replace("/(ü|ú)/", "", $string);



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

}