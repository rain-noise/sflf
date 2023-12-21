<?php
// 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）
//namespace App\Domain;
//
//use App\Domain\Base\RangeDomain;

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■月ドメイン
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Month extends RangeDomain
{
    /**
     * 開始の月を取得します
     *
     * @return int 開始月
     */
    public static function start()
    {
        return  1;
    }

    /**
     * 月の増分を取得します
     *
     * @return int 増分月
     */
    public static function step()
    {
        return  1;
    }

    /**
     * 終了の月を取得します
     *
     * @return int 終了月
     */
    public static function end()
    {
        return 12;
    }

    /**
     * 指定の月をフォーマットします。
     *
     * @param int $i 月
     * @return string ラベル文字列（%02s）
     */
    public static function format($i)
    {
        return sprintf("%02s", $i);
    }
}
