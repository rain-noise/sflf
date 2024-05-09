<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

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
 * @version   v1.1.6
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Log
{
    /** @var int エラーレベル定義：致命的なエラー */
    const LEVEL_FATAL = 0;
    /** @var int エラーレベル定義：エラー */
    const LEVEL_ERROR = 1;
    /** @var int エラーレベル定義：ワーニング */
    const LEVEL_WARN  = 2;
    /** @var int エラーレベル定義：インフォメーション */
    const LEVEL_INFO  = 3;
    /** @var int エラーレベル定義：デバッグ */
    const LEVEL_DEBUG = 4;
    /** @var int エラーレベル定義：トレース */
    const LEVEL_TRACE = 5;

    /** @var int ブラウザ画面表示モード定義：ブラウザ画面への表示を行わない */
    const DISPLAY_NONE    = 1;
    /** @var int ブラウザ画面表示モード定義：ログ内容をストックし、 シャットダウン時にウェブレスポンスに出力 */
    const DISPLAY_FINALLY = 2;

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * エラーラベル定義
     *
     * @var array<int, string>
     */
    private static $_LOG_LEVEL_LABEL = [
        self::LEVEL_FATAL => "FATAL",
        self::LEVEL_ERROR => "ERROR",
        self::LEVEL_WARN  => "WARN ",
        self::LEVEL_INFO  => "INFO ",
        self::LEVEL_DEBUG => "DEBUG",
        self::LEVEL_TRACE => "TRACE"
    ];

    /** @var int ロガー設定定義：ログレベル（default: LEVEL_ERROR） */
    private static $_LOG_LEVEL            = self::LEVEL_ERROR;
    /** @var string|null ロガー設定定義：ログファイル */
    private static $_LOG_FILE             = null;
    /** @var string ロガー設定定義：ログファイル拡張子 */
    private static $_LOG_FILE_SUFFIX      = "_Ym";
    /** @var int ロガー設定定義：ログ画面表示設定 */
    private static $_LOG_DISPLAY          = self::DISPLAY_NONE;
    /** @var string|null ロガー設定定義：ログ出力抑止パターン */
    private static $_LOG_SUPPRESS_PATTERN = null;

    /**
     * ブラウザ画面ログ出力ストック用バッファ
     *
     * @var array<array{ 0: int, 1: string}> [[ログレベル, ログメッセージ], ...]
     */
    private static $_OUT_BUFFER = [];

    /**
     * (ブラウザ画面ログ出力時のみ)
     * ファイルサフィックス ⇒ Content-Type のマップ
     * データダウンロード時の Content-Type が 'application/octet-stream', 'application/force-download' の場合に
     * Content-Disposition に指定されている file_name のサフィックスから適切な Content-Type を判断するために使用
     * ※必要に応じて追加/変更して下さい。
     * ※ここに定義の無いサフィックスの Content-Type はそのまま 'application/octet-stream' 又は 'application/force-download' として扱われます。
     *
     * @var array<string, string> [ファイルサフィックス ⇒ Content-Type]
     */
    private static $_FILE_MIME_TYPE = [
        'csv' => 'text/csv',
        'tsv' => 'text/tsv',
        'txt' => 'text/plain',
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'xml' => 'application/xml',
    ];

    /**
     * (ブラウザ画面ログ出力時のみ)
     * 出力対象のコンテンツタイプによってログ出力方法を変更できます。
     * ※ここに定義のないコンテンツタイプの場合はウェブレスポンスへのログ出力は行われません。
     * ※必要に応じて追加/変更して下さい。
     *
     * @var array<string, array{0: string, 1: string|mixed[]|callable(string $body, int $level):string|null}> [content_type => [how_to, args]]
     */
    private static $_HOW_TO_DISPLAY = [
        'text/html'              => ['html'         , null],
        'text/csv'               => ['wrap-escape'  , ['"', ['"' , '""'], '"']],
        'text/tsv'               => ['wrap-escape'  , ['' , ["\t", '    '], '']],
        'text/plain'             => ['raw'          , null],
        'text/css'               => ['block-comment', [['/*'  , '*/' ], ['[COMMENT]', '[/COMMENT]']]],
        'text/javascript'        => ['line-comment' , '// '],
        'application/javascript' => ['line-comment' , '// '],
        'text/xml'               => ['block-comment', [['<!--', '-->'], ['[COMMENT]', '[/COMMENT]']]],
        'application/xml'        => ['block-comment', [['<!--', '-->'], ['[COMMENT]', '[/COMMENT]']]],
        'unknown'                => ['html'         , null],
        //'content/type'         => ['custom'       , function($body, $level) { ... }],
    ];

    /**
     * ロガーを初期化します。
     *
     * @param int         $level            ログレベル
     * @param string|null $file_name        ログ出力ファイル名 (default: null)
     * @param string      $suffix           ログファイルサフィックスのDatePattern (default: _Ym)
     * @param int         $display          画面出力制御 DISPLAY_* (default: DISPLAY_NONE)
     * @param string|null $suppress_pattern ログ出力抑止パターン正規表現 (default: null)
     * @return void
     */
    public static function init($level, $file_name = null, $suffix = "_Ym", $display = self::DISPLAY_NONE, $suppress_pattern = null)
    {
        self::$_LOG_LEVEL            = $level;
        self::$_LOG_FILE             = $file_name;
        self::$_LOG_FILE_SUFFIX      = $suffix;
        self::$_LOG_DISPLAY          = $display;
        self::$_LOG_SUPPRESS_PATTERN = $suppress_pattern;

        if (self::$_LOG_DISPLAY == self::DISPLAY_FINALLY) {
            register_shutdown_function(function () { Log::display(); });
        }
    }

    /**
     * TRACE レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function trace($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_TRACE, $message, $params, $exception);
    }

    /**
     * DEBUG レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function debug($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_DEBUG, $message, $params, $exception);
    }

    /**
     * INFO レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function info($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_INFO, $message, $params, $exception);
    }

    /**
     * WARN レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function warn($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_WARN, $message, $params, $exception);
    }

    /**
     * ERROR レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function error($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_ERROR, $message, $params, $exception);
    }

    /**
     * FATAL レベルログを出力します。
     *
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    public static function fatal($message, $params = null, $exception = null)
    {
        self::_log(self::LEVEL_FATAL, $message, $params, $exception);
    }

    /**
     * メモリ使用量を出力します。
     *
     * @param string $message   ログメッセージ（default: 空文字）
     * @param int    $decimals  メモリ[MB]の小数点桁数（default: 2）
     * @return void
     */
    public static function memory(string $message = '', int $decimals = 2)
    {
        $current = number_format(memory_get_usage() / 1048576, $decimals);
        $peak    = number_format(memory_get_peak_usage() / 1048576, $decimals);
        $message = empty($message) ? "" : "{$message} : " ;
        $message = $message."Memory {$current} MB / Peak Memory {$peak} MB";
        self::_log(self::LEVEL_INFO, $message);
    }

    /**
     * ログを出力します。
     *
     * @param int                            $level     ログレベル
     * @param string|array|object|mixed      $message   ログメッセージ
     * @param string|array|object|mixed|null $params    パラメータ (default: null)
     * @param \Throwable|null                $exception 例外 (default: null)
     * @return void
     */
    private static function _log($level, $message, $params = null, $exception = null)
    {
        if (self::$_LOG_LEVEL < $level) {
            return;
        }
        if (!is_string($message)) {
            $message = is_object($message) && method_exists($message, '__toString') ? $message->__toString() : print_r($message, true) ;
        }
        if (self::$_LOG_SUPPRESS_PATTERN && preg_match(self::$_LOG_SUPPRESS_PATTERN, $message)) {
            return;
        }

        $microtime = explode('.', (string)microtime(true));
        $now       = (int)$microtime[0];
        $ms        = isset($microtime[1]) ? $microtime[1] : '0' ;
        $body      = date("Y-m-d H:i:s", $now).".".str_pad($ms, 4, '0')." ".getmypid()." [".self::$_LOG_LEVEL_LABEL[$level]."] ".$message;

        if ($params) {
            $body .= self::_indent(
                "\n*** PARAM ***"
                ."\n".print_r($params, true),
                1,
                '>> '
            );
        }

        if ($exception) {
            $body .= self::_indent(
                "\n*** STACK TRACE ***"
                ."\n".$exception,
                1,
                '** '
            );
        }

        error_log($body."\n", 3, self::$_LOG_FILE.date(self::$_LOG_FILE_SUFFIX, $now));

        if (self::$_LOG_DISPLAY != self::DISPLAY_NONE) {
            self::$_OUT_BUFFER[] = [$level, $body];
        }
    }

    /**
     * HTML 表示用のログに整形します。
     *
     * @param string $body  ログメッセージ
     * @param int    $level ログレベル
     * @return string HTML書式のログ
     */
    private static function _toHtmlDisplay(string $body, int $level)
    {
        $fc = '#000000';
        $bc = '#ffffff';
        switch ($level) {
            case self::LEVEL_TRACE:
                $fc = '#666666';
                $bc = '#f9f9f9';
                break;
            case self::LEVEL_DEBUG:
                $fc = '#3333cc';
                $bc = '#eeeeff';
                break;
            case self::LEVEL_INFO:
                $fc = '#229922';
                $bc = '#eeffee';
                break;
            case self::LEVEL_WARN:
                $fc = '#ff6e00';
                $bc = '#ffffee';
                break;
            case self::LEVEL_ERROR:
            case self::LEVEL_FATAL:
                $fc = '#ee3333';
                $bc = '#ffeeee';
                break;
        }

        $mark    = substr_count($body, "\n") > 1 ? "☰" : "　" ;
        $message = preg_replace('/\n/s', '<br />', str_replace(' ', '&nbsp;', htmlspecialchars($body)));
        $html    = <<<EOS
<div style="box-sizing: border-box; height:20px; overflow-y:hidden; cursor:pointer; margin:5px; padding:4px 10px 4px 26px; border-left:8px solid {$fc}; color:{$fc}; background-color:{$bc};display: block;font-size:12px; line-height: 1.2em; word-break : break-all;font-family: Consolas, 'Courier New', Courier, Monaco, monospace;text-indent:-19px;text-align: left;"
     ondblclick="javascript: this.style.height=='20px' ? this.style.height='auto' : this.style.height='20px'">
{$mark} {$message}
</div>
EOS;
        return $html;
    }

    /**
     * レスポンスのコンテンツタイプを取得します。
     * コンテンツタイプが application/octet-stream 又は application/force-download の場合は file_name の拡張子から推測します。
     *
     * @return string コンテンツタイプ
     */
    private static function _getResponseContentType()
    {
        $content_type = null;
        $file_suffix  = null;
        $matcher      = [];
        foreach (\headers_list() as $header) {
            $header  = mb_decode_mimeheader($header);

            if (\preg_match('|content-type:\s*(?<type>[^/]+/[^ ;]+).*|i', $header, $matcher)) {
                $content_type = $matcher['type'];
            }

            if (\preg_match('|content-disposition:.*file_name="?.*\.(?<suffix>[^ ";]+)"?|i', $header, $matcher)) {
                $file_suffix = $matcher['suffix'];
            }
        }

        if ($file_suffix !== null && in_array($content_type, ['application/octet-stream', 'application/force-download'])) {
            $content_type = self::$_FILE_MIME_TYPE[$file_suffix] ?? $content_type ;
        }

        return $content_type ?? 'unknown';
    }

    /**
     * 指定の文字列をインデントします。
     * ※対象の文字列が空の場合、インデントしません。
     *
     * @param string $string 文字列
     * @param int    $depth  インデントの深さ (default: 1)
     * @param string $char   インデント用文字列 (default: \t)
     * @return string インデントされた文字列
     */
    private static function _indent($string, $depth = 1, $char = "\t")
    {
        if (empty($string)) {
            return $string;
        }
        $indent = str_repeat($char, $depth);
        return str_replace("\n", "\n{$indent}", $string);
    }

    /**
     * ディスプレイログを画面に表示します。
     *
     * @return void
     */
    public static function display()
    {
        if (!empty(self::$_OUT_BUFFER)) {
            $content_type      = self::_getResponseContentType();
            [$how_to, $option] = self::$_HOW_TO_DISPLAY[$content_type] ?? ['none', null];
            switch ($how_to) {
                case 'html':
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo self::_toHtmlDisplay($body, $level);
                    }
                    break;
                case 'raw':
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo "\n".$body;
                    }
                    break;
                case 'wrap-escape':
                    if (!is_array($option) || count($option) !== 3) {
                        throw new \ValueError("Invalid 'wrap-escape' options given.");
                    }
                    [$open, $escape, $close] = $option;
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo "\n{$open}".str_replace($escape[0], $escape[1], $body)."{$close}";
                    }
                    break;
                case 'block-comment':
                    if (!is_array($option) || count($option) !== 2) {
                        throw new \ValueError("Invalid 'block-comment' options given.");
                    }
                    [$comment, $replacement] = $option;
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo "\n{$comment[0]}\n".str_replace($comment, $replacement, $body)."\n{$comment[1]}";
                    }
                    break;
                case 'line-comment':
                    if (!is_string($option)) {
                        throw new \ValueError("Invalid 'line-comment' options given.");
                    }
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo "\n".self::_indent($body, 1, $option);
                    }
                    break;
                case 'custom':
                    if (!is_callable($option)) {
                        throw new \ValueError("Invalid 'custom' options given.");
                    }
                    foreach (self::$_OUT_BUFFER as [$level, $body]) {
                        echo "\n".$option($body, $level);
                    }
                    break;
            }
            self::$_OUT_BUFFER = [];
        }
    }

    /**
     * 画面表示用のディスプレイログをクリアします。
     *
     * @return void
     */
    public static function clear()
    {
        self::$_OUT_BUFFER = [];
    }

    /**
     * 画面表示用ディスプレイログをクリアし、画面表示モードを DISPLAY_NONE に設定します。
     *
     * @return void
     */
    public static function quiet()
    {
        self::clear();
        self::$_LOG_DISPLAY = self::DISPLAY_NONE;
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
    public static function error_handle($errno, $errstr, $errfile, $errline)
    {
        $level = self::LEVEL_WARN;
        switch($errno) {
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

        if ($level <= self::LEVEL_ERROR) {
            $trace = self::_traceToString(debug_backtrace(), true);
        }

        self::_log(
            $level,
            "$errstr ({$errfile}:{$errline})" . (empty($trace) ? "" : "\n*** STACK TRACE ***"."\n".$trace)
        );
    }

    /**
     * debug_backtrace を文字列形式に変換します。
     *
     * @param array<array<string, mixed>> $trace     debug_backtrace
     * @param bool                        $with_args true : 引数記載有り／false : 引数記載無し (default: false)
     * @return string デバックバックトレース文字列
     */
    private static function _traceToString($trace, $with_args = false)
    {
        $trace = array_reverse($trace);
        array_pop($trace); // Remove self method stack
        array_walk($trace, function (&$value, $key) use ($with_args) {
            $value = "#{$key} "
            .(empty($value['class']) ? "" : $value['class']."@")
            .$value['function']
            .(empty($value['file']) ? "" : " (".$value['file'].":".$value['line'].")")
            .($with_args && !empty($value['args']) ? "\n-- ARGS --\n".print_r($value['args'], true) : "")
            ;
        });

        // @phpstan-ignore-next-line : Phpstan doesn't consider converted array by array_walk()
        return empty($trace) ? "" : join("\n", $trace) ;
    }
}

// エラーハンドラ登録
$old_handler = set_error_handler(null);
set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$old_handler) {
    if (is_callable($old_handler)) {
        $old_handler($errno, $errstr, $errfile, $errline);
    }
    Log::error_handle($errno, $errstr, $errfile, $errline);
    return false;
});

// シャットダウンハンドラ登録
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        Log::error_handle($error['type'], $error['message'], $error['file'], $error['line']);
    }
});
