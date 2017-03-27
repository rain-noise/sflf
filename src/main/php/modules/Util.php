<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 ユーティリティ クラス
 * 簡便なユーティリティメソッドを集めたクラスです。
 * 本クラスに定義されているメソッドは将来的に特化クラスなどへ移設される可能性があります。
 * 
 * require_once "/path/to/Util.php"; // or use AutoLoader
 * 
 * if(Util::endWith($file, '.pdf')) {
 *     // Something to do
 * }
 * 
 * $pass = Util::randomCode(8);
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Util {
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * 最も左側にある指定文字列より左側(Left Before)の文字をトリムします。
	 *
	 * @param string $str       トリム対象
	 * @param string $delimiter 区切り文字
	 * @param string $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない （デフォルト：true）
	 * @return string トリム文字列
	 */
	public static function lbtrim($str, $delimiter, $remove=true) {
		$start = strpos($str, $delimiter);
		if($start === false) { return $str; }
		return mb_substr($str, $start + ($remove ? mb_strlen($delimiter) : 0));
	}
	
	/**
	 * 最も左側にある指定文字列より右側(Left After)の文字をトリムします。
	 * 
	 * @param string $str       トリム対象
	 * @param string $delimiter 区切り文字
	 * @param string $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない （デフォルト：true）
	 * @return string トリム文字列
	 */
	public static function latrim($str, $delimiter, $remove=true) {
		$end = strpos($str, $delimiter);
		if($end === false) { return $str; }
		return mb_substr($str, 0, $end + ($remove ? 0 : mb_strlen($delimiter)));
	}
	
	/**
	 * 最も右側にある指定文字列より左側(Right Before)の文字をトリムします。
	 * 
	 * @param string $str       トリム対象
	 * @param string $delimiter 区切り文字
	 * @param string $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない （デフォルト：true）
	 * @return string トリム文字列
	 */
	public static function rbtrim($str, $delimiter, $remove=true) {
		$start = strrpos($str, $delimiter);
		if($start === false) { return $str; }
		return mb_substr($str, $start + ($remove ? mb_strlen($delimiter) : 0));
	}
	
	/**
	 * 最も右側にある指定文字列より右側(Right After)の文字をトリムします。
	 * 
	 * @param string $str       トリム対象
	 * @param string $delimiter 区切り文字
	 * @param string $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない （デフォルト：true）
	 * @return string トリム文字列
	 */
	public static function ratrim($str, $delimiter, $remove=true) {
		$end = strrpos($str, $delimiter);
		if($end === false) { return $str; }
		return mb_substr($str, 0, $end + ($remove ? 0 : mb_strlen($delimiter)));
	}
	
	/**
	 * 左端の指定文字列の繰り返しをトリムします。
	 * 
	 * @param string $str    トリム対象
	 * @param string $prefix トリム文字列
	 * @return string トリム文字列
	 */
	public static function ltrim($str, $prefix=' ') {
		return preg_replace("/^(".preg_quote($prefix).")*/u", '', $str);
	}
	
	/**
	 * 右端の指定文字列の繰り返しをトリムします。
	 * 
	 * @param string $str    トリム対象
	 * @param string $suffix トリム文字列
	 * @return string トリム文字列
	 */
	public static function rtrim($str, $suffix=' ') {
		return preg_replace("/(".preg_quote($suffix).")*$/u", '', $str);
	}
	
	/**
	 * ランダムなパスワードを生成します。
	 * 
	 * @param  number $length パスワードの長さ
	 * @param  string $chars  パスワードに使用する文字
	 * @return string ランダムな文字列
	 */
	public static function randomCode($length = 12, $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") {
		$res = "";
		for ($i=0; $i < $length; $i++) {
			$res .= $chars{mt_rand(0, strlen($chars) - 1)};
		}
		return $res;
	}

	/**
	 * 文字列をハッシュ化します。
	 * 
	 * @param  string $text       パスワード
	 * @param  string $salt       ソルト値
	 * @param  string $pepper     ペッパー値
	 * @param  string $stretching ストレッチング回数（デフォルト：1000）
	 * @param  string $algorithm  ハッシュアルゴリズム（デフォルト：SHA256）
	 * @return string ハッシュ文字列
	 */
	public static function hash($text, $salt='', $pepper='', $stretching=1000, $algorithm='SHA256') {
		for($i = 0 ; $i < $stretching ; $i++) {
			$text = hash($algorithm, $salt.md5($text).$pepper);
		}
		return $text;
	}
	
	/**
	 * ランダムなをハッシュ値を生成します。
	 * 
	 * @param  string $algorithm  ハッシュアルゴリズム（デフォルト：SHA256）
	 * @return string ハッシュ文字列
	 */
	public static function randomHash($algorithm='SHA256') {
		return self::hash(date('Y-m-d H:i:s'), self::randomCode(8), self::randomCode(8), 10, $algorithm);
	}
	
	/**
	 * 秘密鍵暗号で暗号化します。
	 * 
	 * @param  string $plain     平文
	 * @param  string $secretKey 秘密鍵
	 * @param  string $cipher    暗号器 デフォルト(MCRYPT_RIJNDAEL_256)
	 * @param  string $mode      暗号化モード デフォルト(MCRYPT_MODE_CBC)
	 * @return string 暗号文
	 */
	public static function encript($plain, $secretKey, $cipher=MCRYPT_RIJNDAEL_256, $mode=MCRYPT_MODE_CBC) {
		$iv        = mcrypt_create_iv(mcrypt_get_iv_size($cipher, $mode), MCRYPT_RAND);
		$key       = substr(hash('sha256', $secretKey), 0, mcrypt_get_key_size($cipher, $mode));
		$encrypted = mcrypt_encrypt($cipher, $key, $plain, $mode, $iv);
		return $iv.$encrypted;
	}
	
	/**
	 * 秘密鍵暗号で複合化します。
	 * 
	 * @param  string encrypted  暗号文
	 * @param  string $secretKey 秘密鍵
	 * @param  string $cipher    暗号器 デフォルト(MCRYPT_RIJNDAEL_256)
	 * @param  string $mode      暗号化モード デフォルト(MCRYPT_MODE_CBC)
	 * @return string 復号文
	 */
	public static function decript($encrypted, $secretKey, $cipher=MCRYPT_RIJNDAEL_256, $mode=MCRYPT_MODE_CBC) {
		$key       = substr(hash('sha256', $secretKey), 0, mcrypt_get_key_size($cipher, $mode));
		$iv_size   = mcrypt_get_iv_size($cipher, $mode);
		$iv        = substr($encrypted, 0 , $iv_size);
		$encrypted = substr($encrypted, $iv_size , mb_strlen($encrypted));
		$decrypted = mcrypt_decrypt($cipher, $key, $encrypted , $mode, $iv);
		return rtrim($decrypted, "\0");
	}
	
	/**
	 * バイナリデータをURLに利用可能な文字列に変換します。
	 * 
	 * @param  byte $byte バイナリデータ
	 * @return string URL利用可能文字列
	 */
	public static function pack($byte) {
		return strtr(base64_encode($byte), '+/=', '._-');
	}
	
	/**
	 * URLに利用可能な文字列をバイナリデータに変換します。
	 * 
	 * @param  string  $packed 文字列
	 * @return byte バイナリデータ
	 */
	public static function unpack($packed) {
		return base64_decode(strtr($packed, '._-', '+/='));
	}
	
	/**
	 * 対象のディレクトリを サブディレクトリを含め 削除します。
	 * 
	 * @param  string $dir 削除対象ディレクトリパス
	 * @return void
	 */
	public static function removeDir($dir) {
		if ($handle = opendir("$dir")) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					if (is_dir("$dir/$item")) {
						self::removeDir("$dir/$item");
					} else {
						unlink("$dir/$item");
					}
				}
			}
			closedir($handle);
			rmdir($dir);
		}
	}
	
	/**
	 * 配列又はオブジェクトから値を取得します。
	 * 
	 * @param  array|obj $obj     配列 or オブジェクト
	 * @param  mixed     $key     キー名
	 * @param  mixed     $default デフォルト値
	 * @return mixed 値
	 */
	public static function get($obj, $key, $default = null) {
		if($obj == null) { return $default; }
		if(is_array($obj)) {
			if(!isset($obj[$key])) { return $default; }
			return self::nvl($obj[$key], $default);
		}
		if(!($obj instanceof stdClass)) {
			$clazz = get_class($obj);
			if(!property_exists($clazz, $key)) { return $default; }
		}
		return self::nvl($obj->$key, $default);
	}
	
	/**
	 * 対象の値が empty の場合にデフォルト値を返します。
	 * ※ nvl の empty版
	 * 
	 * @param  obj $value   値
	 * @param  obj $default デフォルト値
	 * @return obj 値
	 */
	public static function evl($value, $default) {
		return empty($value) ? $default : $value ;
	}
	
	/**
	 * 対象の値が null の場合にデフォルト値を返します。
	 * 
	 * @param  obj $value   値
	 * @param  obj $default デフォルト値
	 * @return obj 値
	 */
	public static function nvl($value, $default) {
		return $value === null ? $default : $value ;
	}
	
	/**
	 * 指定の文字列 [$haystack] が指定の文字列 [$needle] で始まるか検査します。
	 * 
	 * @param  string  $haystack 検査対象文字列
	 * @param  string  $needle   被検査文字列
	 * @return boolean true : 始まる／false : 始まらない
	 */
	public static function startsWith($haystack, $needle) {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	
	/**
	 * 指定の文字列 [$haystack] が指定の文字列 [$needle] で終わるか検査します。
	 * 
	 * @param  string  $haystack 検査対象文字列
	 * @param  string  $needle   被検査文字列
	 * @return boolean true : 終わる／false : 終わらない
	 */
	public static function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	
	/**
	 * 対象の ZIP ファイルを展開します。
	 * 
	 * @param  string $zipPath ZIPファイルパス
	 * @param  string $destDir 展開先ディレクトリパス
	 * @return void
	 */
	public static function unzip($zipPath, $destDir) {
		$zip = new ZipArchive();
		$res = $zip->open($zipPath);
		if ($res === true) {
			$zip->extractTo($destDir);
		    $zip->close();
		}
	}
	
	/**
	 * 対象のパスを ZIP 圧縮します。
	 * 
	 * @param  string   $sourcePath        圧縮対象ファイル or ディレクトリ
	 * @param  string   $outZipPath        圧縮後のZIPファイルパス
	 * @param  boolean  $includeTargetDir  指定ディレクトリをZIPアーカイブに含めるか否か（デフォルト：true[=含める]）
	 * @param  function $filter            格納データ取捨選択用フィルタ
	 *                                     ⇒ $path を引数に取り、 true を返すとそのパスを含み, false を返すとそのパスを除外する。
	 *                                     　 （デフォルト：null = function($path) { return true; }; = 全データ格納）
	 * @param  number   $outDirePermission ZIP格納ディレクトリ自動生成時のパーミッション（デフォルト：0775）
	 * @return void
	 */
	public static function zip($sourcePath, $outZipPath, $includeTargetDir=true, $filter=null, $outDirPermission=0775)
	{
		if(empty($filter)) {
			$filter = function($path) { return true; };
		}
		
		$pathInfo = pathInfo($sourcePath);
		$parentPath = $pathInfo['dirname'];
		$dirName = $pathInfo['basename'];
		
		$destDir = dirname($outZipPath);
		if(!file_exists($destDir)) {
			mkdir($destDir, $outDirPermission, true);
		}
		
		$z = new ZipArchive();
		$z->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if($includeTargetDir) {
			$z->addEmptyDir($dirName);
		}
		self::folderToZip($sourcePath, $z, strlen($includeTargetDir ? "$parentPath/" : "$parentPath/$dirName/"), $filter);
		$z->close();
	}
	
	/**
	 * ディレクトリを再帰的にZIP圧縮します。
	 * 
	 * @param  string   $folder
	 * @param  string   $zipFile
	 * @param  int      $exclusiveLength
	 * @param  function $filter
	 * @return void
	 */
	private static function folderToZip($folder, &$zipFile, $exclusiveLength, $filter) {
		$handle = opendir($folder);
		while (false !== $f = readdir($handle)) {
			if ($f != '.' && $f != '..') {
				$filePath = "$folder/$f";
				if(!$filter($filePath)) { continue; }
				
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath)) {
					$zipFile->addFile($filePath, $localPath);
				} elseif (is_dir($filePath)) {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					self::folderToZip($filePath, $zipFile, $exclusiveLength, $filter);
				}
			}
		}
		closedir($handle);
	}
	
	/**
	 * 機種依存文字が含まれるかチェックします。
	 * 
	 * @param  string $text   検査対象文字列
	 * @param  string $encode 機種依存チェックを行う文字コード
	 * @return array 機種依存文字の配列
	 */
	public static function checkDependenceChar($text, $encode = 'sjis-win') {
		$org  = $text;
		$conv = mb_convert_encoding(mb_convert_encoding($text,$encode,'UTF-8'),'UTF-8',$encode);
		if(strlen($org) != strlen($conv)) {
			$diff = array_diff(self::stringToArray($org), self::stringToArray($conv));
			return $diff;
		}
		
		return array();
	}
	
	/**
	 * 文字列を文字の配列に変換します。
	 * 
	 * @param  string $string 文字列
	 * @return array 文字の配列
	 */
	public static function stringToArray ($string) {
		return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	/**
	 * スネークケース(snake_case)文字列をキャメルケース(CamelCase)文字列に変換します。
	 * 
	 * @param  string $str スネークケース文字列
	 * @return string キャメルケース文字列
	 */
	public static function camelize($str) {
		return str_replace('_', '', ucwords($str, '_'));
	}
	
	/**
	 * キャメルケース(CamelCase) 文字列をスネークケース(snake_case)文字列に変換します。
	 * 
	 * @param  string $str キャメルケース文字列
	 * @return string スネークケース文字列
	 */
	public static function snakize($str) {
		return strtolower(preg_replace('/[a-z]+(?=[A-Z])|[A-Z]+(?=[A-Z][a-z])/', '\0_', $str));
	}
	
	/**
	 * 指定文字の先頭を大文字にします。
	 * 
	 * @param  string $str 文字列
	 * @return string 文字列
	 */
	public static function capitalize($str) {
		return ucfirst($str);
	}
	
	/**
	 * 指定文字の先頭を小文字にします。
	 * 
	 * @param  string $str 文字列
	 * @return string 文字列
	 */
	public static function uncapitalize($str) {
		return lcfirst($str);
	}

	/**
	 * ページをリダイレクトします。
	 * ※本メソッドは exit を call します。
	 * 
	 * @param  string $url リダイレクトURL
	 * @return void
	 * @todo   パラメータ構築などの機能を追加
	 */
	public static function redirect($url) {
		ob_clean();
		header("HTTP/1.1 302 Found");
		header("Location: {$url}");
		exit();
	}
	
	/**
	 * データを JSON形式 で書き出します。
	 * ※本メソッドは exit を call します。
	 * 
	 * @param obj $data オブジェクト
	 */
	public static function json($data) {
		ob_clean();
		header("HTTP/1.1 200 OK");
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($data);
		exit();
	}
	
	/**
	 * データを JSONP形式 で書き出します。
	 * ※本メソッドは exit を call します。
	 * 
	 * @param obj    $data     オブジェクト
	 * @param string $callback コールバック関数
	 */
	public static function jsonp($data, $callback) {
		ob_clean();
		header("HTTP/1.1 200 OK");
		header('Content-Type: application/javascript; charset=UTF-8');
		echo "{$callback}(".json_encode($data).")";
		exit();
	}
	
	/**
	 * 指定の配列が連想配列かチェックします。
	 * 
	 * @param  array $array 配列
	 * @return boolean true : 連想配列／false : 配列
	 */
	public static function isMap(array $array) {
		return array_values($array) !== $array;
	}
	
	/**
	 * データを CSV形式 で書き出します。
	 * ※本メソッドは exit を call します。
	 * 
	 * 【コンバーター定義】
	 * $converter = function($line, $col, $val) {
	 *     // return converted value.
	 *     //  - date, number format
	 *     //  - code to label convert
	 *     //  - using not exists col name to new col like 'name' return "{$line->last_name} {$line->first_name}"
	 * }
	 * 
	 * 【使い方】
	 * // ケース1 ： $rs 内の UserDetailDto クラスの全フィールドを出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *  );
	 * 
	 * // ケース2 ： $rs 内の UserDetailDto クラスの全フィールドを出力／日付のフォーマットを指定
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($val instanceof DateTime) { return $val->format('Y年m月d日 H:i'); }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *  );
	 * 
	 * // ケース3 ： 指定のフィールドを任意の列順で出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name')
	 *  );
	 *  
	 * // ケース4 ： 存在しない項目を固定値で追加
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($col == 'fixed_col') { return 1; }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name','fixed_col')
	 *  );
	 *  
	 * // ケース5 ： 複数項目を結合して出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($col == 'name') { return "{$line->last_name} {$line->first_name}"; }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,array('user_id','mail_address','name')
	 *  );
	 *  
	 * // ケース6 ： ヘッダ行を出力しない
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *    ,false
	 *  );
	 * 
	 * // ケース7 ： ヘッダラベル指定（配列指定）
	 * // ※配列の範囲外の項目はシステムラベルで出力されます
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name')
	 *    ,true
	 *    ,array('会員ID','メールアドレス','姓','名')
	 *  );
	 *  
	 * // ケース8 ： ヘッダラベル指定（連想配列指定）
	 * // ※連想配列に定義の無い項目はシステムラベルで出力されます
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *    ,true
	 *    ,array(
	 *         'user_id'      => '会員ID'
	 *        ,'mail_address' => 'メールアドレス'
	 *        ,'last_name'    => '姓'
	 *        ,'first_name'   => '名'
	 *    )
	 *  );
	 *  
	 * @param string       $fileName  出力ファイル名
	 * @param function     $converter コンバータ
	 * @param array        $rs        結果セット
	 * @param array|string $cols      出力対象列名リスト or DTOクラス名
	 * @param boolean      $hasHeader true : ヘッダ行を出力する／false : ヘッダ行を出力しない - デフォルト true
	 * @param array        $colLabels ヘッダ行のラベル指定(配列又は連想配列)                  - デフォルト array()
	 */
	public static function csv($fileName, $converter, array $rs, $cols, $hasHeader = true, $colLabels = array()) {
		if(is_string($cols)) {
			$reflect = new ReflectionClass($cols);
			$props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			$cols    = array();
			foreach ($props as $prop) {
				$cols[] = $prop->getName();
			}
		}
		
		// 出力
		ob_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Type: application/force-download");
		header('Content-Disposition: attachment; filename=' . mb_convert_encoding($fileName, "SJIS-win", "UTF-8"));
		header("Content-Transfer-Encoding: binary");
		
		$last = count($rs) - 1;
		
		if($hasHeader) {
			$line = '';
			$isMap = self::isMap($colLabels);
			foreach ($cols AS $i => $col) {
				$val = $isMap ? self::get($colLabels, $col, $col ) : self::get($colLabels, $i, $col) ;
				$line .= '"'.str_replace('"','""', $val).'",';
			}
			$line  = substr($line, 0, -1);
			if(0 <= $last) {
				$line .= "\n";
			}
			echo mb_convert_encoding($line, "SJIS-win", "UTF-8");
		}
		
		foreach ($rs AS $i => $row) {
			$line = '';
			foreach ($cols AS $col) {
				$val = self::get($row, $col) ;
				if($converter) {
					$val = $converter($row, $col, $val);
				}
				$line .= '"'.str_replace('"','""', $val).'",';
			}
			$line  = substr($line, 0, -1);
			if($i != $last) {
				$line .= "\n";
			}
			echo mb_convert_encoding($line, "SJIS-win", "UTF-8");
		}
		exit();
	}
	
	/**
	 * ヒアドキュメントへの文字列埋め込み用の匿名関数を返します。
	 * 
	 * 【使い方】
	 * $_ = Util::heredocImplanter();
	 * $str = <<<EOS
	 *     text text text {$_(Class::CONST)}
	 *     {$_(CONSTANT)} text
	 * EOS;
	 * 
	 * @return function
	 */
	public static function heredocImplanter() {
		return function($s){return $s;};
	}
	
	/**
	 * 指定日時点での年齢を計算します。
	 * 
	 * @param DateTime $birthday 生年月日
	 * @param DateTime $at       起点日
	 * @return int 起点日における年齢
	 */
	public static function ageAt(DateTime $birthday, DateTime $at = null) {
		if(empty($birthday)) { return null; }
		$at = self::nvl($at, new DateTime());
		return floor(($at->format('Ymd') - $birthday->format('Ymd')) / 10000);
	}
	
	/**
	 * int 型に変換します
	 * 
	 * @param  $var  変換対象
	 * @param  $base 基数
	 * @return int 
	 */
	public static function intval($var, $base = null) {
		return $var == null || $var == '' ? null : intval($var, $base);
	}
	
	/**
	 * float 型に変換します
	 * 
	 * @param  $var  変換対象
	 * @return float
	 */
	public static function floatval($var) {
		return $var == null || $var == '' ? null : floatval($var);
	}
	
	/**
	 * double 型に変換します
	 * 
	 * @param  $var  変換対象
	 * @return double
	 */
	public static function doubleval($var) {
		return $var == null || $var == '' ? null : doubleval($var);
	}
	
	/**
	 * file_get_contents で指定URLのページデータを取得します。
	 * 
	 * @param string $url URL
	 * @return mixed 受信データ
	 */
	public static function urlGetContents($url) {
		return file_get_contents($url, false, stream_context_create(array(
			 'http' => array('ignore_errors' => true)
			,'ssl'=>array(
				 'verify_peer' => false
				,'verify_peer_name' => false
			),
		)));
	}
}