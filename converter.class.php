<?php


class converter{
	
	
	static function dateRu2MySQL($date){
		list($d,$m,$y) = explode('.', $date);
		return "$y-$m-$d";
	}

	static function dateMySQL2Ru($date){
		if($date == '0000-00-00') return '';
		list($y, $m, $d) = explode('-', $date);
		return "$d.$m.$y";
	}
	
}