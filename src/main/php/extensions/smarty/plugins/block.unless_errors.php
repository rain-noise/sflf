<?php
/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 *
 * ■エラー存在チェック分岐ブロック
 *
 * -------------------------------------------------------------
 * File:	 block.unless_errors.php
 * Type:	 block
 * Name:	 unless_errors
 * Params:
 *  - name (optional) : name of error key (default all)
 * Purpose:  エラーメッセージが存在しない場合にコンテンツを表示します
 * -------------------------------------------------------------
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param array{
 *   name?: string,
 * }              $params  パラメータ
 * @param mixed   $content ブロックで囲われたコンテンツ
 * @param \Smarty &$smarty テンプレートオブジェクト
 * @param bool    &$repeat 繰り返し制御
 * @return mixed|null
 */
function smarty_block_unless_errors($params, $content, &$smarty, &$repeat)
{
    require_once(__DIR__ . 'block.if_errors.php');
    return empty(smarty_block_if_errors($params, $content, $smarty, $repeat)) ? $content : null ;
}
