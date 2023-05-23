<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■ページサイズドメイン
 *
 * ページサイズの定義はシステムに合わせて適宜変更して下さい。
 *
 * @package   SFLF
 * @version   v1.0.1
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class PageSize extends Domain
{
    protected static function generate()
    {
        return [
            new PageSize(10, '10件')
            , new PageSize(25, '25件')
            , new PageSize(50, '50件')
            , new PageSize(100, '100件')
        ];
    }
}
