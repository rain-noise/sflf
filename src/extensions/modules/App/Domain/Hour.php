<?php
// 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）
//namespace App\Domain;
//
//use App\Domain\Base\RangeDomain;

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■時刻ドメイン
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Hour extends RangeDomain
{
    /**
     * 開始の時刻を取得します
     *
     * @return int 開始時刻
     */
    public static function start()
    {
        return  0;
    }

    /**
     * 時刻の増分を取得します
     *
     * @return int 増分時刻
     */
    public static function step()
    {
        return  1;
    }

    /**
     * 終了の時刻を取得します
     *
     * @return int 終了時刻
     */
    public static function end()
    {
        return 23;
    }

    /**
     * 指定の時刻をフォーマットします。
     *
     * @param int $i 時刻
     * @return string ラベル文字列（%02s）
     */
    public static function format($i)
    {
        return sprintf("%02s", $i);
    }
}
