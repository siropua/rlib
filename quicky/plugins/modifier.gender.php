<?php
/**
 * Quicky plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Quicky plugin
 *
 * Type:     modifier<br>
 * Name:     gender<br>
 * Date:     Mar 4, 2008
 * Purpose:  return second argument if gender=f else return first
 * Example:  {$gender|gender:'сделал':'сделала'} сделал{$gender|gender:'а'}
 * @version  1.0
 * @author   Steel Ice
 * @param string
 * @return string
 */
function quicky_modifier_gender($gender, $m, $f = NULL){
 if( $f == NULL) return $gender == 'f' ? $m : '';
 return $gender == 'f' ? $f : $m;
}