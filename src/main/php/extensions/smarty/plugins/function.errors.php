<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.errors.php
 * Type:     function
 * Name:     errors
 * Params:
 *  - name (optional) : name of error key (default all)
 * Purpose:  エラーメッセージを出力する
 * -------------------------------------------------------------
 */
function smarty_function_errors($params, &$smarty)
{
	// ---------------------------------------------------------
	// パラメータ解析
	// ---------------------------------------------------------
	$name = isset($params['name']) ? $params['name'] : null ;
	
	// ---------------------------------------------------------
	// タグ出力処理
	// ---------------------------------------------------------
	$errors = $smarty->getTemplateVars('errors');
	if(empty($errors)) { return null; }
	
	$html = "";
	if(empty($name)) {
		foreach ($errors AS $error) {
			foreach ($error AS $message) {
				$html .= '<span class="error">'.$message.'</span>';
			}
		}
	} else {
		$error = isset($errors[$name]) ? $errors[$name] : null ;
		if(empty($error)) { return null; }
		
		foreach ($error AS $message) {
			$html .= '<span class="error">'.$message.'</span>';
		}
	}
	
	
	return $html;
}
