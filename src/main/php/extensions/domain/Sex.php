<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■性別ドメイン
 *
 * @package   SFLF
 * @version   v1.0.2
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Sex extends Domain
{
    /** @var Sex 性別：男性 */
    public static $MALE;
    /** @var Sex 性別：女性 */
    public static $FEMALE;

    /**
     * 性別ドメインを初期化します。
     *
     * @return void
     */
    public static function init()
    {
        self::$MALE   = new Sex(1, '男性');
        self::$FEMALE = new Sex(2, '女性');
    }
}
Sex::init();
