<?php
/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 * 
 * ■ドメイン表示ファンクション
 * 
 * -------------------------------------------------------------
 * File:     function.domains.php
 * Type:     function
 * Name:     domains
 * Params:
 *  - type       (required)          : output type (option|checkbox|radio|plain|label)
 *  - domain     (required)          : domain class name or array of domains(any objects which has value and label field also ok)
 *  - selected   (optional)          : selected values (value or array : default no selected)
 *  - value      (optional)          : value field name (default 'value')
 *  - label      (optional)          : label field name (default 'label')
 *  - check      (optional)          : check field name for include/exclude value check (default same of 'value' option)
 *  - include    (optional)          : comma separated or array include output value
 *  - exclude    (optional)          : comma separated or array exclude output value
 *  - delimiter  (optional)          : tag delimiter (default : ' ')
 *  - null_label (optional)          : null label (default : '')
 *  - case       (optional)          : workflow - case code (default : null)
 *  - current    (optional)          : workflow - current doamin value (default : null)
 *  - prevtag    (optional)          : html code of previous position for each of items (default : '')
 *  - posttag    (optional)          : html code of post position for each of items (default : '')
 *  - addable    (optional)          : add to select option using unexsits selected value (default : false)
 *  - {tag_attr} (optional)          : html tag attribute and value like id, class, name, style, data-*
 * Purpose:  ドメイン選択フォーム及びラベルを表示する
 * -------------------------------------------------------------
 * 
 * @see       https://github.com/rain-noise/sflf/blob/master/src/main/php/Sflf/Domain.php
 * 
 * @package   SFLF
 * @version   v1.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_function_domains($params, &$smarty)
{
	// ---------------------------------------------------------
	// パラメータ解析
	// ---------------------------------------------------------
	$domain     = isset($params['domain']) ? $params['domain'] : trigger_error("error: missing 'domain' parameter", E_USER_NOTICE) ;
	$selected   = isset($params['selected']) ? (is_array($params['selected']) ? $params['selected'] : array($params['selected'])) : array() ;
	$value      = isset($params['value']) ? $params['value'] : 'value' ;
	$label      = isset($params['label']) ? $params['label'] : 'label' ;
	$check      = isset($params['check']) ? $params['check'] : $value ;
	$type       = isset($params['type']) ? $params['type'] : trigger_error("error: missing 'type' parameter", E_USER_NOTICE) ;
	if(!in_array($type, array('option','checkbox','radio','plain','label'))) {
		trigger_error("error: invalid 'type' parameter : {$type}", E_USER_NOTICE);
	}
	$include    = isset($params['include']) ? (is_array($params['include']) ? $params['include'] : explode(',', $params['include'])) : array() ;
	$exclude    = isset($params['exclude']) ? (is_array($params['exclude']) ? $params['exclude'] : explode(',', $params['exclude'])) : array() ;
	$delimiter  = isset($params['delimiter']) ? $params['delimiter'] : ' ' ;
	$null_label = isset($params['null_label']) ? $params['null_label'] : '' ;
	$case       = isset($params['case']) ? $params['case'] : null ;
	$current    = isset($params['current']) ? $params['current'] : null ;
	$prevtag    = isset($params['prevtag']) ? $params['prevtag'] : '' ;
	$posttag    = isset($params['posttag']) ? $params['posttag'] : '' ;
	$addable    = isset($params['addable']) ? $params['addable'] : false ;
	$name  = '';
	$attrs = '';
	foreach ($params AS $k => $v) {
		if(in_array($k, array('id','domain','selected','value','label','check','type','include','exclude','delimiter','null_label','case','current','prevtag','posttag','addable'))) { continue; }
		$attrs .= $k.'="'.$v.'" ';
		if($k == 'name') { $name = $v; }
	}
	$attrs = trim($attrs);
	
	// ---------------------------------------------------------
	// コンテンツ出力
	// ---------------------------------------------------------
	if($type === 'label' && empty($selected) && !empty($null_label)) {
		return $prevtag.$null_label.$posttag;
	}
	
	$html  = "";
	$lists = is_string($domain) ? in_array($type, ['plain', 'label']) ? $domain::lists() : $domain::nexts($current, $case) : $domain ;
	foreach ($lists AS $d) {
		$v = $d->$value;
		$l = empty($d->$label) ? $null_label : $d->$label ;
		$c = $d->$check;
		
		if(!empty($include) && !in_array($c, $include)) { continue; }
		if(!empty($exclude) &&  in_array($c, $exclude)) { continue; }
		
		switch ($type) {
			case 'option':
				$select = in_array($v, $selected) ? ' selected' : '';
				$html .= '<option '.$attrs.' value="'.htmlspecialchars($v).'"'.$select.'>'.$prevtag.$l.$posttag.'</option>'.$delimiter;
				break;
			case 'checkbox':
				$select = in_array($v, $selected) ? ' checked' : '';
				$html .= $prevtag.'<input id="'.$name.'_'.$v.'" type="checkbox" '.$attrs.' value="'.htmlspecialchars($v).'"'.$select.'/><label for="'.$name.'_'.$v.'">'.htmlspecialchars($l).'</label>'.$posttag.$delimiter;
				break;
			case 'radio':
				$select = in_array($v, $selected) ? ' checked' : '';
				$html .= $prevtag.'<input id="'.$name.'_'.$v.'" type="radio" '.$attrs.' value="'.htmlspecialchars($v).'"'.$select.'/><label for="'.$name.'_'.$v.'">'.htmlspecialchars($l).'</label>'.$posttag.$delimiter;
				break;
			case 'plain':
				$html .= $prevtag.htmlspecialchars($l).$posttag.$delimiter;
				break;
			case 'label':
				if(in_array($v, $selected)) {
					$html .= $prevtag.htmlspecialchars($l).$posttag.$delimiter;
				}
				break;
		}
	}
	
	if($addable && $type == 'option') {
		$domain_values = [];
		foreach ($lists AS $d) {
			$domain_values[] = $d->value;
		}

		foreach ($selected as $v) {
			if(!in_array($v, $domain_values)) {
				$html .= '<option '.$attrs.' value="'.htmlspecialchars($v).'" selected>'.$prevtag.htmlspecialchars($v).$posttag.'</option>'.$delimiter;
			}
		}
	}
	
	return preg_replace("/".preg_quote($delimiter, '/')."$/", "", $html);
}
