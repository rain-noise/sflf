<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 MySQL データベースアクセスオブジェクト クラス
 * 
 * 【使い方】
 * //[php.ini] date.timezone = Asia/Tokyo
 * require_once "/path/to/Dao.php"; // or use AutoLoader
 * require_once "/path/to/Log.php"; // or use AutoLoader
 *  
 * Dao::connect('host', 'user', 'pass', 'dbName', $port, function($sql){ Log::debug("SQL :: {$sql}"); });
 * try {
 *     Dao::begin();
 *     $user = Dao::find('SELECT * FROM user WHERE id = :id',array(':id' => $id), UserEntiry::class);
 *     // You can set IN phrase like 'WHERE status IN (:status)'. ($status will be converted comma separated values when $status is array) 
 *     
 *     // Something to do
 *     
 *     Dao::commit();
 * } catch(Throwable $e) {
 *     Dao::rollback();
 * }
 * 
 * 又は
 * 
 * Dao::connect('host', 'user', 'pass', 'dbName', $port, function($sql){ Log::debug("SQL :: {$sql}"); });
 * Dao::transaction(function(){
 *     $user = Dao::find('SELECT * FROM user WHERE id = :id',array(':id' => $id), UserEntiry::class);
 *     // Something to do
 * });
 * 
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/includes/paginate.tpl ページ送り Smarty テンプレート
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
 class Dao
{
	const SHUTDOWN_MODE_DO_NOTHING = 1;
	const SHUTDOWN_MODE_ROLLBACK   = 2;
	const SHUTDOWN_MODE_COMMIT     = 3;
	
	/**
	 * データベースオブジェクト
	 * 
	 * @var mysqli
	 */
	private static $_DB;
	
	/**
	 * SQLログ出力用コールバック関数
	 * 
	 * function($sql) を処理する Closure が設定されます。
	 * NULL の場合、SQLログ出力は行われません。
	 * 
	 * @var function function($sql){}
	 */
	private static $_SQL_LOG_CALLBACK = null;
	
	/**
	 * シャットダウンモード
	 * 
	 * exit() / die() などが呼ばれた際のシャットダウン時のトランザクション挙動を定義します。
	 * @var int Dao::SHUTDOWN_MODE_* （デフォルト： Dao::SHUTDOWN_MODE_DO_NOTHING）
	 */
	private static $_SHUTDOWN_MODE = self::SHUTDOWN_MODE_DO_NOTHING;
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * SQLログ出力用のコールバック関数を設定します。
	 * 
	 * 例）
	 * Dao::setSqlLogger(function($sql){ echo("<div>{$sql}</div>"); });
	 * Dao::setSqlLogger(function($sql){ error_log($sql, 3, '/path/to/sql.log'); });
	 * Dao::setSqlLogger(function($sql){ Log::debug("SQL :: {$sql}"); });
	 * Dao::setSqlLogger(function($sql) use ($logger){ $logger->debug("SQL :: {$sql}"); });
	 * 
	 * @param function $callback
	 */
	public static function setSqlLogger($callback) {
		self::$_SQL_LOG_CALLBACK = $callback;
	}
	
	/**
	 * データベースに接続します。
	 * ※ autocommit は off になります。
	 * ※ トランザクション分離レベル は READ COMMITTED になります。
	 * 
	 * @param  string   $host   接続ホスト
	 * @param  string   $user   接続ユーザー名
	 * @param  string   $pass   接続パスワード
	 * @param  string   $dbName 接続データベース名
	 * @param  string   $port   接続ポート番号     - デフォルト ini_get("mysqli.default_port")
	 * @param  function $logger SQLログ出力用コールバック関数
	 * @return boolean true : 新規接続時／false : 既存コネクション存在時
	 * @throws DatabaseException データベース接続に失敗した場合
	 */
	public static function connect($host, $user, $pass, $dbName, $port = null, $logger = null) {
		if(!self::$_DB) {
			if(isset($logger)) {
				self::setSqlLogger($logger);
			}
			
			$port = $port ? $port : ini_get("mysqli.default_port") ;
			self::$_DB = new mysqli($host, $user, $pass, $dbName, $port);
			if(self::$_DB->connect_error) {
				throw new DatabaseException(self::_createErrorMessage(__METHOD__));
			}
			
			self::$_DB->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
			self::$_DB->autocommit(false);
			
			register_shutdown_function(function(){
				switch (self::$_SHUTDOWN_MODE) {
					case self::SHUTDOWN_MODE_ROLLBACK:
						return self::rollback(true);
					case self::SHUTDOWN_MODE_COMMIT:
						return self::commit();
				}
			});
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * トランザクションを開始します。
	 * 
	 * @return void
	 * @throws DatabaseException
	 */
	public  static function begin() {
		if(!empty(self::$_DB) && !self::$_DB->begin_transaction()) {
			throw new DatabaseException(self::_createErrorMessage(__METHOD__));
		}
	}
	
	/**
	 * トランザクションをロールバックします。
	 * 
	 * @param  boolean $quiet true : 例外を throw しない(デフォルト)／false : 例外を throw する
	 * @return void
	 * @throws DatabaseException
	 */
	public  static function rollback($quiet=true) {
		if(!empty(self::$_DB) && !self::$_DB->rollback() && !$quiet) {
			throw new DatabaseException(self::_createErrorMessage(__METHOD__));
		}
	}
	
	/**
	 * トランザクションをコミットします。
	 * 
	 * @return void
	 * @throws DatabaseException
	 */
	public  static function commit() {
		if(!empty(self::$_DB) && !self::$_DB->commit()) {
			throw new DatabaseException(self::_createErrorMessage(__METHOD__));
		}
	}
	
	/**
	 * トランザクションを開始／ロールバック／コミットします。
	 * 
	 * @param function $callback      ひとまとまりの処理
	 * @param int      $shutdown_mode シャットダウンモード Dao::SHUTDOWN_MODE_* (デフォルト： Dao::SHUTDOWN_MODE_COMMIT)
	 */
	public  static function transaction($callback, $shutdown_mode = self::SHUTDOWN_MODE_COMMIT) {
		self::$_SHUTDOWN_MODE = $shutdown_mode;
		try {
			self::begin();
			$callback();
			self::commit();
		} catch (Throwable $e) {
			self::rollback(true);
			throw $e;
		}
	}
	
	/**
	 * 文字列をエスケープします。
	 * 
	 * @param  string $val
	 * @return string エスケープ文字列
	 */
	public static function escape($val) {
		if(is_object($val)) {
			$val = method_exists($val, '__toString') ? $val->__toString() : null ;
		}
		return self::$_DB->escape_string($val);
	}
	
	/**
	 * 直近で挿入した自動採番のID番号を取得します。
	 * 
	 * @return long インサートID
	 */
	public static function getInsertId() {
		return self::$_DB->insert_id;
	}
	
	/**
	 * 直近の INSERT、 UPDATE、REPLACE あるいは DELETE クエリにより変更された行の数を返します。
	 * 
	 * @return long 更新件数
	 */
	public static function getAffectedRows() {
		return self::$_DB->affected_rows;
	}
	
	/**
	 * 指定のSQLを実行します。
	 * ※戻り値は mysqli_query() の戻り値(falseを除く)となります。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @return mysqli_query|mysqli_result 結果セット
	 * @throws DatabaseException
	 */
	public static function query($sql, $params = array()) {
		$sql = self::_compile($sql, $params);
		if(self::$_SQL_LOG_CALLBACK) {
			$log = self::$_SQL_LOG_CALLBACK;
			$log($sql);
		}
		
		$rs = self::$_DB->query($sql);
		if($rs === false) {
			throw new DatabaseException("Execute query failed : ".self::$_DB->error."\n--- [SQL] ---\n{$sql}\n-------------\n");
		}
		
		return $rs;
	}
	
	/**
	 * 指定のSQLを実行し、結果を複数件取得します。
	 * ※戻り値は array($clazz) になります。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @param  string    $clazz  結果セットのマッピング型 - デフォルト 'stdClass'
	 * @return array 検索結果
	 * @throws DatabaseException
	 */
	public static function select($sql, $params = array(), $clazz = 'stdClass') {
		$rs = self::query($sql, $params);
		if(is_bool($rs) || empty($rs)) { return array(); }
		
		$types = array();
		foreach ($rs->fetch_fields() AS $meta) {
			$types[$meta->name] = $meta->type;
		}
		
		$list  = array();
		foreach ($rs AS $row) {
			$entity = new $clazz();
			foreach ($row AS $col => $val) {
				$entity->$col = self::_convertToPhp($val, $types[$col]) ;
			}
			$list[] = $entity;
		}
		
		return $list;
	}
	
	/**
	 * 指定のSQLを実行し、指定列の結果を配列で取得します。
	 * 
	 * @param  string    $col    列名
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @return array 検索結果から指定列のみを抽出したリスト
	 * @throws DatabaseException
	 */
	public static function lists($col, $sql, $params = array()) {
		$rs = self::query($sql, $params);
		if(is_bool($rs) || empty($rs)) { return array(); }
		
		$types = array();
		foreach ($rs->fetch_fields() AS $meta) {
			$types[$meta->name] = $meta->type;
		}
		
		$list  = array();
		foreach ($rs AS $row) {
			$list[] = self::_convertToPhp($row[$col], $types[$col]);
		}
		
		return $list;
	}
	
	/**
	 * 指定のSQLを実行し、結果を1件取得します。
	 * ※戻り値は $clazz or null になります。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @param  string    $clazz  結果セットのマッピング型 - デフォルト 'stdClass'
	 * @return obj $clazz で指定した検索結果
	 * @throws DatabaseException
	 */
	public static function find($sql, $params = array(), $clazz = 'stdClass') {
		$list = self::select($sql, $params, $clazz);
		if(empty($list)) { return null; }
		return $list[0];
	}
	
	/**
	 * 対象SQLの検索結果件数を取得します。
	 * ※戻り値は int になります。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @return int 検索結果件数
	 * @throws DatabaseException
	 */
	public static function count($sql, $params = array()) {
		$row = self::find("SELECT count(*) AS count FROM ({$sql}) AS T", $params);
		return $row ? intval($row->count) : 0 ;
	}
	
	/**
	 * 対象SQLの結果が存在するかを判定します。
	 * ※戻り値は boolean になります。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @return boolean true : 存在する／false : 存在しない
	 * @throws DatabaseException
	 */
	public static function exists($sql, $params = array()) {
		$row = self::find("{$sql} LIMIT 1", $params);
		return !empty($row);
	}
	
	/**
	 * 指定のSQLを実行し、結果を複数件取得します。
	 * ※本処理はページング処理を行った検索を提供します。
	 * ※戻り値は array(PageInfo, array($clazz)) です。
	 * 
	 * list($pi, $rs) = Dao::paginate(1, 25, "SELECT * FROM ...", array(':status' => 1), 'UserEntity');
	 * 
	 * @param int       $page     取得ページ
	 * @param int       $pageSize 1ページのサイズ(データ件数)
	 * @param string    $sql      SQL文
	 * @param array|obj $params   パラメータ
	 * @param string    $clazz    結果セットのマッピング型 - デフォルト 'stdClass'
	 * @return array(PageInfo, array($clazz)) ページ情報と検索結果
	 * @throws DatabaseException
	 */
	public static function paginate($page, $pageSize, $sql, $params = array(), $clazz  = 'stdClass') {
		$pi = new PageInfo($page, $pageSize, self::count($sql, $params));
		$rs = self::select("$sql LIMIT {$pi->offset}, {$pi->pageSize}", $params, $clazz);
		return array($pi, $rs);
	}
	
	/**
	 * 対象のテーブルにデータを挿入します。
	 * ※戻り値は insert_id になります。
	 * ※エンティティに const DAO_IGNORE_FILED = ['exclude_col', ...] 定数フィールドが定義されている場合、指定されたフィールドは INSERT 文から除外されます
	 * 
	 * @param string    $tableName テーブル名
	 * @param array|obj $entity    エンティティ情報
	 * @return long インサートID
	 * @throws DatabaseException
	 */
	public static function insert($tableName, $entity) {
		$reflect = new ReflectionClass(get_class($entity));
		$ignore  = $reflect->hasConstant('DAO_IGNORE_FILED') ? $reflect->getConstant('DAO_IGNORE_FILED') : array() ;
		$cols   = array();
		$values = array();
		foreach ($entity AS $col => $value) {
			if(in_array($col, $ignore)) { continue; }
			$cols[]   = $col;
			$values[] = self::_convertToSql($value);
		}
		
		self::query("INSERT INTO {$tableName} (".join(',',$cols).") VALUES (".join(',',$values).")") ;
		return self::getInsertId();
	}
	
	/**
	 * 対象のテーブルのデータを更新します。
	 * ※エンティティに const DAO_IGNORE_FILED = ['exclude_col', ...] 定数フィールドが定義されている場合、指定されたフィールドは UPDATE 文から除外されます
	 * 
	 * @param string    $tableName テーブル名
	 * @param array|obj $entity    エンティティ情報
	 * @param string    $where     更新条件
	 * @param array     $option    where   : array() where句用パラメータ       （未指定時は $entity が利用される）
	 *                             include : array() set句に含めるフィールド名 （未指定時は $entity の DAO_IGNORE_FILED 指定以外の全フィールド）
	 *                             exclude : array() set句から除くフィールド名
	 * @return long インサートID
	 */
	public static function update($tableName, $entity, $where, $option = array()) {
		$reflect = new ReflectionClass(get_class($entity));
		$ignore  = $reflect->hasConstant('DAO_IGNORE_FILED') ? $reflect->getConstant('DAO_IGNORE_FILED') : array() ;
		$param   = isset($option['where']) ? $option['where'] : array() ;
		$include = isset($option['include']) ? $option['include'] : array() ;
		$exclude = isset($option['exclude']) ? $option['exclude'] : array() ;
		
		$set = "";
		if(!empty($include)) {
			$clazz = get_class($entity);
			foreach ($include AS $col) {
				if(!property_exists($clazz, $col)) { continue; }
				if(in_array($col, $ignore)) { continue; }
				$set .= $col.'='.self::_convertToSql($entity->$col).', ';
			}
		} else {
			foreach ($entity AS $col => $value) {
				if(in_array($col, $exclude)) { continue; }
				if(in_array($col, $ignore)) { continue; }
				$set .= $col.'='.self::_convertToSql($value).', ';
			}
		}
		
		$set = rtrim($set, ', ');
		
		return self::query("UPDATE {$tableName} SET {$set} WHERE {$where}", empty($param) ? $entity : $param) ;
	}
	
	/**
	 * SQLテンプレートを展開します。
	 * 
	 * @param  string    $sql    SQL文
	 * @param  array|obj $params パラメータ
	 * @return string パラメータ展開後のSQL文
	 * @throws DatabaseException
	 */
	private static function _compile($sql, $params) {
		if(!is_array($params)) {
			$converted = array();
			foreach ($params AS $key => $value) {
				$converted[":{$key}"] = $value ;
			}
			$params = $converted;
		}
		
		foreach ($params AS $key => $value) {
			if(!preg_match('/:[A-Za-z0-9_]+/', $key)) {
				throw new DatabaseException("Invalid SQL query parameter key [ {$key} ], key must be pattern of /:[A-Za-z0-9_]+/.");
			}
			
			if(is_array($value)) {
				foreach ($value AS &$item) {
					$item = self::_convertToSql($item);
				}
				
				$value = join(', ', $value);
			} else {
				$value = self::_convertToSql($value);
			}
			
			$sql = preg_replace("/{$key}(?=[^a-zA-Z0-9]|$)/", "{$value}", $sql);
		}
		
		return $sql;
	}
	
	/**
	 * 値をSQL文字列用にコンバートします。
	 * 
	 * @param null|int|long|float|double|string|DateTime $value
	 * @return string
	 */
	private static function _convertToSql($value) {
		if($value === null || $value === '') {
			return 'NULL';
		}
		
		if(is_int($value) || is_long($value) || is_float($value) || is_double($value)) {
			return $value;
		}
		
		if($value instanceof DateTime || $value instanceof DateTimeImmutable) {
			return "'".$value->format("Y-m-d H:i:s")."'";
		}
		
		return "'".self::escape($value)."'";
	}
	
	/**
	 * 結果セットの値をPHPオブジェクトにコンバートします。
	 * 
	 * @param mysqli_object $value
	 * @return string|int|float|DateTime PHPオブジェクトコンバート結果
	 * @todo 整理＆実装
	 */
	private static function _convertToPhp($value, $type) {
		if($value == null || $type == null) { return $value; }
		
		switch ($type) {
			case MYSQLI_TYPE_BLOB:
			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_STRING:
			case MYSQLI_TYPE_VAR_STRING:
			case MYSQLI_TYPE_CHAR:
				return $value;
				
			case MYSQLI_TYPE_BIT:
			case MYSQLI_TYPE_TINY:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_INT24:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_LONGLONG:
				return intval($value);
				
			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
			case MYSQLI_TYPE_NEWDECIMAL:
				return floatval($value);
				
			case MYSQLI_TYPE_TIMESTAMP:
			case MYSQLI_TYPE_DATETIME:
				return $value == '0000-00-00 00:00:00' ? null : DateTime::createFromFormat('Y-m-d H:i:s', $value) ;
				
			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_NEWDATE:
				return $value == '0000-00-00' ? null : DateTime::createFromFormat('Y-m-d', $value) ;
				
			case MYSQLI_TYPE_YEAR:
				return intval($value);
				
			case MYSQLI_TYPE_TIME:
				// TODO 適切な変換が出来れば実装
				
			case MYSQLI_TYPE_INTERVAL:
				// TODO 適切な変換が出来れば実装
				
			case MYSQLI_TYPE_ENUM:
			case MYSQLI_TYPE_SET:
				// TODO 適切な変換が出来れば実装
				
			case MYSQLI_TYPE_GEOMETRY:
				// TODO 適切な変換が出来れば実装
				
			case MYSQLI_TYPE_NULL:
				// TODO 適切な変換が出来れば実装
		}
		
		return $value;
	}
	
	/**
	 * データベースエラー例外用文字列を構築します。
	 * 
	 * @param  string $method エラーが発生した箇所のメソッド名
	 * @return string エラーメッセージ
	 */
	private static function _createErrorMessage($method) {
		if(self::$_DB->connect_error) {
			return "Dao {$method} failed : ".self::$_DB->connect_errno." ". mb_convert_encoding(self::$_DB->connect_error, 'UTF-8','auto');
		}
		return "Dao {$method} failed.";
	}
}

/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 データベース関連エラー クラス（Dao付帯クラス）
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class DatabaseException extends RuntimeException {
	public function __construct ($message, $code=null, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 ページ情報 クラス（Dao付帯クラス）
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class PageInfo {
	/**
	 * 検索ヒット件数
	 * @var long
	 */
	public $hitCount;
	
	/**
	 * ページ
	 * @var int
	 */
	public $page;
	
	/**
	 * ページサイズ
	 * @var int
	 */
	public $pageSize;
	
	/**
	 * 最終ページ
	 * @var int
	 */
	public $maxPage;
	
	/**
	 * オフセット位置
	 * @var int
	 */
	public $offset;
	
	/**
	 * オフセット終端位置
	 * @var int
	 */
	public $limit;
	
	/**
	 * ページ情報を構築します。
	 * 
	 * @param int  $page
	 * @param int  $pageSize
	 * @param long $hitCount
	 */
	public function __construct ($page, $pageSize, $hitCount) {
		
		$maxPage  = floor($hitCount / $pageSize) + ($hitCount % $pageSize == 0 ? 0 : 1 );
		$maxPage  = $maxPage == 0 ? 1 : $maxPage ;
		$page     = (empty($page) || $page < 1) ? 1 : $page ;
		$page     = $maxPage < $page ? $maxPage : $page ;
		$offset   = ($page - 1) * $pageSize;
		$limit    = $offset + $pageSize - 1;
		$limit    = $hitCount < $limit ? $hitCount - 1 : $limit ;
		$limit    = $limit < 0 ? 0 : $limit ;
		
		$this->page     = $page;
		$this->pageSize = $pageSize;
		$this->hitCount = $hitCount;
		$this->maxPage  = $maxPage;
		$this->offset   = $offset;
		$this->limit    = $limit;
	}
	
	/**
	 * 検索結果が空かチェックします。
	 * 
	 * @return boolean true : 空である／false : 空でない
	 */
	public function isEmpty() {
		return $this->hitCount == 0;
	}
	
	/**
	 * ページが複数存在するかチェックします。
	 * 
	 * @return boolean true : 存在する／false : 存在しない
	 */
	public function isMultiPage() {
		return $this->maxPage != 1;
	}
	
	/**
	 * 最初のページかチェックします。
	 * 
	 * @return boolean true : 最初のページである／false : 最初のページではない
	 */
	public function isFirstPage() {
		return $this->page == 1;
	}
	
	/**
	 * 前のページが存在するチェックします。
	 * 
	 * @return boolean true : 存在する／false : 存在しない
	 */
	public function hasPrevPage() {
		return $this->page > 1 ;
	}
	
	/**
	 * 次のページが存在するかチェックします。
	 * 
	 * @return boolean true : 存在する／false : 存在しない
	 */
	public function hasNextPage() {
		return $this->page < $this->maxPage ;
	}
	
	/**
	 * 最後のページかチェックします。
	 * 
	 * @return boolean true : 最後のページである／false : 最後のページではない
	 */
	public function isLastPage() {
		return $this->page == $this->maxPage;
	}
	
	/**
	 * 近隣のページ番号リストを取得します。
	 * 
	 * @param  int $size ページ番号リストのサイズ（奇数のみ）
	 * @return array(int) 現在のページの近隣ページ番号リスト
	 * @throws InvalidArgumentException
	 */
	public function getNeighborPages($size) {
		if($size % 2 == 0) {
			throw new InvalidArgumentException('size must be odd number');
		}
		
		$start = $this->page - floor($size / 2);
		$end   = $this->page + floor($size / 2);
		if($start < 1) {
			$end   = $end - $start + 1 < $this->maxPage ? $end - $start + 1 : $this->maxPage ;
			$start = 1;
		}
		if($end > $this->maxPage) {
			$start = $start - ($end - $this->maxPage) > 1 ? $start - ($end - $this->maxPage) : 1 ;
			$end   = $this->maxPage;
		}
		
		$list = array();
		for ($i = $start ; $i <= $end ; $i++) {
			$list[] = $i;
		}
		
		return $list;
	}
}

