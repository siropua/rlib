<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty plugin
 *
 * Type:     modifier<br>
 * Name:     numlabel<br>
 * Date:     Mar 4, 2008
 * Purpose:  use russian declension for numbers
 * Example:  {$num|numlabel:'упячка':'упячки':'упячек'} 1 упячка, 2 упячки, 5 упячек
 * @version  1.0
 * @author   Steel Ice
 * @param string
 * @return string
 */
function smarty_modifier_numlabel($num2, $nomer, $nomera, $nomerov = ''){
 
    $num = (int)$num2;
	
	$num = $num%100;
	if(($num>=5)&&($num<=20))return $nomerov;
	$num = $num%10;
	if($num==1)return $nomer;
	if($num==0)return $nomerov;
	if($num<=4)return $nomera;
	return $nomerov;	
}


?>