<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 トークン クラス
 * 
 * 【使い方】
 * require_once "/path/to/Token.php"; // or use AutoLoader
 * 
 * - UserController@registerConfirm
 * Token::generate('USER_REGISTER');
 * 
 * - smarty_function_token
 * return '<input type"hidden" name="token" value="'.Token::get($key).'" />'
 * 
 * - template of confirm
 * {token key="USER_REGISTER"}
 * 
 * - UserController@registerExecute
 * if(!Token::validate($_REQUEST['token'])) {
 *     //Something to do
 * }
 * 
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.token.php トークン出力用 Smarty タグ
 * 
 * @package   SFLF
 * @version   v1.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Token {
	
	const SESSION_KEY_PREFIX = "SFLF_TOKEN_";
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * トークンを生成しセッションに保存します。
	 * 
	 * @param  string $key キー名 - デフォルト 'global'
	 * @return string トークン文字列
	 */
	public static function generate($key='global') {
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION[self::SESSION_KEY_PREFIX.$key] = $token;
		return $token;
	}
	
	/**
	 * トークンを取得します。
	 * ※セッション上のトークン値は削除されません。
	 * 
	 * @param  string $key キー名 - デフォルト 'global'
	 * @return string トークン文字列
	 */
	public static function get($key='global') {
		return isset($_SESSION[self::SESSION_KEY_PREFIX.$key]) ? $_SESSION[self::SESSION_KEY_PREFIX.$key] : null ;
	}
	
	/**
	 * トークンを検証します。
	 * ※セッション上のトークン値は削除されます。
	 * 
	 * @param  string $token 検証対象トークン文字列
	 * @param  string $key   キー名 - デフォルト 'global'
	 * @return boolean true : OK／false : NG
	 */
	public static function validate($token, $key='global') {
		$origin = self::get($key);
		unset($_SESSION[self::SESSION_KEY_PREFIX.$key]);
		return !empty($token) && $token == $origin ;
	}
}