<?php
// 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）
//namespace App\Domain\Base;
//
//use Sflf\Domain;

/**
 * Single File Low Functionality Class Tools - Extensions
 *
 * ■範囲ドメイン基底クラス
 *
 * 本クラスは数値範囲系のドメイン定義を簡潔に記述するための基底クラスとなります。
 *
 * 【使い方】
 * require_once "/path/to/vendor/Sflf/RangeDomain.php";
 * class Hour extends RangeDomain
 * {
 *     public static function start()    { return  0; }
 * 	   public static function step()     { return  1; }
 * 	   public static function end()      { return 23; }
 * 	   public static function format($i) { return sprintf("%02s",$i); }
 * }
 *
 * class Minute extends RangeDomain
 * {
 *     public static function start()    { return  0; }
 *     public static function step()     { return  5; }
 *     public static function end()      { return 55; }
 *     public static function format($i) { return sprintf("%02s",$i); }
 * }
 *
 *
 * @package   SFLF
 * @version   v1.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
abstract class RangeDomain extends Domain
{
    /**
     * ドメイン生成
     *
     * @param mixed  $value 値
     * @param string $label ラベル
     * @return static
     */
    final protected function __construct($value, $label)
    {
        parent::__construct($value, $label);
    }

    /**
     * 開始の数値を指定します
     *
     * @return int 開始番号
     */
    abstract public static function start();

    /**
     * 変動値を指定します
     *
     * @return int 変動幅
     */
    abstract public static function step();

    /**
     * 終了の数値を指定します
     *
     * @return int 終了番号
     */
    abstract public static function end();

    /**
     * ラベルを value の数値でフォーマットして返します。
     *
     * @param int $i 数字
     * @return string ラベル文字列
     */
    abstract public static function format($i);

    /**
     * ドメインの一覧を生成します。
     *
     * @return static[]
     */
    protected static function generate()
    {
        $list = [];
        for ($i = static::start() ; $i <= static::end() ; $i += static::step()) {
            $list[] = new static($i, static::format($i));
        }
        return $list;
    }
}
