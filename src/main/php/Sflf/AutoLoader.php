<?php
// namespace App\Core; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 クラスオートローダー クラス
 *
 * 　【注意】基本的には Composer を利用すべきです
 *
 * 本クラスは以下の2種類のオートロード形式をサポートします。
 *
 * 　a) INCLUDE_PATH を起点とした PSR-0 に準拠するロード
 * 　b) INCLUDE_PATH 配下を対象とした クラス名≒ファイル名 とするファイル検索によるロード
 *
 * 各ロード方法の指定は以下の通りです。
 *
 * 　a) INCLUDE_PATH指定方法 ： /path/to/vendor/a
 * 　　 上記の指定で クラス Foo\Bar\XxxYyyZzz は /path/to/vendor/a/Foo/Bar/XxxYyyZzz.php のロードを試みます
 *
 * 　b) INCLUDE_PATH指定方法 ： /path/to/vendor/b/* （末尾に /* を記述）
 * 　　 上記の指定で クラス XxxYyyZzz は以下の手順でロードを試みます
 *
 * 　　　 1. XxxYyyZzz[.class].php (完全一致での検索)
 * 　　　 2. xxxyyyzzz[.class].php (小文字での検索)
 * 　　　 3. YyyZzz[.class].php    (推定親クラスでの検索)
 * 　　　 4. Zzz[.class].php       (推定親クラスでの検索)
 *
 * 　　 ※同名のクラス名となるファイルが複数存意した場合、ファイル検索順序で先勝ちになります。
 * 　　 ※PSR-0形式の INCLUDE_PATH は本形式の検索対象になりません
 *
 * 【使い方】
 * require_once "/path/to/vendor/Sflf/AutoLoader.php";
 * AutoLoader::$INCLUDE_PATH = [
 *     '/path/to/module/*',
 *     '/path/to/vendor',
 *     '/path/to/vendor/Sflf/*'
 * ];
 *
 * $obj = new ClassName();
 *
 * @package    SFLF
 * @version    v1.0.4
 * @author     github.com/rain-noise
 * @copyright  Copyright (c) 2017 github.com/rain-noise
 * @license    MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 * @deprecated You should use Composer
 */
class AutoLoader
{
    /**
     * クラスファイルを検索するディレクトリリスト
     *
     * @var string[]
     */
    public static $INCLUDE_PATH = [];

    /**
     * インクルードパス内のクラスファイルリスト
     *
     * @var array<string, string>
     */
    private static $CLASS_FILE_PATH = [];

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 指定のクラスをロードする
     *
     * @param string $class クラス名
     * @return void
     */
    public static function load($class)
    {
        if (class_exists($class)) {
            return;
        }

        // ---------------------------------------------------------------------
        // 名前空間を持っている ： PSR-0 に準拠するクラスのロード
        // ---------------------------------------------------------------------
        if (($last_ns_pos = strripos($class, '\\')) !== false) {
            $namespace  = substr($class, 0, $last_ns_pos);
            $class_name = substr($class, $last_ns_pos + 1);
            $base_path  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class_name);

            foreach (self::$INCLUDE_PATH as $includePath) {
                if (self::_endsWith($includePath, '/*')) {
                    continue;
                }
                if (file_exists("{$includePath}/{$base_path}.php")) {
                    require_once "{$includePath}/{$base_path}.php";
                    return;
                }
            }
        }

        // ---------------------------------------------------------------------
        // PSR-0 に準拠しないクラスのロード
        // ---------------------------------------------------------------------
        if (empty(self::$CLASS_FILE_PATH)) {
            self::_init();
        }

        if (isset(self::$CLASS_FILE_PATH[$class])) {
            require_once self::$CLASS_FILE_PATH[$class];
            return;
        }
        // 小文字で検索
        if (isset(self::$CLASS_FILE_PATH[strtolower($class)])) {
            require_once self::$CLASS_FILE_PATH[strtolower($class)];
            // @phpstan-ignore-next-line : Phpstan doesn't consider require_once
            if (class_exists($class)) {
                return;
            }
        }

        // 推定親クラス名で検索
        $split = preg_split("/([A-Z][^A-Z]+)/", $class, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        while (count($split ?: []) > 1) {
            array_shift($split);
            $expect = join('', $split);
            if (isset(self::$CLASS_FILE_PATH[$expect])) {
                require_once self::$CLASS_FILE_PATH[$expect];
                // @phpstan-ignore-next-line : Phpstan doesn't consider require_once
                if (class_exists($class)) {
                    return;
                }
            }
        }

        return;
    }

    /**
     * クラスファイルリストを初期化します
     *
     * @return void
     */
    private static function _init()
    {
        foreach (self::$INCLUDE_PATH as $dir) {
            if (self::_endsWith($dir, '/*')) {
                self::_listup(dirname($dir));
            }
        }
    }

    /**
     * インクルード内のクラスをリストアップします
     *
     * @param string $dir 検索対象ディレクトリ
     * @return void
     */
    private static function _listup($dir)
    {
        // カレントディレクトリ検索
        foreach (glob($dir."/*.php") ?: [] as $path) {
            $class = basename(basename($path, '.php'), '.class');
            if (!isset(self::$CLASS_FILE_PATH[$class])) {
                self::$CLASS_FILE_PATH[$class] = $path;
            }
        }

        // サブディレクトリ検索
        foreach (glob($dir.'/*', GLOB_ONLYDIR) ?: [] as $subDir) {
            if ($subDir !== '.' && $subDir !== '..') {
                self::_listup($subDir);
            }
        }
    }

    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で終わるか検査します。
     *
     * @param string|null $haystack 検査対象文字列
     * @param string      $needle   被検査文字列
     * @return bool true : 終わる／false : 終わらない
     */
    private static function _endsWith($haystack, $needle)
    {
        if ($haystack === null) {
            return false;
        }
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}

spl_autoload_register(['AutoLoader', 'load']);
