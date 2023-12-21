<?php
// 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）
//namespace App\Domain;
//
//use App\Domain\Base\RangeDomain;

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■分ドメイン
 *
 * システムで扱う時刻が 5分刻み／15分刻み などの場合は step／end 定義を変えて下さい
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Minute extends RangeDomain
{
    /**
     * 開始の分を取得します
     *
     * @return int 開始分
     */
    public static function start()
    {
        return  0;
    }

    /**
     * 分の増分を取得します
     *
     * @return int 増分分
     */
    public static function step()
    {
        return  1;
    }

    /**
     * 終了の分を取得します
     *
     * @return int 終了分
     */
    public static function end()
    {
        return 59;
    }

    /**
     * 指定の分をフォーマットします。
     *
     * @param int $i 分
     * @return string ラベル文字列（%02s）
     */
    public static function format($i)
    {
        return sprintf("%02s", $i);
    }
}
