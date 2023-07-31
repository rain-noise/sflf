<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■都道府県ドメイン
 *
 * @package   SFLF
 * @version   v1.0.2
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Prefecture extends Domain
{
    /**
     * 都道府県ドメインの一覧を生成します。
     *
     * @return Prefecture[]
     */
    protected static function generate()
    {
        return [
            new Prefecture(1, '北海道'),
            new Prefecture(2, '青森県'),
            new Prefecture(3, '岩手県'),
            new Prefecture(4, '宮城県'),
            new Prefecture(5, '秋田県'),
            new Prefecture(6, '山形県'),
            new Prefecture(7, '福島県'),
            new Prefecture(8, '茨城県'),
            new Prefecture(9, '栃木県'),
            new Prefecture(10, '群馬県'),
            new Prefecture(11, '埼玉県'),
            new Prefecture(12, '千葉県'),
            new Prefecture(13, '東京都'),
            new Prefecture(14, '神奈川県'),
            new Prefecture(15, '新潟県'),
            new Prefecture(16, '富山県'),
            new Prefecture(17, '石川県'),
            new Prefecture(18, '福井県'),
            new Prefecture(19, '山梨県'),
            new Prefecture(20, '長野県'),
            new Prefecture(21, '岐阜県'),
            new Prefecture(22, '静岡県'),
            new Prefecture(23, '愛知県'),
            new Prefecture(24, '三重県'),
            new Prefecture(25, '滋賀県'),
            new Prefecture(26, '京都府'),
            new Prefecture(27, '大阪府'),
            new Prefecture(28, '兵庫県'),
            new Prefecture(29, '奈良県'),
            new Prefecture(30, '和歌山県'),
            new Prefecture(31, '鳥取県'),
            new Prefecture(32, '島根県'),
            new Prefecture(33, '岡山県'),
            new Prefecture(34, '広島県'),
            new Prefecture(35, '山口県'),
            new Prefecture(36, '徳島県'),
            new Prefecture(37, '香川県'),
            new Prefecture(38, '愛媛県'),
            new Prefecture(39, '高知県'),
            new Prefecture(40, '福岡県'),
            new Prefecture(41, '佐賀県'),
            new Prefecture(42, '長崎県'),
            new Prefecture(43, '熊本県'),
            new Prefecture(44, '大分県'),
            new Prefecture(45, '宮崎県'),
            new Prefecture(46, '鹿児島県'),
            new Prefecture(47, '沖縄県')
        ];
    }
}
