<?php
// 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）
//namespace App\Domain;
//
//use App\Domain\Base\RangeDomain;

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■日数ドメイン
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Day extends RangeDomain
{
    /**
     * 開始の日付を取得します
     *
     * @return int 開始日付
     */
    public static function start()
    {
        return  1;
    }

    /**
     * 日付の増分を取得します
     *
     * @return int 増分日付
     */
    public static function step()
    {
        return  1;
    }

    /**
     * 終了の日付を取得します
     *
     * @return int 終了日付
     */
    public static function end()
    {
        return 31;
    }

    /**
     * 指定の日付をフォーマットします。
     *
     * @param int $i 日付
     * @return string ラベル文字列（%02s）
     */
    public static function format($i)
    {
        return sprintf("%02s", $i);
    }
}
