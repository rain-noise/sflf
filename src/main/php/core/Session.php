<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 セッション クラス
 * 
 * 【使い方】
 * require_once "/path/to/Session.php"; // or use AutoLoader
 * 
 * Session::set('LOGIN', $user);
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://opensource.org/licenses/MIT
 */
class Session {
	
	const SESSION_KEY_PREFIX = "SFLF_SESSION_";
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * セッションに値を保存します。
	 * 
	 * @param  string $key   キー名
	 * @param  obj    $value 値
	 * @return void
	 */
	public static function set($key, $value) {
		$_SESSION[self::SESSION_KEY_PREFIX.$key] = serialize($value);
	}
	
	/**
	 * セッションから値を取得します。
	 * 
	 * @param  string $key     キー名
	 * @param  obj    $default デフォルト値
	 * @return mixed 格納した値
	 */
	public static function get($key, $default = null) {
		return isset($_SESSION[self::SESSION_KEY_PREFIX.$key]) ? unserialize($_SESSION[self::SESSION_KEY_PREFIX.$key]) : $default ;
	}
	
	/**
	 * セッションが存在するかチェックします。
	 * 
	 * @param  string $key キー名
	 * @return boolean true : 存在する／false : 存在しない
	 */
	public static function exists($key) {
		return isset($_SESSION[self::SESSION_KEY_PREFIX.$key]) ;
	}
	
	/**
	 * セッション情報を削除します。
	 * 
	 * @param  string $key キー名
	 * @return void
	 */
	public static function remove($key) {
		if(self::exists($key)) {
			unset($_SESSION[self::SESSION_KEY_PREFIX.$key]);
		}
	}
}