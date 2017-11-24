<?php
/**
 * 範囲ドメイン
 */
abstract class RangeDomain extends Domain
{
	/**
	 * 開始の数値を指定します
	 */
	abstract public static function start();
	
	/**
	 * 変動値を指定します
	 */
	abstract public static function step();
	
	/**
	 * 終了の数値を指定します
	 */
	abstract public static function end();
	
	/**
	 * ラベルを value の数値でフォーマットして返します。
	 * @return string
	 */
	abstract public static function format($i);
	
	/**
	 * ドメインの一覧を生成します。
	 */
	protected static function generate() {
		$list = array();
		for ($i = static::start() ; $i <= static::end() ; $i += static::step()) {
			$list[] = new static($i, static::format($i));
		}
		return $list;
	}
}
