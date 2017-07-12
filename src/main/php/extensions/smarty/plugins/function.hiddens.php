<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.hiddens.php
 * Type:     function
 * Name:     hiddens
 * Params:
 *  - form        (required) : form (discribe target) object
 *  - include     (optional) : comma separated include output filed name
 *                             if form contains multiple field, the name of index like '[]', '[0]' will be ignored.
 *                             so you should write 'include' param like below,
 *                              - field                            : name                   => name
 *                              - multiple field                   : hobbies[]              => hobbies
 *                              - sub form field                   : bank[holder]           => bank[holder]      # you should set parent field 'bank' too.
 *                              - multiple sub form field          : children[0][name]      => children[name]    # you should set parent field 'children' too.
 *                              - multiple sub form multiple field : children[0][hobbies][] => children[hobbies] # you should set parent field 'children' too.
 *  - exclude     (optional) : comma separated exclude output filed name
 *                             you should write 'exclude' param like 'include', but you don't need set parent field if you want to exclude some of sub form fields.
 *  - date_format (optional) : date format of DateTime (default 'Y-m-d H:i:s')
 * Purpose:  <input type="hidden" /> タグを出力する
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
	smarty_function_hiddens__generate($hiddens, $form, $include, $exclude, $date_format);
	return join("\n", $hiddens);
}

function smarty_function_hiddens__generate(&$hiddens, $form, $include, $exclude, $date_format, $name_prefix = '') {
	foreach ($form AS $key => $value) {
		$key     = empty($name_prefix) ? $key : "{$name_prefix}[{$key}]" ;
		$matcher = preg_replace('/\[[0-9]*\]/', '', $key);
		if(!empty($include) && !in_array($matcher, $include)) { continue; }
		if(!empty($exclude) &&  in_array($matcher, $exclude)) { continue; }
		
		if(is_array($value)) {
			foreach ($value AS $i => $v) {
				if($v instanceof Form) {
					smarty_function_hiddens__generate($hiddens, $v, $include, $exclude, $date_format, "{$key}[{$i}]");
				} else {
					smarty_function_hiddens__append_tag($hiddens, "{$key}[]", $v, $date_format);
				}
			}
		} else {
			if($value instanceof Form) {
				smarty_function_hiddens__generate($hiddens, $value, $include, $exclude, $date_format, $key);
			} else {
				smarty_function_hiddens__append_tag($hiddens, $key, $value, $date_format);
			}
		}
	}
	
	return;
}

function smarty_function_hiddens__append_tag(&$hiddens, $name, $value, $date_format) {
	$value = $value instanceof DateTime ? $value->format($date_format) : $value ;
	$hiddens[] = '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars("".$value).'" />';
	return;
}