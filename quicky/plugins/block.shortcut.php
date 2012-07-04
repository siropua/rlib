<?php
function quicky_block_shortcut($params,$content,$compiler)
{
 $block_name = 'shortcut';
 $name = trim($params);
 $content = $compiler->_tag_token($content,$block_name);
 $compiler->_shortcuts[$name] = $content;
 return '';
}
