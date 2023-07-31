<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 ユーティリティ クラス
 * 簡便なユーティリティメソッドを集めたクラスです。
 * 本クラスに定義されているメソッドは将来的に特化クラスなどへ移設される可能性があります。
 *
 * require_once "/path/to/Util.php"; // or use AutoLoader
 *
 * if(Util::endsWith($file, '.pdf')) {
 *     // Something to do
 * }
 *
 * $pass = Util::randomCode(8);
 *
 * @package   SFLF
 * @version   v1.2.6
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Util
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 受入れ可能な日付／日時フォーマットのリスト
     * 上から順に解析が試みられ、一番最初に解析に成功したフォーマットで日付の解析が実行されます。
     * 必要に応じて受入れ可能フォーマットや優先度を編集して下さい。
     *
     * @var string[]
     */
    const ACCEPTABLE_DATETIME_FORMAT = [
        'Y年m月d日 H時i分s秒',
        'Y年m月d日 H:i:s',
        'Y-m-d H:i:s',
        'Y/m/d H:i:s',
        'YmdHis',
        'Y年m月d日 H時i分',
        'Y年m月d日 H:i',
        'Y-m-d H:i',
        'Y/m/d H:i',
        'YmdHi',
        'Y年m月d日',
        'Y-m-d',
        'Y/m/d',
        'Ymd'
    ];

    /**
     * DateTime オブジェクトを解析します。
     * ※本メソッドは analyzeDateTime() から日付フォーマット情報を除外して日付のみを返す簡易メソッドです。
     *
     * @param string|DateTime|null $value              日時文字列
     * @param string               ...$primary_formats 優先フォーマット (default: [])
     * @return DateTime|null
     */
    public static function createDateTime($value, ...$primary_formats)
    {
        list($date, ) = self::analyzeDateTime($value, ...$primary_formats);
        return $date;
    }

    /**
     * DateTime オブジェクトを解析します。
     * ※本メソッドは解析に成功した日付フォーマットも返します
     *
     * @param string|DateTime|null $value              日時文字列
     * @param string               ...$primary_formats 優先フォーマット (default: [])
     * @return array{0: DateTime|null, 1: string|null} [DateTime or null, apply_format or null]
     */
    public static function analyzeDateTime($value, ...$primary_formats)
    {
        if ($value === null || $value === '') {
            return [null, null];
        }
        if ($value instanceof DateTime) {
            return [$value, null];
        }

        $formats = static::ACCEPTABLE_DATETIME_FORMAT ;
        if (!empty($primary_formats)) {
            array_unshift($formats, ...$primary_formats);
        }

        $date         = null;
        $apply_format = null;
        foreach ($formats as $format) {
            $date = self::_tryToParseDateTime($value, $format);
            if (!empty($date)) {
                $apply_format = $format;
                break;
            }
        }

        return [$date, $apply_format];
    }

    /**
     * DateTime オブジェクトを生成を試みます。
     *
     * @param string $value  日付文字列
     * @param string $format 日付フォーマット
     * @return DateTime|null
     */
    private static function _tryToParseDateTime($value, $format)
    {
        $date = DateTime::createFromFormat("!{$format}", $value);
        $le   = DateTime::getLastErrors();
        return $date === false || !empty($le['errors']) || !empty($le['warnings']) ? null : $date->setTimezone(new DateTimeZone(date_default_timezone_get())) ;
    }

    /**
     * 三項演算のメソッド版
     *
     * @param bool  $expr     判別式
     * @param mixed $if_true  真の場合の値
     * @param mixed $if_false 偽の場合の値
     * @return mixed 三項演算の結果
     */
    public static function when($expr, $if_true, $if_false)
    {
        return $expr ? $if_true : $if_false ;
    }

    /**
     * 空でない最初の要素を返します。
     *
     * @param mixed|null ...$items 要素
     * @return mixed 空でない最初の要素
     */
    public static function coalesce(...$items)
    {
        foreach ($items as $item) {
            if (!empty($item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 最も左側にある指定文字列より左側(Left Before)の文字をトリムします。
     *
     * @param string $str       トリム対象
     * @param string $delimiter 区切り文字
     * @param bool   $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない (default: true)
     * @return string トリムされた文字列
     */
    public static function lbtrim($str, $delimiter, $remove = true)
    {
        $start = strpos($str, $delimiter);
        if ($start === false) {
            return $str;
        }
        return mb_substr($str, $start + ($remove ? mb_strlen($delimiter) : 0));
    }

    /**
     * 最も左側にある指定文字列より右側(Left After)の文字をトリムします。
     *
     * @param string $str       トリム対象
     * @param string $delimiter 区切り文字
     * @param bool   $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない (default: true)
     * @return string トリムされた文字列
     */
    public static function latrim($str, $delimiter, $remove = true)
    {
        $end = strpos($str, $delimiter);
        if ($end === false) {
            return $str;
        }
        return mb_substr($str, 0, $end + ($remove ? 0 : mb_strlen($delimiter)));
    }

    /**
     * 最も右側にある指定文字列より左側(Right Before)の文字をトリムします。
     *
     * @param string $str       トリム対象
     * @param string $delimiter 区切り文字
     * @param bool   $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない (default: true)
     * @return string トリムされた文字列
     */
    public static function rbtrim($str, $delimiter, $remove = true)
    {
        $start = strrpos($str, $delimiter);
        if ($start === false) {
            return $str;
        }
        return mb_substr($str, $start + ($remove ? mb_strlen($delimiter) : 0));
    }

    /**
     * 最も右側にある指定文字列より右側(Right After)の文字をトリムします。
     *
     * @param string $str       トリム対象
     * @param string $delimiter 区切り文字
     * @param bool   $remove    true : 区切り文字も削除する, false : 区切り文字は削除しない (default: true)
     * @return string トリムされた文字列
     */
    public static function ratrim($str, $delimiter, $remove = true)
    {
        $end = strrpos($str, $delimiter);
        if ($end === false) {
            return $str;
        }
        return mb_substr($str, 0, $end + ($remove ? 0 : mb_strlen($delimiter)));
    }

    /**
     * 左端の指定文字列の繰り返しをトリムします。
     *
     * @param string $str    トリム対象
     * @param string $prefix トリム文字列 (default: ' ')
     * @return string トリムされた文字列
     */
    public static function ltrim($str, $prefix = ' ')
    {
        $str = preg_replace("/^(".preg_quote($prefix).")*/u", '', $str);
        assert(is_string($str));
        return $str;
    }

    /**
     * 右端の指定文字列の繰り返しをトリムします。
     *
     * @param string $str    トリム対象
     * @param string $suffix トリム文字列 (default: ' ')
     * @return string トリムされた文字列
     */
    public static function rtrim($str, $suffix = ' ')
    {
        $str = preg_replace("/(".preg_quote($suffix).")*$/u", '', $str);
        assert(is_string($str));
        return $str;
    }

    /**
     * ランダムコードを生成します。
     *
     * @param int    $length ランダムコードの長さ (default: 12)
     * @param string $chars  ランダムコードに使用する文字 (default: 1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890)
     * @return string ランダムな文字列
     */
    public static function randomCode($length = 12, $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890")
    {
        $res = "";
        for ($i = 0; $i < $length; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $res;
    }

    /**
     * 文字列をハッシュ化します。
     *
     * @param string $text       パスワード
     * @param string $salt       ソルト値 (default: '')
     * @param string $pepper     ペッパー値 (default: '')
     * @param int    $stretching ストレッチング回数 (default: 1000)
     * @param string $algorithm  ハッシュアルゴリズム (default: SHA256)
     * @return string ハッシュ文字列
     * @throws ValueError when invalid algorithm value given.
     */
    public static function hash($text, $salt = '', $pepper = '', $stretching = 1000, $algorithm = 'SHA256')
    {
        for ($i = 0 ; $i < $stretching ; $i++) {
            $text = \hash($algorithm, $salt.md5($text).$pepper);
            if ($text === false) {
                throw new ValueError("Invalid algorithm value given.");
            }
        }
        return $text;
    }

    /**
     * ランダムなをハッシュ値を生成します。
     *
     * @param string $algorithm  ハッシュアルゴリズム (default: SHA256)
     * @return string ハッシュ文字列
     */
    public static function randomHash($algorithm = 'SHA256')
    {
        return self::hash(date('Y-m-d H:i:s'), self::randomCode(8), self::randomCode(8), 10, $algorithm);
    }

    /**
     * 対象のリストから指定の件数だけランダムに選択します。
     *
     * @param mixed[] $list         選択対象リスト
     * @param int     $select_count 選択数
     * @return array{0: mixed[], 1: mixed[]} [[選択された要素], [選択されなかった要素]]
     */
    public static function randomSelect(array $list, int $select_count)
    {
        if (count($list) <= $select_count) {
            return [$list, []] ;
        }

        $selected = [];
        for ($i = 0; $i < $select_count; $i++) {
            shuffle($list);
            $idx        = mt_rand(0, count($list) - 1);
            $selected[] = $list[$idx];
            unset($list[$idx]);
        }

        return [$selected, array_merge($list)];
    }

    /**
     * 秘密鍵暗号で暗号化します。
     *
     * @param string $plain      平文
     * @param string $secret_key 秘密鍵
     * @param string $cipher     暗号器 (default: AES-256-CBC)
     * @return string 暗号
     * @throws ValueError when failed to get the cipher iv length, perhaps invlid cipher was given
     */
    public static function encript($plain, $secret_key, $cipher = 'AES-256-CBC')
    {
        if (($iv_size = openssl_cipher_iv_length($cipher)) === false || $iv_size < 1) {
            throw new ValueError("Failed to get the cipher iv length, perhaps invlid cipher was given.");
        }
        $iv        = random_bytes($iv_size);
        $encrypted = openssl_encrypt($plain, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return $iv.$encrypted;
    }

    /**
     * 秘密鍵暗号で複合化します。
     *
     * @param string $encrypted  暗号文
     * @param string $secret_key 秘密鍵
     * @param string $cipher     暗号器 (default: AES-256-CBC)
     * @return string|null 復号文 (復号失敗時は null)
     * @throws ValueError when failed to get the cipher iv length, perhaps invlid cipher was given
     */
    public static function decript($encrypted, $secret_key, $cipher = 'AES-256-CBC')
    {
        if (($iv_size = openssl_cipher_iv_length($cipher)) === false || $iv_size < 1) {
            throw new ValueError("Failed to get the cipher iv length, perhaps invlid cipher was given.");
        }
        $iv        = substr($encrypted, 0, $iv_size);
        $encrypted = substr($encrypted, $iv_size);
        $decrypted = openssl_decrypt($encrypted, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return $decrypted === false ? null : rtrim($decrypted, "\0") ;
    }

    /**
     * バイナリデータをURLに利用可能な文字列に変換します。
     *
     * @param string $byte バイナリデータ
     * @return string URL利用可能文字列
     */
    public static function pack($byte)
    {
        return strtr(base64_encode($byte), '+/=', '._-');
    }

    /**
     * URLに利用可能な文字列をバイナリデータに変換します。
     *
     * @param string $packed 文字列
     * @return string バイナリデータ
     */
    public static function unpack($packed)
    {
        return base64_decode(strtr($packed, '._-', '+/='));
    }

    /**
     * 対象のディレクトリを サブディレクトリを含め 削除します。
     *
     * @param string $dir 削除対象ディレクトリパス
     * @return void
     */
    public static function removeDir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
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
     * @param array<array-key, mixed>|object $obj     配列 or オブジェクト
     * @param array-key                      $key     キー名(.[dot]区切りでオブジェクトプロパティ階層指定可)
     * @param mixed                          $default デフォルト値 (default: null)
     * @return mixed 値
     */
    public static function get($obj, $key, $default = null)
    {
        if ($obj == null) {
            return $default;
        }

        if (is_string($key)) {
            $nests = explode('.', $key);
            if (count($nests) > 1) {
                $current = array_shift($nests);
                $target  = self::get($obj, $current);
                return $target == null ? $default : self::get($target, join('.', $nests), $default);
            }
        }

        if (is_array($obj)) {
            return $obj[$key] ?? $default;
        }

        return $obj->$key ?? $default;
    }

    /**
     * 対象の値が empty の場合にデフォルト値を返します。
     * ※ nvl の empty版
     *
     * @param mixed|null  $value   値
     * @param mixed       $default デフォルト値
     * @return mixed 値
     */
    public static function evl($value, $default)
    {
        return empty($value) ? $default : $value ;
    }

    /**
     * 対象の値が null の場合にデフォルト値を返します。
     *
     * @param mixed|null $value   値
     * @param mixed      $default デフォルト値
     * @return mixed 値
     */
    public static function nvl($value, $default)
    {
        return $value === null ? $default : $value ;
    }

    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で始まるか検査します。
     *
     * @param string $haystack 検査対象文字列
     * @param string $needle   被検査文字列
     * @return bool true : 始まる／false : 始まらない
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で終わるか検査します。
     *
     * @param string $haystack 検査対象文字列
     * @param string $needle   被検査文字列
     * @return bool true : 終わる／false : 終わらない
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /**
     * 対象の ZIP ファイルを展開します。
     *
     * @param string $zip_path ZIPファイルパス
     * @param string $dest_dir 展開先ディレクトリパス
     * @return void
     */
    public static function unzip($zip_path, $dest_dir)
    {
        $zip = new ZipArchive();
        $res = $zip->open($zip_path);
        if ($res === true) {
            $zip->extractTo($dest_dir);
            $zip->close();
        }
    }

    /**
     * 対象のパスを ZIP 圧縮します。
     *
     * @param  string                      $source_path        圧縮対象ファイル or ディレクトリ
     * @param  string                      $out_zip_path       圧縮後のZIPファイルパス
     * @param  bool                        $include_target_dir 指定ディレクトリをZIPアーカイブに含めるか否か (default: true[=含める])
     * @param  callable(string $path):bool $filter             格納データ取捨選択用フィルタ
     *                                                         ⇒ $path を引数に取り、 true を返すとそのパスを含み, false を返すとそのパスを除外する。
     *                                            　           (default: null = function($path) { return true; }; = 全データ格納)
     * @param  int                         $out_dir_permission ZIP格納ディレクトリ自動生成時のパーミッション (default: 0775)
     * @return void
     */
    public static function zip($source_path, $out_zip_path, $include_target_dir = true, $filter = null, $out_dir_permission = 0775)
    {
        if (empty($filter)) {
            $filter = function ($path) { return true; };
        }

        $path_info   = pathInfo($source_path);
        $parent_path = $path_info['dirname'] ?? '';
        $dir_name    = $path_info['basename'];

        $dest_dir = dirname($out_zip_path);
        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, $out_dir_permission, true);
        }

        $z = new ZipArchive();
        $z->open($out_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($include_target_dir) {
            $z->addEmptyDir($dir_name);
        }
        self::folderToZip($source_path, $z, strlen($include_target_dir ? "$parent_path/" : "$parent_path/$dir_name/"), $filter);
        $z->close();
    }

    /**
     * ディレクトリを再帰的にZIP圧縮します。
     *
     * @param string                      $folder           対象ディレクトリ
     * @param ZipArchive                  $zip_file         ZIPファイル
     * @param int                         $exclusive_length 除外ファイルパス
     * @param callable(string $path):bool $filter           格納データ取捨選択用フィルタ
     * @return void
     */
    private static function folderToZip($folder, &$zip_file, $exclusive_length, $filter)
    {
        if (($handle = opendir($folder)) === false) {
            throw new Exception("Can not open directory '{$folder}'.");
        }
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                if (!$filter($filePath)) {
                    continue;
                }

                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusive_length);
                if (is_file($filePath)) {
                    $zip_file->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zip_file->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zip_file, $exclusive_length, $filter);
                }
            }
        }
        closedir($handle);
    }

    /**
     * 機種依存文字が含まれるかチェックします。
     *
     * @param string $text   検査対象文字列
     * @param string $encode 機種依存チェックを行う文字コード (default: sjis-win)
     * @return string[] 機種依存文字の配列
     */
    public static function checkDependenceChar($text, $encode = 'sjis-win')
    {
        $org  = $text;
        $conv = mb_convert_encoding(mb_convert_encoding($text, $encode, 'UTF-8'), 'UTF-8', $encode);
        if (strlen($org) != strlen($conv)) {
            $diff = array_diff(self::stringToArray($org), self::stringToArray($conv));
            return $diff;
        }

        return [];
    }

    /**
     * 文字列を文字の配列に変換します。
     *
     * @param string $string 文字列
     * @return string[] 文字の配列
     * @throws Exception when failed split to letters by preg_split()
     */
    public static function stringToArray($string)
    {
        if (($letters = preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY)) === false) {
            throw new Exception("Failed split to letters by preg_split().");
        }
        return $letters;
    }

    /**
     * スネークケース(snake_case)文字列をキャメルケース(CamelCase)文字列に変換します。
     *
     * @param string $str スネークケース文字列
     * @return string キャメルケース文字列
     */
    public static function camelize($str)
    {
        return str_replace('_', '', ucwords($str, '_'));
    }

    /**
     * キャメルケース(CamelCase) 文字列をスネークケース(snake_case)文字列に変換します。
     *
     * @param string $str キャメルケース文字列
     * @return string スネークケース文字列
     */
    public static function snakize($str)
    {
        return strtolower((string)preg_replace('/[a-z]+(?=[A-Z])|[A-Z]+(?=[A-Z][a-z])/', '\0_', $str));
    }

    /**
     * 指定文字の先頭を大文字にします。
     *
     * @param string $str 文字列
     * @return string 文字列
     */
    public static function capitalize($str)
    {
        return ucfirst($str);
    }

    /**
     * 指定文字の先頭を小文字にします。
     *
     * @param string $str 文字列
     * @return string 文字列
     */
    public static function uncapitalize($str)
    {
        return lcfirst($str);
    }

    /**
     * 簡易的な BASIC認証 を掛けます。
     *
     * @param array<string, string>   $auth_list   認証許可リスト [user_name => hashed_password, ...]
     * @param callable(string):string $to_hash     ハッシュ関数 function($password):string { ... } (default: null = function ($password) { return $password; })
     * @param string                  $realm       認証入力時テキスト (default: 'Enter your ID and PASSWORD.')
     * @param string                  $failed_text 認証失敗時テキスト (default: 'Authenticate Failed.')
     * @param string                  $charset     文字コード (default: utf-8)
     * @return string|void
     */
    public static function basicAuthenticate(array $auth_list, callable $to_hash = null, $realm = "Enter your ID and PASSWORD.", $failed_text = "Authenticate Failed.", $charset = 'utf-8')
    {
        if (empty($to_hash)) {
            $to_hash = function ($password) { return $password; };
        }

        $user      = self::get($_SERVER, 'PHP_AUTH_USER');
        $pass      = $to_hash(self::get($_SERVER, 'PHP_AUTH_PW')) ;
        $auth_pass = self::get($auth_list, $user);
        if (!empty($user) && !empty($pass) && $auth_pass == $pass) {
            return $user;
        }

        header('HTTP/1.0 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="'.$realm.'"');
        header('Content-type: text/html; charset='.$charset);

        die($failed_text);
    }

    /**
     * ページをリダイレクトします。
     * ※本メソッドは exit を call します。
     *
     * @param string $url リダイレクトURL
     * @return void
     * @todo パラメータ構築などの機能を追加
     */
    public static function redirect($url)
    {
        ob_clean();
        header("HTTP/1.1 302 Found");
        header("Location: {$url}");
        exit();
    }

    /**
     * データを JSON形式 で書き出します。
     * ※本メソッドは exit を call します。
     *
     * @param object|array<array-key, mixed> $data オブジェクト
     * @return void
     */
    public static function json($data)
    {
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
     * @param object|array<array-key, mixed> $data     オブジェクト
     * @param string                         $callback コールバック関数
     * @return void
     */
    public static function jsonp($data, $callback)
    {
        ob_clean();
        header("HTTP/1.1 200 OK");
        header('Content-Type: application/javascript; charset=UTF-8');
        echo "{$callback}(".json_encode($data).")";
        exit();
    }

    /**
     * 指定の配列が連想配列かチェックします。
     *
     * @param array<array-key, mixed> $array 配列 or 連想配列
     * @return bool true : 連想配列／false : 配列
     */
    public static function isMap(array $array)
    {
        return array_values($array) !== $array;
    }

    /**
     * 多次元配列を一次元配列に変換します。
     *
     * @param array<mixed[]> $array 多次元配列
     * @return mixed[] 1次元配列
     */
    public static function flatten(array $array)
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), false);
    }

    /**
     * 指定のオブジェクト配列から指定の要素を抽出します。
     *
     * @param array<mixed[]|object> $array 対象配列
     * @param string                $field 抽出対象フィールド名
     * @return mixed[]
     */
    public static function pick(array $array, $field)
    {
        if (empty($array) || empty($field)) {
            return [];
        }
        $picks = [];
        foreach ($array as $value) {
            $picks[] = self::get($value, $field);
        }
        return $picks;
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
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) { return $val; },
     *    $rs,
     *    UserDetailDto::class
     *  );
     *
     * // ケース2 ： $rs 内の UserDetailDto クラスの全フィールドを出力／日付のフォーマットを指定
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) {
     *         if($val instanceof DateTime) { return $val->format('Y年m月d日 H:i'); }
     *         return $val;
     *    },
     *    $rs,
     *    UserDetailDto::class
     *  );
     *
     * // ケース3 ： 指定のフィールドを任意の列順で出力
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) { return $val; },
     *    $rs,
     *    ['user_id','mail_address','last_name','first_name']
     *  );
     *
     * // ケース4 ： 存在しない項目を固定値で追加
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) {
     *         if($col == 'fixed_col') { return 1; }
     *         return $val;
     *    },
     *    $rs,
     *    ['user_id','mail_address','last_name','first_name','fixed_col']
     *  );
     *
     * // ケース5 ： 複数項目を結合して出力
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) {
     *         if($col == 'name') { return "{$line->last_name} {$line->first_name}"; }
     *         return $val;
     *    },
     *    $rs,
     *    ['user_id','mail_address','name')]
     *  );
     *
     * // ケース6 ： ヘッダ行を出力しない
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) { return $val; },
     *    $rs,
     *    UserDetailDto::class,
     *    false
     *  );
     *
     * // ケース7 ： ヘッダラベル指定（配列指定）
     * // ※配列の範囲外の項目はシステムラベルで出力されます
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) { return $val; },
     *    $rs,
     *    ['user_id','mail_address','last_name','first_name'],
     *    true,
     *    ['会員ID','メールアドレス','姓','名']
     *  );
     *
     * // ケース8 ： ヘッダラベル指定（連想配列指定）
     * // ※連想配列に定義の無い項目はシステムラベルで出力されます
     * Util::csv(
     *    "user_list_".date('YmdHis').'.csv',
     *    function($line, $col, $val) { return $val; },
     *    $rs,
     *    UserDetailDto::class,
     *    true,
     *    [
     *        'user_id'      => '会員ID',
     *        'mail_address' => 'メールアドレス',
     *        'last_name'    => '姓',
     *        'first_name'   => '名'
     *    ]
     *  );
     *
     * @param string                                                                         $file_name  出力ファイル名
     * @param callable(object|array<string, mixed> $row, string|int $col, mixed $val):string $converter  コンバータ
     * @param array<mixed[]>|object[]                                                        $rs         結果セット
     * @param string[]|class-string                                                          $cols       出力対象列名リスト or DTOクラス名
     * @param bool                                                                           $has_header true : ヘッダ行を出力する／false : ヘッダ行を出力しない (default: true)
     * @param string[]|array<string, string>                                                 $col_labels ヘッダ行のラベル指定(配列又は連想配列) (default: [])
     * @param string                                                                         $encoding   CSVファイルエンコーディング (default: SJIS-win)
     * @return void
     */
    public static function csv($file_name, $converter, array $rs, $cols, $has_header = true, $col_labels = [], $encoding = 'SJIS-win')
    {
        if (is_string($cols)) {
            $reflect = new ReflectionClass($cols);
            $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
            $cols    = [];
            foreach ($props as $prop) {
                $cols[] = $prop->getName();
            }
        }

        // 出力
        static::csvOpen($file_name);
        if ($has_header) {
            static::csvHeader($cols, $col_labels, $encoding);
        }
        foreach ($rs as $i => $row) {
            static::csvLine(!$has_header && $i === 0, $row, $cols, $converter, $encoding);
        }
        static::csvClose();
    }

    /**
     * CSV出力：手順(1)　HTTPヘッダを出力し、CSVデータダウンロードを開始します。
     * ※CSVダウンロードに伴うメモリ使用量を削減したい場合はこれらのCSV出力パーツ関数を組み合わせて利用して下さい。
     *
     * @param string $file_name 出力ファイル名
     * @param string $encoding CSVファイル名エンコーディング (default: SJIS-win)
     * @return void
     */
    public static function csvOpen($file_name, $encoding = 'SJIS-win')
    {
        ob_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/force-download");
        header('Content-Disposition: attachment; filename=' . mb_convert_encoding($file_name, $encoding, "UTF-8"));
        header("Content-Transfer-Encoding: binary");
    }

    /**
     * CSV出力：手順(2)　CSVファイルのヘッダ行を書き出します。
     * ※CSVダウンロードに伴うメモリ使用量を削減したい場合はこれらのCSV出力パーツ関数を組み合わせて利用して下さい。
     * ※ヘッダ行が存在しない CSV ファイルでは呼び出す必要はありません。
     *
     * @param string[]                       $cols       出力対象列名リスト
     * @param string[]|array<string, string> $col_labels ヘッダ行のラベル指定(配列又は連想配列) (default: [])
     * @param string                         $encoding   CSVファイルデータエンコーディング (default: SJIS-win)
     * @return void
     */
    public static function csvHeader(array $cols, array $col_labels = [], $encoding = 'SJIS-win')
    {
        $line  = '';
        $isMap = self::isMap($col_labels);
        foreach ($cols as $i => $col) {
            $val = $isMap ? self::get($col_labels, $col, $col) : self::get($col_labels, $i, $col) ;
            $line .= '"'.str_replace('"', '""', $val).'",';
        }
        $line  = substr($line, 0, -1);
        echo mb_convert_encoding($line, $encoding, "UTF-8");
    }

    /**
     * CSV出力：手順(3)　CSVファイルのデータ行を書き出します。
     * ※CSVダウンロードに伴うメモリ使用量を削減したい場合はこれらのCSV出力パーツ関数を組み合わせて利用して下さい。
     * ※出力データ分だけ繰り返し呼び出して下さい。
     *
     * @param bool                                                                                $is_first_line 最初の行か否か
     * @param array<string, mixed>|object                                                         $row           結果データ（１行分のデータ）
     * @param string[]                                                                            $cols          出力対象列名リスト
     * @param callable(object|array<string, mixed> $row, string|int $col, mixed $val):string|null $converter     コンバータ (default: null)
     * @param string                                                                              $encoding      CSVファイルデータエンコーディング (default: SJIS-win)
     * @return void
     */
    public static function csvLine(bool $is_first_line, $row, array $cols, $converter = null, $encoding = 'SJIS-win')
    {
        $line = $is_first_line ? '' : "\n" ;
        foreach ($cols as $col) {
            $val = self::get($row, $col) ;
            if ($converter) {
                $val = $converter($row, $col, $val);
            }
            $line .= '"'.str_replace('"', '""', $val).'",';
        }
        $line  = substr($line, 0, -1);
        echo mb_convert_encoding($line, $encoding, "UTF-8");
    }

    /**
     * CSV出力：手順(3´)　CSVファイルのデータ行（文字列データ）をそのまま書き出します。
     * ※CSVダウンロードに伴うメモリ使用量を削減したい場合はこれらのCSV出力パーツ関数を組み合わせて利用して下さい。
     * ※出力データ分だけ繰り返し呼び出して下さい。
     *
     * @param bool     $is_first_line 最初の行か否か
     * @param string[] $row           CSV文字列データ
     * @param string   $encoding      CSVファイルデータエンコーディング (default: SJIS-win)
     * @return void
     */
    public static function csvRawLine(bool $is_first_line, array $row, $encoding = 'SJIS-win')
    {
        $line = $is_first_line ? '' : "\n" ;
        foreach ($row as $val) {
            $line .= '"'.str_replace('"', '""', $val).'",';
        }
        $line  = substr($line, 0, -1);
        echo mb_convert_encoding($line, $encoding, "UTF-8");
    }

    /**
     * CSV出力：手順(4)　CSVファイル出力を閉じます。
     * ※CSVダウンロードに伴うメモリ使用量を削減したい場合はこれらのCSV出力パーツ関数を組み合わせて利用して下さい。
     *
     * @return void
     */
    public static function csvClose()
    {
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
     * @return callable(mixed $s):mixed
     */
    public static function heredocImplanter()
    {
        return function ($s) {return $s;};
    }

    /**
     * 指定日時点での年齢を計算します。
     *
     * @param DateTime|null $birthday 生年月日 (default: null)
     * @param DateTime|null $at       起点日 (default: null)
     * @return int|null 起点日における年齢
     */
    public static function ageAt(DateTime $birthday = null, DateTime $at = null)
    {
        if (empty($birthday)) {
            return null;
        }
        $at = self::nvl($at, new DateTime());
        return intval(floor(($at->format('Ymd') - $birthday->format('Ymd')) / 10000));
    }

    /**
     * int 型に変換します
     *
     * @param string|null $var  変換対象
     * @param int         $base 基数 (default: 10)
     * @return int|null
     */
    public static function intval($var, $base = 10)
    {
        return $var === null || $var == '' ? null : intval($var, $base);
    }

    /**
     * float 型に変換します
     *
     * @param string|null $var 変換対象
     * @return float|null
     */
    public static function floatval($var)
    {
        return $var === null || $var == '' ? null : floatval($var);
    }

    /**
     * double 型に変換します
     *
     * @param string|null  $var 変換対象
     * @return double|null
     */
    public static function doubleval($var)
    {
        return $var === null || $var == '' ? null : doubleval($var);
    }

    /**
     * file_get_contents で指定URLのページデータを取得します。
     *
     * @param string $url URL
     * @return mixed 受信データ
     */
    public static function urlGetContents($url)
    {
        return file_get_contents($url, false, stream_context_create([
            'http' => ['ignore_errors' => true],
            'ssl'  => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ],
        ]));
    }

    /**
     * 対象のCSVファイルを読み込みます。
     *
     * @param string $file CSVファイル
     * @param int $flags SplFileObject用フラグ (default: SplFileObject::READ_CSV)
     * @return \SplFileObject
     * @throws Exception when failed to get file contents by file_get_contents().
     */
    public static function loadCsv($file, $flags = SplFileObject::READ_CSV)
    {
        if (($data = file_get_contents($file)) === false) {
            throw new Exception("Failed to get file contents by file_get_contents().");
        }
        $data = mb_convert_encoding($data, 'UTF-8', 'auto');
        $data = preg_replace('/^\xEF\xBB\xBF/', '', $data); // BOMがあれば除去
        file_put_contents($file, $data);

        setlocale(LC_ALL, 'ja_JP.UTF-8');
        $csv = new SplFileObject($file);
        $csv->setFlags($flags);
        $csv->setCsvControl(',', '"', '"');
        return $csv;
    }
}
