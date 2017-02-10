<?php
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.hiddens.php
 * Type:     function
 * Name:     hiddens
 * Params:
 *  - form        (required) : form (discribe target) object
 *  - include     (optional) : comma separated include output filed name
 *  - exclude     (optional) : comma separated exclude output filed name
 *  - date_format (optional) : date format of DateTime (default 'Y-m-d H:i:s')
 * Purpose:  <input type="hidden" /> タグを出力する
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 * -------------------------------------------------------------
 */
function smarty_function_hiddens($params, &$smarty)
{
	// ---------------------------------------------------------
	// パラメータ解析
	// ---------------------------------------------------------
	$form        = isset($params['form']) ? $params['form'] : trigger_error("error: missing 'form' parameter", E_USER_NOTICE) ;
	$include     = isset($params['include']) ? explode(',', $params['include']) : array() ;
	$exclude     = isset($params['exclude']) ? explode(',', $params['exclude']) : array() ;
	$date_format = isset($params['date_format']) ? $params['date_format'] : 'Y-m-d H:i:s' ;
	
	if(!empty($include) && !empty($exclude)) {
		trigger_error("error: conflict option 'include' and 'exclude' was set.", E_USER_NOTICE);
	}
	
	// ---------------------------------------------------------
	// タグ出力処理
	// ---------------------------------------------------------
	$hiddens = array();
	
	// インクルード指定時
	if(!empty($include)) {
		foreach ($include AS $key) {
			$value = $form->$key;
			if(is_array($value)) {
				foreach ($value AS $v) {
					$v = $v instanceof DateTime ? $v->format($date_format) : $v ;
					$hiddens[] = '<input type="hidden" name="'.$key.'[]" value="'.htmlspecialchars("".$v).'" />';
				}
			} else {
				$value = $value instanceof DateTime ? $value->format($date_format) : $value ;
				$hiddens[] = '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars("".$value).'" />';
			}
		}
		return join("\n", $hiddens);
	}
	
	foreach ($form AS $key => $value) {
		if(!in_array($key, $exclude)) {
			if(is_array($value)) {
				foreach ($value AS $v) {
					$v = $v instanceof DateTime ? $v->format($date_format) : $v ;
					$hiddens[] = '<input type="hidden" name="'.$key.'[]" value="'.htmlspecialchars("".$v).'" />';
				}
			} else {
				$value = $value instanceof DateTime ? $value->format($date_format) : $value ;
				$hiddens[] = '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars("".$value).'" />';
			}
		}
	}
	
	return join("\n", $hiddens);
}
