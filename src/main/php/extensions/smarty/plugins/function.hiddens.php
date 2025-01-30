<?php

/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 *
 * ■hiddenタグ出力ファンクション
 *
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
 *
 * @see       https://github.com/rain-noise/sflf/blob/master/src/main/php/Sflf/Form.php
 *
 * @package   SFLF
 * @version   v1.0.4
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param array{
 *   form?: object|null,
 *   include?: string|null,
 *   exclude?: string|null,
 *   date_format?: string|null,
 * }              $params  パラメータ
 * @param Smarty\Smarty &$smarty テンプレートオブジェクト
 * @return string
 */
function smarty_function_hiddens($params, &$smarty)
{
    // ---------------------------------------------------------
    // パラメータ解析
    // ---------------------------------------------------------
    // 必須チェック
    if (!isset($params['form'])) {
        trigger_error("error: missing 'form' parameter", E_USER_NOTICE);
    }

    // パラメータ処理
    $form = $params['form'];
    assert(!is_null($form));
    $include     = isset($params['include']) ? explode(',', $params['include']) : [] ;
    $exclude     = isset($params['exclude']) ? explode(',', $params['exclude']) : [] ;
    $date_format = isset($params['date_format']) ? $params['date_format'] : 'Y-m-d H:i:s' ;

    if (!empty($include) && !empty($exclude)) {
        trigger_error("error: conflict option 'include' and 'exclude' was set.", E_USER_NOTICE);
    }

    // ---------------------------------------------------------
    // タグ出力処理
    // ---------------------------------------------------------
    $hiddens = [];
    smarty_function_hiddens__generate($hiddens, $form, $include, $exclude, $date_format);
    return join("\n", $hiddens);
}

/**
 * フォームオブジェクトのプロパティから <hidden/> タグを構築します。
 *
 * @param string[]    &$hiddens    <hidden/>タグ配列
 * @param object      $form        フォーム
 * @param string[]    $include     <hidden/>タグに含める項目
 * @param string[]    $exclude     <hidden/>タグに含めない項目
 * @param string      $date_format 日付フォーマット
 * @param string      $name_prefix name属性のプレフィックス (default: '')
 * @return void
 */
function smarty_function_hiddens__generate(&$hiddens, $form, $include, $exclude, $date_format, $name_prefix = '')
{
    foreach (get_object_vars($form) as $key => $value) {
        $key     = empty($name_prefix) ? $key : "{$name_prefix}[{$key}]" ;
        $matcher = preg_replace('/\[[0-9]*\]/', '', $key);
        if (!empty($include) && !in_array($matcher, $include)) {
            continue;
        }
        if (!empty($exclude) && in_array($matcher, $exclude)) {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $i => $v) {
                if (is_object($v)) {
                    smarty_function_hiddens__generate($hiddens, $v, $include, $exclude, $date_format, "{$key}[{$i}]");
                } else {
                    smarty_function_hiddens__append_tag($hiddens, "{$key}[{$i}]", $v, $date_format);
                }
            }
        } else {
            if (is_object($value)) {
                smarty_function_hiddens__generate($hiddens, $value, $include, $exclude, $date_format, $key);
            } else {
                smarty_function_hiddens__append_tag($hiddens, $key, $value, $date_format);
            }
        }
    }

    return;
}

/**
 * <hidden/> タグを構築して追加します
 *
 * @param string[] &$hiddens    <hidden/>タグ配列
 * @param string   $name        <hidden/>タグの name 属性名
 * @param mixed    $value       <hidden/>タグの value 属性値
 * @param string   $date_format 日付フォーマット
 * @return void
 */
function smarty_function_hiddens__append_tag(&$hiddens, $name, $value, $date_format)
{
    $value     = $value instanceof \DateTime ? $value->format($date_format) : $value ;
    $hiddens[] = '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars("".$value).'" />';
    return;
}
