<?php

/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 クラスオートローダー クラス
 * {クラス名}.php 又は {クラス名}.class.php で検索を行います。
 * ※サブディレクトリを含んだ検索を行います
 * ※同名のクラス名となるファイルが複数存意した場合、先勝ちになります。
 * 
 * 【クラスファイル検索手順】
 * XxxYyyZzz のクラスをロードする場合、以下の手順でクラスファイルを検索しロードを試みます。
 * 
 * 　 1. XxxYyyZzz[.class].php (完全一致での検索)
 * 　 2. xxxyyyzzz[.class].php (小文字での検索)
 * 　 3. YyyZzz[.class].php    (推定親クラスでの検索)
 * 　 4. Zzz[.class].php       (推定親クラスでの検索)
 * 
 * 【使い方】
 * require_once "/path/to/AutoLoader.php";
 * AutoLoader::$INCLUDE_PATH = array(
 *      '/path/to/application/contoller'
 *     ,'/path/to/application/model'
 *     ,'/path/to/application/util'
 * );
 * AutoLoader::$EXCLUDE_PATH = array(
 *      '/path/to/application/util/ThirdParty'
 * );
 * 
 * $obj = new ClassName();
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class AutoLoader
{
	/**
	 * クラスファイルを検索するディレクトリリスト
	 * @var array
	 */
	public static $INCLUDE_PATH = array();

	/**
	 * クラスファイルの検索から除外するディレクトリリスト
	 * @var array
	 */
	public static $EXCLUDE_PATH = array();
	
	/**
	 * インクルードパス内のクラスファイルリスト
	 * @var array
	 */
	private static $CLASS_FILE_PATH = array();
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * 指定のクラスをロードする
	 * 
	 * @param  string $class クラス名
	 * @return bool   true : 成功／false : 失敗
	 */
	public static function load($class) {
		if(class_exists($class)) { return true; }
		if(empty(self::$CLASS_FILE_PATH)) {
			self::_init();
		}
		
		if(isset(self::$CLASS_FILE_PATH[$class])) {
			require_once self::$CLASS_FILE_PATH[$class];
			return true;
		} else {
			// 小文字で検索
			if(isset(self::$CLASS_FILE_PATH[strtolower($class)])) {
				require_once self::$CLASS_FILE_PATH[strtolower($class)];
				if(class_exists($class)) { return true; }
			}
			
			// 推定親クラス名で検索
			$split = preg_split("/([A-Z][^A-Z]+)/", $class, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			while(count($split) > 1) {
				array_shift($split);
				$expect = join('', $split);
				if(isset(self::$CLASS_FILE_PATH[$expect])) {
					require_once self::$CLASS_FILE_PATH[$expect];
					if(class_exists($class)) { return true; }
				}
			}
		}
		
		return false;
	}
	
	/**
	 * クラスファイルリストを初期化します
	 * 
	 * @return void
	 */
	private static function _init() {
		foreach (self::$INCLUDE_PATH as $dir) {
			self::_listup($dir);
		}
	}
	
	/**
	 * インクルード内のクラスをリストアップします
	 * 
	 * @param  string $dir 検索対象ディレクトリ
	 * @return void
	 */
	private static function _listup($dir) {
		if(in_array($dir, self::$EXCLUDE_PATH)) {
			return;
		}
		
		// カレントディレクトリ検索
		foreach(glob($dir."/*.php") as $path) {
			$class = basename(basename($path, '.php'),'.class');
			if(!isset(self::$CLASS_FILE_PATH[$class])) {
				self::$CLASS_FILE_PATH[$class] = $path;
			}
		}
		
		// サブディレクトリ検索
		foreach(glob($dir.'/*' , GLOB_ONLYDIR) as $subDir) {
			if($subDir !== '.' && $subDir !== '..') {
				self::_listup($subDir);
			}
		}
	}
}

spl_autoload_register(array('AutoLoader', 'load'));
