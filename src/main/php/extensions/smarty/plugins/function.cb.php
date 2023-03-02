<?php
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.cb.php
 * Type:     function
 * Name:     cb
 * Params:
 *  - file     (optional) : file path of asset file
 * Purpose:  Cache Busting 用のパラメータを出力します
 * -------------------------------------------------------------
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
function smarty_function_cb($params, &$smarty)
{
	$assets_root = defined('ASSETS_ROOT') ? ASSETS_ROOT : '' ;
	if($file = isset($params['file']) ? $params['file'] : null && file_exists($assets_root.$file)) {
		return "v=".filemtime($assets_root.$file);
	}
	return defined('CACHE_BUSTING') ? CACHE_BUSTING : '' ;
}
