<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■性別ドメイン
 *
 * @package   SFLF
 * @version   v1.0.1
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Sex extends Domain
{
    public static $MALE;
    public static $FEMALE;

    public static function init()
    {
        self::$MALE   = new Sex(1, '男性');
        self::$FEMALE = new Sex(2, '女性');
    }
}
Sex::init();
