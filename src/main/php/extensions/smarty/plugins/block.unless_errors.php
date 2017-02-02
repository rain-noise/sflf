<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:	 block.unless_errors.php
 * Type:	 block
 * Name:	 unless_errors
 * Params:
 *  - name (optional) : name of error key (default all)
 * Purpose:  エラーメッセージが存在しない場合にコンテンツを表示します
 * -------------------------------------------------------------
 */
function smarty_block_unless_errors($params, $content, &$smarty, &$repeat)
{
	require_once(SMARTY_PLUGINS_DIR . 'block.if_errors.php');
	return empty(smarty_block_if_errors($params, $content, $smarty, $repeat)) ? $content : null ;
}
