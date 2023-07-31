<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 セッション クラス
 *
 * 【使い方】
 * require_once "/path/to/Session.php"; // or use AutoLoader
 *
 * Session::start(new RetrySessionHandler(3, 100));
 * Session::regenerate(30, 300);
 * Session::set('LOGIN', $user);
 *
 * @package   SFLF
 * @version   v1.0.4
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Session
{
    /**
     * @var string セッションキープレフィックス
     */
    const SESSION_KEY_PREFIX = "SFLF_SESSION_";

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * セッションを開始します。
     *
     * @param SessionHandlerInterface $handler  セッションハンドラ (default: null)
     * @param array<string, mixed>    $options  session_start のオプション引数 (default: [])
     * @return bool
     */
    public static function start(SessionHandlerInterface $handler = null, array $options = [])
    {
        if (!empty($handler)) {
            session_set_save_handler($handler, true);
        }
        return session_start($options);
    }

    /**
     * セッションに値を保存します。
     *
     * @param string $key   キー名
     * @param object $value 値
     * @return void
     */
    public static function set($key, $value)
    {
        $_SESSION[self::SESSION_KEY_PREFIX.$key] = serialize($value);
    }

    /**
     * セッションから値を取得します。
     *
     * @param string      $key     キー名
     * @param object|null $default デフォルト値 (default: null)
     * @return mixed 格納した値
     */
    public static function get($key, $default = null)
    {
        return isset($_SESSION[self::SESSION_KEY_PREFIX.$key]) ? unserialize($_SESSION[self::SESSION_KEY_PREFIX.$key]) : $default ;
    }

    /**
     * セッションが存在するかチェックします。
     *
     * @param string $key キー名
     * @return bool true : 存在する／false : 存在しない
     */
    public static function exists($key)
    {
        return isset($_SESSION[self::SESSION_KEY_PREFIX.$key]) ;
    }

    /**
     * セッション情報を削除します。
     *
     * @param string $key キー名
     * @return void
     */
    public static function remove($key)
    {
        if (self::exists($key)) {
            unset($_SESSION[self::SESSION_KEY_PREFIX.$key]);
        }
    }

    /**
     * セッションから値を取得し、その値を削除します。
     *
     * @param string      $key     キー名
     * @param object|null $default デフォルト値 (default: null)
     * @return mixed 格納した値
     */
    public static function pull($key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * セッションを再生成します。
     *
     * @param int $probability 生成確率母数 (default: 1/1)
     * @param int $interval    生成間隔[秒] (default: 0)
     * @return void
     */
    public static function regenerate($probability = 1, $interval = 0)
    {
        if (mt_rand(1, $probability) == 1) {
            $session_file = ini_get('session.save_path') . '/' . 'sess_'.session_id();
            if (file_exists($session_file)) {
                $filemtime = filemtime($session_file);
                if ($filemtime !== false && $filemtime + $interval <= time()) {
                    session_regenerate_id(true);
                }
            }
        }
    }
}


/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 リトライセッションハンドラ クラス（Session付帯クラス）
 *
 * セッションハンドラの各種処理が失敗した際に指定回数だけリトライするセッションハンドラ。
 * なおリトライ対象は open / read / write / destroy で、 RetrySessionHandler::RETRY_* によるリトライモードの ON/OFF 指定でリトライ対象を制御できます。
 * （デフォルトでは open & read がリトライ処理対象となります）
 *
 * 本セッションハンドラは主に session.save_handler = files 時に Ajax などで同時並行に多数のアクセスがあった場合において、
 * 稀にセッションロストする現象への対策用として定義されていますが files ハンドラでも多数のアクセスが無ければ、
 * 又は memcached や 独自のDBセッションハンドラ などを利用するのであれば特に利用する必要はありません。
 *
 * なお、本セッションハンドラはベースのセッションハンドラの実装形態に依存しない構成のため、
 * 必要に応じて files 以外のセッションハンドラとも併用できる可能性があります（未検証）
 *
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class RetrySessionHandler extends SessionHandler
{
    /** @var int リトライモード：OPEN */
    const RETRY_OPEN    =  1;
    /** @var int リトライモード：READ */
    const RETRY_READ    =  2;
    /** @var int リトライモード：WRITE */
    const RETRY_WRITE   =  4;
    /** @var int リトライモード：DESTROY */
    const RETRY_DESTROY =  8;

    /** @var int リトライモード：ALL(= OPEN + READ + WRITE + DESTROY) */
    const RETRY_ALL = self::RETRY_OPEN | self::RETRY_READ | self::RETRY_WRITE | self::RETRY_DESTROY ;

    /**
     * リトライモード
     * @var int
     */
    private $mode;

    /**
     * 最大リトライ回数
     * @var int
     */
    private $max_retry;

    /**
     * リトライまでのインターバル[ミリ秒]
     * @var int
     */
    private $interval;

    /**
     * エラー状態
     * @var bool
     */
    private $has_error = false;

    /**
     * リトライハンドラを構築します。
     *
     * @param int $max_retry 最大リトライ回数
     * @param int $interval リトライまでのインターバル[ミリ秒]
     * @param int $mode     リトライモード RetrySessionHandler::RETRY_* の論理和 (default: RETRY_OPEN | RETRY_READ)
     */
    public function __construct($max_retry, $interval, $mode = self::RETRY_OPEN | self::RETRY_READ)
    {
        $this->max_retry = $max_retry;
        $this->interval  = $interval;
        $this->mode      = $mode;
        $old_handler     = set_error_handler(null);
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$old_handler) {
            $this->has_error = true;
            if (is_callable($old_handler)) {
                $old_handler($errno, $errstr, $errfile, $errline);
            }
            return false;
        });
    }

    /**
     * オーバーライド
     *
     * @param string $save_path セッションセーブパス
     * @param string $session_name セッション名
     * @return bool
     */
    public function open($save_path, $session_name) : bool
    {
        if (!($this->mode & self::RETRY_OPEN)) {
            return parent::open($save_path, $session_name);
        }

        $is_opend = false;
        $retry    = 0;

        while (true) {
            try {
                $this->has_error = false;
                $is_opend        = parent::open($save_path, $session_name);
                // @phpstan-ignore-next-line Phpstan doesn't consider to set has_error in error_handler
                if (!$this->has_error && ($is_opend || $retry++ >= $this->max_retry)) {
                    break;
                }
            } catch (Throwable $t) {
                if ($retry++ >= $this->max_retry) {
                    throw $t;
                }
            }
            usleep($this->interval * 1000);
        }

        return $is_opend;
    }

    /**
     * オーバーライド
     *
     * @param string $id
     * @return string エラー時は false
     */
    public function read($id) : string
    {
        if (!($this->mode & self::RETRY_READ)) {
            return parent::read($id);
        }

        $data  = '';
        $retry = 0;

        while (true) {
            try {
                $this->has_error = false;
                $data            = parent::read($id);
                // @phpstan-ignore-next-line Phpstan doesn't consider to set has_error in error_handler
                if (!$this->has_error || $retry++ >= $this->max_retry) {
                    break;
                }
            } catch (Throwable $t) {
                if ($retry++ >= $this->max_retry) {
                    throw $t;
                }
            }
            usleep($this->interval * 1000);
        }

        return $data;
    }

    /**
     * オーバーライド
     *
     * @param string $id   セッションID
     * @param string $data 書き込みデータ
     * @return bool
     */
    public function write($id, $data) : bool
    {
        if (!($this->mode & self::RETRY_WRITE)) {
            return parent::write($id, $data);
        }

        $is_wrote = false;
        $retry    = 0;

        while (true) {
            try {
                $this->has_error = false;
                $is_wrote        = parent::write($id, $data);
                // @phpstan-ignore-next-line Phpstan doesn't consider to set has_error in error_handler
                if (!$this->has_error && ($is_wrote || $retry++ >= $this->max_retry)) {
                    break;
                }
            } catch (Throwable $t) {
                if ($retry++ >= $this->max_retry) {
                    throw $t;
                }
            }
            usleep($this->interval * 1000);
        }

        return $is_wrote;
    }

    /**
     * オーバーライド
     *
     * @param string $session_id セッションID
     * @return bool
     */
    public function destroy($session_id) : bool
    {
        if (!($this->mode & self::RETRY_DESTROY)) {
            return parent::destroy($session_id);
        }

        $is_destroied = false;
        $retry        = 0;

        while (true) {
            try {
                $this->has_error = false;
                $is_destroied    = parent::destroy($session_id);
                // @phpstan-ignore-next-line Phpstan doesn't consider to set has_error in error_handler
                if (!$this->has_error && ($is_destroied || $retry++ >= $this->max_retry)) {
                    break;
                }
            } catch (Throwable $t) {
                if ($retry++ >= $this->max_retry) {
                    throw $t;
                }
            }
            usleep($this->interval * 1000);
        }

        return $is_destroied;
    }
}
