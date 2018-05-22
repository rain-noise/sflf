<?php
/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 * 
 * ■デフォルト値設定ファンクション
 * 
 * -------------------------------------------------------------
 * File:     function.default.php
 * Type:     function
 * Name:     default
 * Params:
 *  - var     (required) : name of var
 *  - default (required) : default value
 * Purpose:  指定の変数が未定義／未設定の場合にデフォルト値をセットする
 * -------------------------------------------------------------
 * 
 * @package   SFLF
 * @version   v1.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_function_default($params, &$smarty)
{
	$var     = isset($params['var']) ? $params['var'] : trigger_error("error: missing 'var' parameter", E_USER_NOTICE) ;
	$value   = $smarty->getTemplateVars($var);
	$default = array_key_exists('default', $params) ? $params['default'] : trigger_error("error: missing 'default' parameter", E_USER_NOTICE);
	
	if(empty($value)) {
		$smarty->assign($var, $default);
	}
}
