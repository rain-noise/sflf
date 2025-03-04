<?php
// use App\Core\Token; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 *
 * ■トークン hidden タグ出力
 *
 * -------------------------------------------------------------
 * File:     function.token.php
 * Type:     function
 * Name:     token
 * Params:
 *  - key  (optional) : name of token key (default 'global')
 *  - name (optional) : name of hidden form name (default 'token')
 * Purpose:  トークンを hidden タグで出力する
 * -------------------------------------------------------------
 *
 * @see       https://github.com/rain-noise/sflf/blob/master/src/main/php/Sflf/Token.php
 *
 * @package   SFLF
 * @version   v4.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param array{
 *   key?: string|null,
 *   name?: string|null,
 * }              $params  パラメータ
 * @param Smarty\Smarty &$smarty テンプレートオブジェクト
 * @return string
 */
function smarty_function_token($params, &$smarty)
{
    $name  = isset($params['name']) ? $params['name'] : 'token' ;
    $token = Token::get(isset($params['key']) ? $params['key'] : 'global');
    return '<input type="hidden" name="'.$name.'" value="'.$token.'" />';
}
