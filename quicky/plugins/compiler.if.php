<?php
function quicky_compiler_if($params,$compiler,$close = FALSE)
{
 if ($close) {return '<?php endif; ?>';}
 if (trim($params) == '') {return $compiler->_syntax_error('Empty condition.');}
 return '<?php if ('.$compiler->_expr_token($params,FALSE,TRUE).'): ?>';
}
