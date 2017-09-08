<?php
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.date_diff.php
 * Type:     function
 * Name:     date_diff
 * Params:
 *  - from       (required) : start of date
 *  - to         (required) : end of date
 *  - format     (required) : format of date-interval
 *  - null_label (optional) : null label (default '')
 * Purpose:  指定の日付の時差を表示します。
 * -------------------------------------------------------------
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_function_date_diff($params, &$smarty)
{
	$from       = isset($params['from']) ? $params['from'] : null ;
	$from       = $from instanceof DateTime ? $from : new DateTime($from) ;
	$to         = isset($params['to']) ? $params['to'] : null ;
	$to         = $to instanceof DateTime ? $to : new DateTime($to) ;
	$format     = isset($params['format']) ? $params['format'] : trigger_error("error: missing 'format' parameter", E_USER_NOTICE) ;
	$null_label = isset($params['null_label']) ? $params['null_label'] : '' ;
	
	if(empty($from) || empty($to)) { return $null_label; }
	$interval = date_diff($from, $to);
	return $interval->format($format);
}
