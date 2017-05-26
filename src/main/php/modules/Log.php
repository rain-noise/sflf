<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 ファイルロガー クラス
 * error_log 関数を利用した低機能なファイルロガーです。
 * 
 * 【使い方】
 * require_once "/path/to/Log.php"; // or use AutoLoader
 * 
 * // 開発環境(例)
 * Log::init(
 *      Log::LEVEL_TRACE
 *     ,"/path/to/application.log"
 *     ,"_Ym"
 *     , Log::DISPLAY_FINALLY
 *     ,'/^Undefined index: /'
 * );
 * 
 * // 本番環境(例)
 * Log::init(
 *      Log::LEVEL_ERROR
 *     ,"/path/to/application.log"
 *     ,"_Ym"
 * );
 * 
 * // バッチ(例)
 * Log::init(
 *      Log::LEVEL_INFO
 *     ,"/path/to/batchName.log"
 *     ,"_Ymd"
 * );
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Log {
	
	// エラーレベル定義
	const LEVEL_FATAL = 0;
	const LEVEL_ERROR = 1;
	const LEVEL_WARN  = 2;
	const LEVEL_INFO  = 3;
	const LEVEL_DEBUG = 4;
	const LEVEL_TRACE = 5;
	
	// ブラウザ画面表示モード定義
	const DISPLAY_NONE        = 1; // ブラウザ画面への表示を行わない
	const DISPLAY_IMMEDIATELY = 2; // ログ出力が呼ばれる度にブラウザ画面に出力
	const DISPLAY_TRIGGER     = 3; // ログ内容をストックし、 Log::display() のコールでブラウザ画面に出力
	const DISPLAY_FINALLY     = 4; // ログ内容をストックし、 シャットダウン時にブラウザ画面に出力
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	// エラーラベル定義
	private static $_LOG_LEVEL_LABEL = array(
		 self::LEVEL_FATAL => "FATAL"
		,self::LEVEL_ERROR => "ERROR"
		,self::LEVEL_WARN  => "WARN "
		,self::LEVEL_INFO  => "INFO "
		,self::LEVEL_DEBUG => "DEBUG"
		,self::LEVEL_TRACE => "TRACE"
	);
	
	// ロガー設定定義
	private static $_LOG_LEVEL            = self::LEVEL_ERROR;
	private static $_LOG_FILE             = null;
	private static $_LOG_FILE_SUFFIX      = "_Ym";
	private static $_LOG_DISPLAY          = self::DISPLAY_NONE;
	private static $_LOG_SUPPRESS_PATTERN = null;
	
	// ブラウザ画面ログ出力ストック用バッファ
	private static $_OUT_BUFFER = "";
	
	/**
	 * ロガーを初期化します。
	 * 
	 * @param int    $level           ログレベル
	 * @param string $fileName        ログ出力ファイル名
	 * @param string $suffix          ログファイルサフィックス（DatePattern）
	 * @param string $display         画面出力制御
	 * @param string $suppressPattern ログ出力抑止パターン（正規表現）
	 * @return void
	 */
	public static function init($level, $fileName = null, $suffix = "_Ym", $display = self::DISPLAY_NONE, $suppressPattern = null) {
		self::$_LOG_LEVEL            = $level;
		self::$_LOG_FILE             = $fileName;
		self::$_LOG_FILE_SUFFIX      = $suffix;
		self::$_LOG_DISPLAY          = $display;
		self::$_LOG_SUPPRESS_PATTERN = $suppressPattern;
		
		if(self::$_LOG_DISPLAY == self::DISPLAY_FINALLY) {
			register_shutdown_function(function(){ Log::display(); });
		}
	}
	
	/**
	 * TRACE レベルログを出力します。
	 * 
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function trace($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_TRACE, $message, $params, $exception);
	}
	
	/**
	 * DEBUG レベルログを出力します。
	 *
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function debug($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_DEBUG, $message, $params, $exception);
	}
	
	/**
	 * INFO レベルログを出力します。
	 * 
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function info($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_INFO, $message, $params, $exception);
	}
	
	/**
	 * WARN レベルログを出力します。
	 * 
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function warn($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_WARN, $message, $params, $exception);
	}
	
	/**
	 * ERROR レベルログを出力します。
	 * 
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function error($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_ERROR, $message, $params, $exception);
	}
	
	/**
	 * FATAL レベルログを出力します。
	 * 
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	public static function fatal($message, $params=null, $exception=null) {
		self::_log(self::LEVEL_FATAL, $message, $params, $exception);
	}
	
	/**
	 * ログを出力します。
	 * 
	 * @param int                    $level     ログレベル
	 * @param string|array|obj|mixed $message   ログメッセージ
	 * @param string|array|obj|mixed $params    パラメータ
	 * @param Throwable              $exception 例外           - デフォルト null
	 * @return void
	 */
	private static function _log($level, $message, $params=null, $exception=null) {
		
		if(self::$_LOG_LEVEL < $level) { return; }
		if(!is_string($message) && !method_exists($message, '__toString')) { $message = var_export($message, true); }
		if(self::$_LOG_SUPPRESS_PATTERN && preg_match(self::$_LOG_SUPPRESS_PATTERN, $message)) { return; }
		
		$now  = strtotime("now");
		$body = date("Y-m-d H:i:s", $now)." ".getmypid()." [".self::$_LOG_LEVEL_LABEL[$level]."] ".$message;
		
		if($params) {
			$body .= self::_indent(
				 "\n*** PARAM ***"
				."\n".var_export($params, true)
				, 1
				,'>> '
			);
		}
		
		if($level >= self::LEVEL_DEBUG && self::$_LOG_LEVEL >= self::LEVEL_TRACE) {
			$body .= self::_indent(
				 "\n*** DEBUG TRACE ***"
				."\n".self::_traceToString(debug_backtrace(), false)
				, 1
				,'-- '
			);
		}
		
		if($exception) {
			$body .= self::_indent(
				 "\n*** STACK TRACE ***"
				."\n".$exception->getMessage(). " (".$exception->getFile().":".$exception->getLine().")"
				."\n".$exception->getTraceAsString()
				, 1
				,'** '
			);
		}
		
		error_log($body."\n", 3, self::$_LOG_FILE.date(self::$_LOG_FILE_SUFFIX, $now));
		
		if(self::$_LOG_DISPLAY != self::DISPLAY_NONE) {
			switch ($level) {
				case self::LEVEL_TRACE:
					$fc = '#666666'; $bc = '#f9f9f9';
					break;
				case self::LEVEL_DEBUG:
					$fc = '#3333cc'; $bc = '#eeeeff';
					break;
				case self::LEVEL_INFO:
					$fc = '#229922'; $bc = '#eeffee';
					break;
				case self::LEVEL_WARN:
					$fc = '#ff6e00'; $bc = '#ffffee';
					break;
				case self::LEVEL_ERROR:
				case self::LEVEL_FATAL:
					$fc = '#ee3333'; $bc = '#ffeeee';
					break;
			}
			
			$mark    = substr_count($body,"\n") > 1 ? "☰" : "　" ;
			$message = preg_replace('/\n/s', '<br />', str_replace(' ', '&nbsp;', htmlspecialchars($body)));
			$html    = <<<EOS
<div style="box-sizing: border-box; height:20px; overflow-y:hidden; cursor:pointer; margin:5px; padding:4px 10px 4px 26px; border-left:8px solid {$fc}; color:{$fc}; background-color:{$bc};display: block;font-size:12px; line-height: 1.2em; word-break : break-all;font-family: Consolas, 'Courier New', Courier, Monaco, monospace;text-indent:-19px;text-align: left;"
     ondblclick="javascript: this.style.height=='20px' ? this.style.height='auto' : this.style.height='20px'">
{$mark} {$message}
</div>
EOS;
			switch (self::$_LOG_DISPLAY) {
				case self::DISPLAY_IMMEDIATELY:
					echo $html;
					break;
				case self::DISPLAY_TRIGGER: // Do not break.
				case self::DISPLAY_FINALLY:
					self::$_OUT_BUFFER .= $html;
					break;
			}
		}
	}
	
	/**
	 * 指定の文字列をインデントします。
	 * ※対象の文字列が空の場合、インデントしません。
	 * 
	 * @param unknown $string
	 */
	private static function _indent($string, $depth = 1, $char = "\t") {
		$indent = str_repeat($char, $depth);
		if(empty($string)){ return $string; }
		return str_replace("\n", "\n{$indent}", $string);
	}
	
	/**
	 * ディスプレイログを画面に表示します。
	 * 
	 * @return void
	 */
	public static function display() {
		if(!empty(self::$_OUT_BUFFER)) {
			echo self::$_OUT_BUFFER;
			self::$_OUT_BUFFER = "";
		}
	}
	
	/**
	 * 画面表示用のディスプレイログをクリアします。
	 * 
	 * @return void
	 */
	public static function clear() {
		self::$_OUT_BUFFER = "";
	}
	
	/**
	 * エラーハンドラー用のエラーハンドル
	 * 
	 * @param int    $errno   エラー番号
	 * @param string $errstr  エラーメッセージ
	 * @param string $errfile ファイル名
	 * @param int    $errline 行番号
	 * @return void
	 */
	public static function error_handle($errno, $errstr, $errfile, $errline) {
		$level = self::LEVEL_WARN;
		switch($errno)
		{
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_PARSE:
				$level = self::LEVEL_FATAL;
				break;
			case E_ERROR:
			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				$level = self::LEVEL_ERROR;
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
				$level = self::LEVEL_WARN;
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_STRICT:
				$level = self::LEVEL_TRACE;
				break;
		}
		
		if($level <= self::LEVEL_ERROR) {
			$trace = self::_traceToString(debug_backtrace(), true);
		}
		
		self::_log(
			 $level
			,"$errstr ({$errfile}:{$errline})" . ( empty($trace) ? "" : "\n*** STACK TRACE ***"."\n".$trace )
		);
	}
	
	/**
	 * debug_backtrace を文字列形式に変換します。
	 * 
	 * @param array $trace debug_backtrace
	 * @param boolean true : 引数記載有り／false : 引数記載無し（デフォルト）
	 * @return string デバックバックトレース文字列
	 */
	private static function _traceToString($trace, $withArgs=false) {
		$trace = array_reverse($trace);
		array_pop($trace); // Remove self method stack
		array_walk($trace, function(&$value, $key) use ($withArgs) {
			$value = "#{$key} "
			.(empty($value['class']) ? "" : $value['class']."@")
			.$value['function']
			.(empty($value['file']) ? "" : " (".$value['file'].":".$value['line'].")")
			.($withArgs && !empty($value['args']) ? "\n-- ARGS --\n".var_export($value['args'], true) : "" )
			;
		});
		
		return empty($trace) ? "" : join("\n", $trace) ;
	}
}

// エラーハンドラ登録
$old_handler = set_error_handler(null);
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$old_handler)
{
	if(is_callable($old_handler)) {
		$old_handler($errno, $errstr, $errfile, $errline);
	}
	Log::error_handle($errno, $errstr, $errfile, $errline);
});

// シャットダウンハンドラ登録
register_shutdown_function(function()
{
	$error = error_get_last();
	if($error) {
		Log::error_handle($error['type'], $error['message'], $error['file'], $error['line']);
	}
});

