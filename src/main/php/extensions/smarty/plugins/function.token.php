<?php
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.token.php
 * Type:     function
 * Name:     token
 * Params:
 *  - key  (optional) : name of token key (default 'global')
 *  - name (optional) : name of hidden form name (default 'token')
 * Purpose:  トークンを出力する
 * -------------------------------------------------------------
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_function_token($params, &$smarty)
{
	$name  = isset($params['name']) ? $params['name'] : 'token' ;
	$token = Token::get(isset($params['key']) ? $params['key'] : 'global');
	return '<input type="hidden" name="'.$name.'" value="'.$token.'" />';
}
