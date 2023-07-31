<?php
/**
 * Single File Low Functionality Class Tools - Extensions : Smarty Plugin
 *
 * ■日付差分表示ファンクション
 *
 * -------------------------------------------------------------
 * File:     function.date_diff.php
 * Type:     function
 * Name:     date_diff
 * Params:
 *  - from           (required) : start of date
 *  - to             (required) : end of date
 *  - format         (required) : format of date-interval
 *  - format_hour    (optional) : format of date-interval for less than 1 day (default : null)
 *  - format_minute  (optional) : format of date-interval for less than 1 hour (default : null)
 *  - ignore_time    (optional) : ignore time (default false)
 *  - null_label     (optional) : null label (default '')
 *  - positive_label (optional) : positive (+ time) label (default null : display by format)
 *  - zero_label     (optional) : zero (0 time) label (default null : display by format)
 *  - negative_label (optional) : negative (- time) label (default null : display by format)
 * Purpose:  指定の日付の時差を表示します。
 * -------------------------------------------------------------
 *
 * @package   SFLF
 * @version   v1.0.2
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 *
 * @param array{
 *   from?: string|DateTime|null,
 *   to?: string|DateTime|null,
 *   format?: string|null,
 *   format_hour?: string|null,
 *   format_minute?: string|null,
 *   ignore_time?: bool|null,
 *   null_label?: string,
 *   positive_label?: string|null,
 *   zero_label?: string|null,
 *   negative_label?: string|null,
 * }             $params  パラメータ
 * @param Smarty &$smarty テンプレートオブジェクト
 * @return mixed|null
 */
function smarty_function_date_diff($params, &$smarty)
{
    // ---------------------------------------------------------
    // パラメータ解析
    // ---------------------------------------------------------
    // 必須チェック
    if (!isset($params['from']) && !is_null($params['from'])) {
        trigger_error("error: missing 'from' parameter", E_USER_NOTICE);
    }
    if (!isset($params['to']) && !is_null($params['to'])) {
        trigger_error("error: missing 'to' parameter", E_USER_NOTICE);
    }
    if (!isset($params['format'])) {
        trigger_error("error: missing 'format' parameter", E_USER_NOTICE);
    }

    // パラメータ処理
    $ignore_time    = isset($params['ignore_time']) ? $params['ignore_time'] : false ;
    $from           = $params['from'] ;
    if (!empty($from)) {
        $from       = $from instanceof DateTime ? clone $from : new DateTime($from) ;
        $from       = $ignore_time ? clone $from->setTime(0, 0, 0) : $from ;
    }
    $to             = $params['to'];
    if (!empty($to)) {
        $to         = $to instanceof DateTime ? clone $to : new DateTime($to) ;
        $to         = $ignore_time ? $to->setTime(0, 0, 0) : $to ;
    }
    $format         = $params['format'];
    assert(is_string($format));
    $format_hour    = isset($params['format_hour']) ? $params['format_hour'] : null ;
    $format_minute  = isset($params['format_minute']) ? $params['format_minute'] : null ;
    $null_label     = isset($params['null_label']) ? $params['null_label'] : '' ;
    $positive_label = isset($params['positive_label']) ? $params['positive_label'] : null ;
    $zero_label     = isset($params['zero_label']) ? $params['zero_label'] : null ;
    $negative_label = isset($params['negative_label']) ? $params['negative_label'] : null ;

    if (empty($from) || empty($to)) {
        return $null_label;
    }
    if (!empty($positive_label) && $from < $to) {
        return $positive_label;
    }
    if (!empty($zero_label) && $from == $to) {
        return $zero_label;
    }
    if (!empty($negative_label) && $from > $to) {
        return $negative_label;
    }
    $interval = date_diff($from, $to);

    if (!empty($format_minute) && $interval->y == 0 && $interval->m == 0 && $interval->d == 0 && $interval->h == 0) {
        return $interval->format($format_minute);
    }
    if (!empty($format_hour) && $interval->y == 0 && $interval->m == 0 && $interval->d == 0) {
        return $interval->format($format_hour);
    }

    return $interval->format($format);
}
