<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 Cookie クラス
 * 
 * 【使い方】
 * require_once "/path/to/Cookie.php"; // or use AutoLoader
 * 
 * Cookie::set('key','value','+3 day');
 * $value = Cookie::get('key','default');
 * Cookie::remove('key');
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
 class Cookie
{
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * Cookie の値が存在するかチェックします。
	 * 
	 * @param  string  $name
	 * @return boolean true : 存在する／false : 存在しない
	 */
	public static function exists($name) {
		return isset($_COOKIE[$name]) && !empty($_COOKIE[$name]);
	}

	/**
	 * Cookie の値を取得します
	 * 
	 * @param  string $name    Cookie 名
	 * @param  string $default デフォルト値
	 * @return string Cookie の値
	 */
	public static function get($name, $default = null) {
		return self::exists($name) ? $_COOKIE[$name] : $default ;
	}

	/**
	 * Cookie の値を設定します
	 * 
	 * @param  string  $name   Cookie 名
	 * @param  string  $value  値
	 * @param  string  $expiry 有効期限  - デフォルト '+1 day'
	 * @param  string  $path   パス      - デフォルト '/'
	 * @param  string  $domain ドメイン  - デフォルト ''
	 * @param  string  $secure セキュア  - デフォルト false
	 * @return boolean true : 成功／false : 失敗
	 */
	public static function set($name, $value, $expiry = '+1 day', $path = '/', $domain = '', $secure = false) {
		//$domain = $domain ? $domain : $_SERVER['HTTP_HOST'] ;
		if(setcookie($name, $value, strtotime($expiry), $path, $domain, $secure)) {
			$_COOKIE[$name] = $value;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Cookie を削除します
	 * 
	 * @param  string  $name   Cookie 名
	 * @param  string  $path   パス      - デフォルト '/'
	 * @param  string  $domain ドメイン  - デフォルト ''
	 * @return boolean true : 成功／false : 失敗
	 */
	public static function remove($name, $path = '/', $domain = "") {
		if(setcookie($name, '', time() - 3600, $path, $domain)) {
			unset($_COOKIE[$name]);
			return true;
		}
		
		return false;
	}
}
