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
 * Name:     func<br>
 * Date:     Feb 14, 2008
 * Purpose:  use user function
 * Example:  {$text|func:somefunc}
 * @version  1.0
 * @author   Steel Ice
 * @param string
 * @return string
 */
function quicky_modifier_func($string, $funcName)
{
    return $funcName($string);
}

/* vim: set expandtab: */

?>
