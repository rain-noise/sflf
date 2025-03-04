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
 * @version   v4.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param array{
 *   var?: string|null,
 *   default?: mixed|null,
 * }              $params  パラメータ
 * @param Smarty\Smarty &$smarty テンプレートオブジェクト
 * @return mixed|null
 */
function smarty_function_default($params, &$smarty)
{
    // ---------------------------------------------------------
    // パラメータ解析
    // ---------------------------------------------------------
    // 必須チェック
    if (!isset($params['var'])) {
        trigger_error("error: missing 'var' parameter", E_USER_NOTICE);
    }
    if (!array_key_exists('default', $params)) {
        trigger_error("error: missing 'default' parameter", E_USER_NOTICE);
    }

    // パラメータ処理
    $var = $params['var'];
    assert(is_string($var));
    $value   = $smarty->getTemplateVars($var);
    $default = $params['default'] ?? null;

    if (empty($value)) {
        $smarty->assign($var, $default);
    }
}
