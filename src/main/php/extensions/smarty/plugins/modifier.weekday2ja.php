<?php
/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 *
 * ■曜日変換
 *
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     modifier.weekday2ja.php
 * Type:     modifier
 * Name:     weekday2ja
 * Params:
 *  - string (required) : Date time with english week day label
 * Purpose:  英語の曜日表記を日本語表記に変換する
 * -------------------------------------------------------------
 *
 * @package   SFLF
 * @version   v4.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param string|null $string 日時文字列
 * @return ($string is null ? null : string)
 */
function smarty_modifier_weekday2ja($string)
{
    if (empty($string)) {
        return $string;
    }
    return str_replace(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'], ['日', '月', '火', '水', '木', '金', '土'], $string);
}
