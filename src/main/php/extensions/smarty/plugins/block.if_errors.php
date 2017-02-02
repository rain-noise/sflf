<?php
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:	 block.if_errors.php
 * Type:	 block
 * Name:	 if_errors
 * Params:
 *  - name (optional) : name of error key (default all)
 * Purpose:  エラーメッセージが存在する場合にコンテンツを表示します。
 * -------------------------------------------------------------
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_block_if_errors($params, $content, &$smarty, &$repeat)
{
	if (is_null($content)) { return; }

	// ---------------------------------------------------------
	// パラメータ解析
	// ---------------------------------------------------------
	$name = isset($params['name']) ? $params['name'] : null ;
	
	// ---------------------------------------------------------
	// コンテンツ出力
	// ---------------------------------------------------------
	$errors = $smarty->getTemplateVars('errors');
	if(empty($errors)) { return null; }
	
	if($name == null) { return $content; }
	if(isset($errors[$name]) && !empty($errors[$name])) { return $content; }
	
	return null;
}
