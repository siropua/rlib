<?php
function quicky_compiler_local($params,$compiler)
{
 $this->_def_mode = 'local';
 $expr = $compiler->_expr_token(ltrim($params));
 $this->_def_mode = NULL;
 return '<?php '.$expr.'; ?>';
}
