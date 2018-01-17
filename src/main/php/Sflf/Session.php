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
 * Session::regenerate();
 * Session::set('LOGIN', $user);
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Session {
	
	const SESSION_KEY_PREFIX = "SFLF_SESSION_";
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * セッションを開始します。
	 * 
	 * @param SessionHandlerInterface $handler  セッションハンドラ（デフォルト： null）
	 * @param array                   $options  session_start のオプション引数（デフォルト： []）
	 * @return type
	 */
	public static function start(SessionHandlerInterface $handler = null, array $options = []) {
		if(!empty($handler)) {
			session_set_save_handler($handler, true);
		}
		return session_start($options);
	}
	
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
	
	/**
	 * セッションを再生成します。
	 * 
	 * @param int $probability 生成確率母数 (デフォルト：1/30)
	 * @param int $interval    生成間隔[秒] (デフォルト：300秒)
	 */
	public static function regenerate($probability = 30, $interval = 300) {
		if(mt_rand(1, $probability) == 1) {
			$session_file = ini_get('session.save_path') . '/' . 'sess_'.session_id();
			if(file_exists($session_file)) {
				$filemtime = filemtime($session_file);
				if($filemtime !== false && $filemtime + $interval <= time()) {
					session_regenerate_id( true );
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
 * （デフォルトでは read のみがリトライ処理対象となります）
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
	// リトライモード
	const RETRY_OPEN    =  1;
	const RETRY_READ    =  2;
	const RETRY_WRITE   =  4;
	const RETRY_DESTROY =  8;
	
	const RETRY_ALL = self::RETRY_OPEN | self::RETRY_READ | self::RETRY_WRITE | self::RETRY_DESTROY ;
	
	/**
	 * リトライモード
	 * @var type 
	 */
	private $mode;
	
	/**
	 * 最大リトライ回数
	 * @var int
	 */
	private $maxRetry;
	
	/**
	 * リトライまでのインターバル[ミリ秒]
	 * @var int
	 */
	private $interval;
	
	/**
	 * エラー状態
	 * @var type 
	 */
	private $hasError = false;
	
	/**
	 * リトライハンドラを構築します。
	 * 
	 * @param int $maxRetry 最大リトライ回数
	 * @param int $interval リトライまでのインターバル[ミリ秒]
	 * @param int $mode     リトライモード RetrySessionHandler::RETRY_* の論理和 （デフォルト： RETRY_OPEN | RETRY_READ）
	 */
	public function __construct($maxRetry, $interval, $mode = self::RETRY_OPEN | self::RETRY_READ) {
        $this->maxRetry = $maxRetry;
        $this->interval = $interval;
        $this->mode     = $mode;
		$old_handler    = set_error_handler(null);
		set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$old_handler)
		{
			$this->hasError = true;
			if(is_callable($old_handler)) {
				$old_handler($errno, $errstr, $errfile, $errline);
			}
		});
	}
	
	/**
	 * オーバーライド
	 */
	public function open($savePath, $sessionName) {
		if(!($this->mode & self::RETRY_OPEN)) {
			return parent::open($savePath, $sessionName);
		}
		
		$isOpend = false;
		$retry   = 0;

		while(true) {
			try {
				$this->hasError = false;
				$isOpend = parent::open($savePath, $sessionName);
				if(!$this->hasError && ($isOpend || $retry++ >= $this->maxRetry)) { break; }
			} catch (Throwable $t) {
				if($retry++ >= $this->maxRetry) { throw $t; }
			}
			usleep($this->interval * 1000);
		}

		return $isOpend;
	}
	
	/**
	 * オーバーライド
	 */
	public function read($id) {
		if(!($this->mode & self::RETRY_READ)) {
			return parent::read($id);
		}
		
		$data  = '';
		$retry = 0;

		while(true) {
			try {
				$this->hasError = false;
				$data = parent::read($id);
				if(!$this->hasError || $retry++ >= $this->maxRetry) { break; }
			} catch (Throwable $t) {
				if($retry++ >= $this->maxRetry) { throw $t; }
			}
			usleep($this->interval * 1000);
		}

		return $data;
	}
	
	/**
	 * オーバーライド
	 */
	public function write($id, $data) {
		if(!($this->mode & self::RETRY_WRITE)) {
			return parent::write($id, $data);
		}
		
		$isWrote = false;
		$retry   = 0;

		while(true) {
			try {
				$this->hasError = false;
				$isWrote = parent::write($id, $data);
				if(!$this->hasError && ($isWrote || $retry++ >= $this->maxRetry)) { break; }
			} catch (Throwable $t) {
				if($retry++ >= $this->maxRetry) { throw $t; }
			}
			usleep($this->interval * 1000);
		}

		return $isWrote;
	}

	/**
	 * オーバーライド
	 */
	public function destroy($sessionId) {
		if(!($this->mode & self::RETRY_DESTROY)) {
			return parent::destroy($sessionId);
		}
		
		$isDestroied = false;
		$retry       = 0;

		while(true) {
			try {
				$this->hasError = false;
				$isDestroied = parent::destroy($sessionId);
				if(!$this->hasError && ($isDestroied || $retry++ >= $this->maxRetry)) { break; }
			} catch (Throwable $t) {
				if($retry++ >= $this->maxRetry) { throw $t; }
			}
			usleep($this->interval * 1000);
		}

		return $isDestroied;
	}
}
