<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■月ドメイン
 *
 * @package   SFLF
 * @version   v1.0.1
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Month extends RangeDomain
{
    public static function start()
    {
        return  1;
    }

    public static function step()
    {
        return  1;
    }

    public static function end()
    {
        return 12;
    }

    public static function format($i)
    {
        return sprintf("%02s", $i);
    }
}
