<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）


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
 * Dao::connect('host', 'user', 'pass', 'db_name', $port, function(Query $query){ Log::debug("SQL :: {$query}"); });
 * try {
 *     Dao::begin();
 *     $user = Dao::find('SELECT * FROM user WHERE id = :id', ['id' => $id], UserEntiry::class);
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
 * Dao::connect('host', 'user', 'pass', 'db_name', $port, function(Query $query){ Log::debug("SQL :: {$query}"); });
 * Dao::transaction(function(){
 *     $user = Dao::find('SELECT * FROM user WHERE id = :id', ['id' => $id], UserEntiry::class);
 *     // Something to do
 * });
 *
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/includes/paginate.tpl ページ送り Smarty テンプレート
 *
 * @package   SFLF
 * @version   v2.0.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Dao
{
    /**
     * データベースオブジェクト
     *
     * @var mysqli|null
     */
    private static $_DB;

    /**
     * SQLログ出力用コールバック関数
     *
     * function(Query $query) を処理する Closure が設定されます。
     * NULL の場合、SQLログ出力は行われません。
     *
     * @var callable(Query $query):void|null コールバック関数
     */
    private static $_SQL_LOG_CALLBACK = null;

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * DB接続を取得します。
     * 接続が確率されていない場合は例外を throw します。
     *
     * @return mysqli
     * @throws DatabaseException when do not connect database yet, or already closed connection.
     */
    public static function db()
    {
        if (empty(self::$_DB)) {
            throw new DatabaseException("Do not connect database yet, or already closed connection.");
        }
        return self::$_DB;
    }

    /**
     * 入力をクエリ内で区切り識別子として安全に使用できるようにフォーマットします。
     * 識別子は、テーブル名や列名などのオブジェクトです。
     *
     * @param string $identifier 識別子
     * @return string
     */
    public static function quoteIdentifier($identifier)
    {
        return strpos($identifier, '`') === false ? "`{$identifier}`" : $identifier;
    }

    /**
     * SQLログ出力用のコールバック関数を設定します。
     *
     * 例）
     * Dao::setSqlLogger(function($query){ echo("<div>{$query->emulate()}</div>"); });
     * Dao::setSqlLogger(function($query){ error_log($query->emulate(), 3, '/path/to/sql.log'); });
     * Dao::setSqlLogger(function($query){ Log::debug("SQL :: {$query->emulate()}"); });
     * Dao::setSqlLogger(function($query){ Log::debug("SQL", ['sql' => $query->sql(), 'params' => $query->params()]); });
     * Dao::setSqlLogger(function($query) use ($logger){ $logger->debug("SQL :: {$query->emulate()}"); });
     * Dao::setSqlLogger(null);
     *
     * @param callable(Query $query):void|null $callback SQLログ出力用コールバック関数 (default: null)
     * @return void
     */
    public static function setSqlLogger($callback)
    {
        self::$_SQL_LOG_CALLBACK = $callback;
    }

    /**
     * データベースに接続します。
     * ※ autocommit は off になります。
     * ※ トランザクション分離レベル は READ COMMITTED になります。
     *
     * @param string                          $host    接続ホスト
     * @param string                          $user    接続ユーザー名
     * @param string                          $pass    接続パスワード
     * @param string                          $db_name 接続データベース名
     * @param int|null                        $port    接続ポート番号 (default: null = use "mysqli.default_port" first, then 3306 if not set)
     * @param callable(Query $sql):void|null  $logger  SQLログ出力用コールバック関数 (default: null)
     * @return bool true : 新規接続時／false : 既存コネクション存在時
     * @throws DatabaseException データベース接続に失敗した場合
     */
    public static function connect($host, $user, $pass, $db_name, $port = null, $logger = null)
    {
        if (!self::$_DB) {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                if (isset($logger)) {
                    self::setSqlLogger($logger);
                }

                $port      = $port ? $port : intval(ini_get("mysqli.default_port") ?: 3306) ;
                self::$_DB = new mysqli($host, $user, $pass, $db_name, $port);
                if (self::$_DB->connect_error) {
                    throw new DatabaseException(self::_createErrorMessage(__METHOD__));
                }

                self::$_DB->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
                self::$_DB->autocommit(false);

                return true;
            } catch(mysqli_sql_exception $e) {
                throw new DatabaseException(self::_createErrorMessage(__METHOD__)." : ".$e->getCode()." ".$e->getMessage(), $e->getCode(), $e);
            }
        }

        return false;
    }

    /**
     * トランザクションを開始します。
     *
     * @return void
     * @throws DatabaseException
     */
    public static function begin()
    {
        if (!self::db()->begin_transaction()) {
            throw new DatabaseException(self::_createErrorMessage(__METHOD__));
        }
    }

    /**
     * トランザクションをロールバックします。
     *
     * @param bool $quiet true : 例外を throw しない／false : 例外を throw する (default: true)
     * @return void
     * @throws DatabaseException
     */
    public static function rollback($quiet = true)
    {
        if (!self::db()->rollback() && !$quiet) {
            throw new DatabaseException(self::_createErrorMessage(__METHOD__));
        }
    }

    /**
     * トランザクションをコミットします。
     *
     * @return void
     * @throws DatabaseException
     */
    public static function commit()
    {
        if (!self::db()->commit()) {
            throw new DatabaseException(self::_createErrorMessage(__METHOD__));
        }
    }

    /**
     * DB接続を閉じます
     * ※本メソッドは例外を無視します
     *
     * @return void
     */
    public static function close()
    {
        try {
            static::db()->close();
            self::$_DB = null;
        } catch (Exception $e) {
            // 何もしない
        }
    }

    /**
     * トランザクションを開始／ロールバック／コミットします。
     *
     * @param callable():void $callback ひとまとまりの処理
     * @return void
     */
    public static function transaction($callback)
    {
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
     * @param string|object $val
     * @return string エスケープ文字列
     */
    public static function escape($val)
    {
        if (is_object($val)) {
            $val = method_exists($val, '__toString') ? $val->__toString() : null ;
        }
        return self::db()->real_escape_string($val);
    }

    /**
     * 直近で挿入した自動採番のID番号を取得します。
     *
     * @return int|string インサートID
     */
    public static function getInsertId()
    {
        return self::db()->insert_id;
    }

    /**
     * 対象のテーブルを Truncate します。
     *
     * @param string $table_name 対象テーブル名
     * @return void
     */
    public static function truncate(string $table_name)
    {
        $table_name = static::quoteIdentifier($table_name);
        static::queryAffect("TRUNCATE {$table_name}");
    }

    /**
     * AUTO_INCREMENT の値を更新する。
     *
     * @param string $table_name     対象テーブル名
     * @param int    $auto_increment 設定する AUTO_INCREMENT の値
     * @return void
     */
    public static function setAutoIncrement(string $table_name, int $auto_increment)
    {
        $table_name = static::quoteIdentifier($table_name);
        static::queryAffect("ALTER TABLE {$table_name} AUTO_INCREMENT = {$auto_increment}");
    }

    /**
     * 指定のSQLを実行します。
     * ※戻り値は mysqli_query() の戻り値(falseを除く)となります。
     *
     * @param string                      $sql    SQL文
     * @param array<string, mixed>|object $params パラメータ (default: [])
     * @return mysqli_result|int 結果セット: SELECT/SHOW/DESCRIBE/EXPLAIN 発行時, 更新件数: その他クエリ発行時
     * @throws DatabaseException SQL実行エラー時
     */
    public static function query($sql, $params = [])
    {
        $query = self::compile($sql, $params);
        if (self::$_SQL_LOG_CALLBACK) {
            $log = self::$_SQL_LOG_CALLBACK;
            $log($query);
        }

        $stmt = null;
        try {
            $stmt = self::db()->prepare($query->sql());
            if ($stmt === false) {
                throw new DatabaseException("Execute query failed : ".self::db()->errno." ".self::db()->error."\n--- [SQL] ---\n{$sql}\n-------------\n", self::db()->errno);
            }
            if (!empty($query->params())) {
                $stmt->bind_param($query->bindParamTypes(), ...$query->params());
            }
            if ($stmt->execute() === false) {
                throw new DatabaseException("Execute query failed : ".self::db()->errno." ".self::db()->error."\n--- [SQL] ---\n{$sql}\n-------------\n", self::db()->errno);
            }

            $rs = $stmt->get_result();
            if ($rs === false) {
                if (static::db()->errno === 0) {
                    return intval($stmt->affected_rows);
                }
                throw new DatabaseException("Execute query failed : ".self::db()->errno." ".self::db()->error."\n--- [SQL] ---\n{$sql}\n-------------\n", self::db()->errno);
            }
            return $rs;
        } catch(mysqli_sql_exception $e) {
            throw new DatabaseException("Execute query failed : ".$e->getCode()." ".$e->getMessage()."\n--- [SQL] ---\n{$sql}\n-------------\n", $e->getCode(), $e);
        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }

    /**
     * 指定のSQL(SELECT/SHOW/DESCRIBE/EXPLAIN)を実行します。
     * ※戻り値は結果セットになります。
     *
     * @param string                      $sql    SQL文
     * @param array<string, mixed>|object $params パラメータ (default: [])
     * @return mysqli_result 結果セット
     * @throws DatabaseException SQL実行エラー時、又は SELECT/SHOW/DESCRIBE/EXPLAIN 以外のSQL指定時
     */
    public static function querySelect($sql, $params = [])
    {
        if (is_int($rs = self::query($sql, $params))) {
            throw new DatabaseException("Invalid SQL query given, querySelect() MUST a SELECT/SHOW/DESCRIBE/EXPLAIN query.");
        }
        return $rs;
    }

    /**
     * 指定のSQL(SELECT/SHOW/DESCRIBE/EXPLAIN 以外)を実行します。
     * ※戻り値は更新件数になります。
     *
     * @param string                      $sql    SQL文
     * @param array<string, mixed>|object $params パラメータ (default: [])
     * @return int 更新件数
     * @throws DatabaseException SQL実行エラー時、又は SELECT/SHOW/DESCRIBE/EXPLAIN のSQL指定時
     */
    public static function queryAffect($sql, $params = [])
    {
        if (is_int($affected_rows = self::query($sql, $params))) {
            return $affected_rows;
        }
        throw new DatabaseException("Invalid SQL query given, queryAffect() MUST not be a SELECT/SHOW/DESCRIBE/EXPLAIN queries.");
    }

    /**
     * 指定のSQLを実行し、結果の各行に callback 関数を適用します。
     * ※大容量の CSV データ出力などメモリ使用量を押さえたい場合などに利用できます。
     * ※もう一歩踏み込んだメモリ使用量の定量化を行い場合は chunk() をご利用下さい。
     *
     * @template T
     * @param callable(int $i, T $entity):void $callback コールバック関数
     * @param string                           $sql      SQL
     * @param array<string, mixed>|object      $params   パラメータ  (default: [])
     * @param class-string<T>                  $clazz    エンティティクラス  (default: stdClass)
     * @return void
     * @throws DatabaseException
     */
    public static function each(callable $callback, $sql, $params = [], $clazz = 'stdClass')
    {
        $rs = self::querySelect($sql, $params);
        if ($rs->num_rows === 0) {
            return;
        }

        $types = [];
        foreach ($rs->fetch_fields() as $meta) {
            $types[$meta->name] = $meta->type;
        }

        foreach ($rs as $i => $row) {
            $entity = new $clazz();
            foreach ($row as $col => $val) {
                $entity->$col = self::_convertToPhp($val, $types[$col]) ;
            }
            $callback($i, $entity);
        }

        return;
    }

    /**
     * 指定のSQLを実行し、結果の各行に callback 関数を適用します。
     * ※大容量の CSV データ出力などメモリ使用量を押さえたい場合などに利用できます。
     * ※指定の $chunk 単位でデータを分割してDBより取得するため each よりメモリ使用量を抑えられます。
     * ※LIMIT OFFSET を使用した分割データ取得となるため、指定のSQLはデータの並び順が一意となるように指定されていなければなりません。
     *
     * @template T
     * @param callable(int $i, T $entity):void                                      $callback コールバック関数
     * @param callable(array<string, mixed>|object &$params, T|null $cursor):string $sqler    ページングSQL生成関数
     * @param array<string, mixed>|object                                           $params   パラメータ (default: [])
     * @param class-string<T>                                                       $clazz    エンティティクラス (default: stdClass)
     * @param int                                                                   $chunk    一度に取得する最大件数 (default: 1000)
     * @return void
     * @throws DatabaseException
     */
    public static function chunk(callable $callback, callable $sqler, $params = [], $clazz = 'stdClass', int $chunk = 1000)
    {
        $i      = 0;
        $types  = [];
        $cursor = null;
        while (true) {
            $sql = $sqler($params, $cursor);
            $sql = preg_match('/LIMIT/i', $sql) ? "SELECT * FROM ({$sql}) AS T" : $sql ;
            $rs  = self::querySelect("{$sql} LIMIT {$chunk}", $params);
            if ($rs->num_rows === 0) {
                return;
            }

            if (empty($types)) {
                foreach ($rs->fetch_fields() as $meta) {
                    $types[$meta->name] = $meta->type;
                }
            }

            foreach ($rs as $j => $row) {
                $entity = new $clazz();
                foreach ($row as $col => $val) {
                    $entity->$col = self::_convertToPhp($val, $types[$col]) ;
                }
                $callback(($chunk * $i) + $j, $entity);
                $cursor = $entity;
            }

            $rs->free();
            $i++;
        }
    }

    /**
     * 指定のSQLを実行し、結果〔N行M列〕を取得します。
     *
     * @template T
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ (default: [])
     * @param  class-string<T>             $clazz  結果セットのマッピング型 (default: stdClass)
     * @return T[] 検索結果
     * @throws DatabaseException
     */
    public static function select($sql, $params = [], $clazz = 'stdClass')
    {
        $rs = self::querySelect($sql, $params);
        if ($rs->num_rows === 0) {
            return [];
        }

        $types = [];
        foreach ($rs->fetch_fields() as $meta) {
            $types[$meta->name] = $meta->type;
        }

        $list = [];
        foreach ($rs as $row) {
            $entity = new $clazz();
            foreach ($row as $col => $val) {
                $entity->$col = self::_convertToPhp($val, $types[$col]) ;
            }
            $list[] = $entity;
        }

        return $list;
    }

    /**
     * 指定のSQLを実行し、結果〔N行1列〕を配列で取得します。
     *
     * @param  string                      $col    列名
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ (default: [])
     * @return mixed[] 検索結果から指定列のみを抽出したリスト
     * @throws DatabaseException
     */
    public static function lists($col, $sql, $params = [])
    {
        $rs = self::querySelect($sql, $params);
        if ($rs->num_rows === 0) {
            return [];
        }

        $types = [];
        foreach ($rs->fetch_fields() as $meta) {
            $types[$meta->name] = $meta->type;
        }

        $list = [];
        foreach ($rs as $row) {
            $list[] = self::_convertToPhp($row[$col], $types[$col]);
        }

        return $list;
    }

    /**
     * 指定のSQLを実行し、結果〔1行M列〕を1件取得します。
     * ※戻り値は $clazz or null になります。
     *
     * @template T
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ (default: [])
     * @param  class-string<T>             $clazz  結果セットのマッピング型 (default: stdClass)
     * @return T|null $clazz で指定した検索結果
     * @throws DatabaseException
     */
    public static function find($sql, $params = [], $clazz = 'stdClass')
    {
        $list = self::select($sql, $params, $clazz);
        if (empty($list)) {
            return null;
        }
        return $list[0];
    }

    /**
     * 指定のSQLを実行し、結果〔1行1列〕を取得します。
     *
     * @param  string                      $sql     集約SQL文
     * @param  array<string, mixed>|object $params  パラメータ (default: [])
     * @param  mixed|null                  $default デフォルト値 (default: null)
     * @return mixed|null 集約結果
     *
     * @see Dao::_convertToPhp()
     */
    public static function get($sql, $params = [], $default = null)
    {
        $rs   = self::querySelect($sql, $params);
        $meta = $rs->fetch_field_direct(0);
        $row  = $rs->fetch_row();
        return $row ? self::_convertToPhp($row[0], $meta->type ?? null) : $default ;
    }

    /**
     * 対象SQLの結果が存在するかを判定します。
     * ※戻り値は bool になります。
     *
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ (default: [])
     * @return bool true : 存在する／false : 存在しない
     * @throws DatabaseException
     */
    public static function exists($sql, $params = [])
    {
        $row = self::find("{$sql} LIMIT 1", $params);
        return !empty($row);
    }

    /**
     * 対象SQLの検索結果件数を取得します。
     * ※戻り値は int になります。
     *
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ (default: [])
     * @return int 検索結果件数
     * @throws DatabaseException
     */
    public static function count($sql, $params = [])
    {
        return self::get("SELECT count(*) FROM ({$sql}) AS T", $params);
    }

    /**
     * 指定のSQLを実行し、結果を複数件取得します。
     * ※本処理はページング処理を行った検索を提供します。
     *
     * list($pi, $rs) = Dao::paginate(1, 25, "SELECT * FROM ...", [':status' => 1], UserEntity::class);
     *
     * @template T
     * @param int                            $page                取得ページ
     * @param int                            $page_size           1ページのサイズ(データ件数)
     * @param string                         $sql                 SQL文
     * @param array<string, mixed>|object    $params              パラメータ (default: [])
     * @param class-string<T>                $clazz               結果セットのマッピング型 (default: stdClass)
     * @param string|null                    $optimized_count_sql ヒット件数検索用の最適化されたカウント用SQL (default: null = self::count() の利用)
     * @return array{0: PageInfo, 1: T[]} ページ情報と検索結果
     * @throws DatabaseException
     */
    public static function paginate($page, $page_size, $sql, $params = [], $clazz = 'stdClass', $optimized_count_sql = null)
    {
        $hit_count = empty($optimized_count_sql) ? self::count($sql, $params) : self::get($optimized_count_sql, $params) ;
        $pi        = new PageInfo($page, $page_size, $hit_count);
        $rs        = self::select("$sql LIMIT {$pi->offset}, {$pi->page_size}", $params, $clazz);
        return [$pi, $rs];
    }

    /**
     * 対象のテーブルにデータを挿入します。
     * ※戻り値は insert_id になります。
     * ※エンティティに const DAO_IGNORE_FILED = ['exclude_col', ...] 定数フィールドが定義されている場合、指定されたフィールドは INSERT 文から除外されます
     *
     * @param string                      $table_name テーブル名
     * @param array<string, mixed>|object $entity     エンティティ情報
     * @return int|string インサートID
     * @throws DatabaseException
     */
    public static function insert($table_name, $entity)
    {
        $ignore = [];
        if (!is_array($entity)) {
            $reflect = new ReflectionClass(get_class($entity));
            $ignore  = $reflect->hasConstant('DAO_IGNORE_FILED') ? $reflect->getConstant('DAO_IGNORE_FILED') : [] ;
            $entity  = get_object_vars($entity) ;
        }
        $cols   = [];
        $values = [];
        foreach ($entity as $col => $value) {
            if (in_array($col, $ignore)) {
                continue;
            }
            $cols[]   = $col;
            $values[] = $value;
        }

        self::query("INSERT INTO {$table_name} (".join(',', $cols).") VALUES (:values)", ['values' => $values]) ;
        return self::getInsertId();
    }

    /**
     * 対象のテーブルのデータを更新します。
     * ※エンティティに const DAO_IGNORE_FILED = ['exclude_col', ...] 定数フィールドが定義されている場合、指定されたフィールドは UPDATE 文から除外されます
     *
     * @param string                                                                      $table_name テーブル名
     * @param array<string, mixed>|object                                                 $entity     エンティティ情報
     * @param string                                                                      $where      更新条件SQL
     * @param array{where?: array<string, mixed>, include?: string[], exclude?: string[]} $option     更新オプション (default: [])
     *     - where   : where句用パラメータ （未指定時は $entity が利用される）
     *     - include : SET句に含めるフィールド名 （未指定時は $entity の DAO_IGNORE_FILED 指定以外の全フィールド）
     *     - exclude : SET句から除くフィールド名
     * @return int 更新件数
     */
    public static function update($table_name, $entity, $where, $option = [])
    {
        $ignore = [];
        if (!is_array($entity)) {
            $reflect = new ReflectionClass(get_class($entity));
            $ignore  = $reflect->hasConstant('DAO_IGNORE_FILED') ? $reflect->getConstant('DAO_IGNORE_FILED') : [] ;
            $entity  = get_object_vars($entity);
        }
        $param   = isset($option['where']) ? $option['where'] : $entity ;
        $include = isset($option['include']) ? $option['include'] : [] ;
        $exclude = isset($option['exclude']) ? $option['exclude'] : [] ;

        $set = "";
        if (!empty($include)) {
            foreach ($include as $col) {
                if (!array_key_exists($col, $entity)) {
                    continue;
                }
                if (in_array($col, $ignore)) {
                    continue;
                }
                $set .= "{$col}=:_{$col}, ";
                $param["_{$col}"] = $entity[$col];
            }
        } else {
            foreach ($entity as $col => $value) {
                if (in_array($col, $exclude)) {
                    continue;
                }
                if (in_array($col, $ignore)) {
                    continue;
                }
                $set .= "{$col}=:_{$col}, ";
                $param["_{$col}"] = $value;
            }
        }

        $set = rtrim($set, ', ');

        return self::queryAffect("UPDATE {$table_name} SET {$set} WHERE {$where}", $param) ;
    }

    /**
     * SQLテンプレートを展開します。
     *
     * @param  string                      $sql    SQL文
     * @param  array<string, mixed>|object $params パラメータ
     * @return Query                       パラメータ展開後のSQLクエリ
     * @throws DatabaseException
     */
    public static function compile($sql, $params)
    {
        $params = is_array($params) ? $params : get_object_vars($params) ;

        // --------------------------------------------------------------------
        // パラメータキー形式チェック
        // --------------------------------------------------------------------
        $converted = [];
        foreach ($params as $key => $value) {
            // @todo 下位互換処理（将来的には削除する）
            if (self::_startWith($key, ':')) {
                trigger_error("Deprecated SQL query parameter key [ {$key} ] given, prease remove ':' from key. It will be an error in the future.", E_USER_DEPRECATED);
                $key = trim($key, ':');
            }
            $converted[$key] = $value ;

            if (!preg_match('/[a-zA-Z0-9_]+/', $key)) {
                throw new DatabaseException("Invalid SQL query parameter key [ {$key} ], the key must be pattern of /[a-zA-Z0-9_]+/.");
            }
        }
        $params = $converted;

        // ---------------------------------------------------------------------
        // 名前付きプレースホルダーの解決
        // ---------------------------------------------------------------------
        $real_params = [];
        $count       = 0;
        $sql         = preg_replace_callback(
            '/(:[A-Za-z0-9_]+)(?=[^a-zA-Z0-9_]|$)/',
            function ($matches) use ($params, &$real_params, &$count) {
                $key = trim($matches[1], ':');
                if (!array_key_exists($key, $params)) {
                    return $matches[1];
                }
                $val    = $params[$key];
                $holder = "";
                if (is_array($val)) {
                    foreach ($val as $v) {
                        $real_params[] = $v;
                        $holder .= "?/*{$count}*/, ";
                        $count++;
                    }
                    $holder = trim($holder, ", ");
                } else {
                    $real_params[] = $val;
                    $holder .= "?/*{$count}*/";
                    $count++;
                }
                return $holder;
            },
            $sql
        );

        if ($sql === null) {
            throw new DatabaseException("Failed to compile SQL.\n- SQL  : {$sql}\n- PRAMS: ".json_encode($params));
        }

        return new Query($sql, $real_params);
    }

    /**
     * 指定の文字列 [$haystack] が指定の文字列 [$needle] で始まるか検査します。
     *
     * @param string  $haystack 検査対象文字列
     * @param string  $needle   被検査文字列
     * @return bool true : 始まる／false : 始まらない
     */
    private static function _startWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * 結果セットの値をPHPオブジェクトにコンバートします。
     *
     * @param mixed|null $value 値
     * @param int|null   $type  MySQL型情報
     * @return mixed|null PHPオブジェクトコンバート結果
     * @todo 整理＆実装
     */
    private static function _convertToPhp($value, $type)
    {
        if ($value == null || $type == null) {
            return $value;
        }

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
                return $value == '0000-00-00' ? null : DateTime::createFromFormat('!Y-m-d', $value) ;

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
    private static function _createErrorMessage($method)
    {
        if (!empty(self::$_DB) && self::$_DB->connect_error) {
            return "{$method} failed : ".self::$_DB->connect_errno." ". mb_convert_encoding(self::$_DB->connect_error, 'UTF-8', 'auto');
        }
        return "{$method} failed.";
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
class DatabaseException extends RuntimeException
{
    /**
     * DB例外を構築します。
     *
     * @param string         $message   エラーメッセージ
     * @param int            $code      エラーコード (default: 0)
     * @param Throwable|null $previous  原因例外 (default: null)
     * @return DatabaseException
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 SQLクエリ クラス（Dao付帯クラス）
 *
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2023 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Query
{
    /**
     * Full or partial SQL sentence.
     *
     * @var string
     */
    protected $sql = '';

    /**
     * Full or partial SQL parameters
     *
     * @var mixed[]
     */
    protected $params = [];

    /**
     * Create full or partial SQL.
     *
     * @param string $sql
     * @param mixed[] $params (default: [])
     * @return Query
     */
    public function __construct($sql, $params = [])
    {
        $this->sql    = $sql;
        $this->params = array_map(function ($v) {
            if ($v === '') {
                return null;
            }
            if ($v instanceof DateTime || $v instanceof DateTimeImmutable) {
                return $v->format("Y-m-d H:i:s");
            }
            return $v;
        }, $params);
    }

    /**
     * Get full or partial SQL sentence.
     *
     * @return string
     */
    public function sql()
    {
        return $this->sql;
    }

    /**
     * Get full or partial SQL parameters
     *
     * @return mixed[]
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * ステーメントにバインドする際のパラメータタイプ文字列を取得します。
     *
     * @return string
     */
    public function bindParamTypes()
    {
        $types = '';
        foreach ($this->params as $param) {
            if ($param === null || $param === '') {
                $types .= 's';
                continue;
            }

            if (is_int($param)) {
                $types .= 'i';
                continue;
            }

            if (is_float($param)) {
                $types .= 'd';
                continue;
            }

            if (is_bool($param)) {
                $types .= 'i';
                continue;
            }

            $types .= 's';
            continue;
        }


        return $types;
    }

    /**
     * Emulate SQL for logging and exception message.
     * You should not use this method other than to emulate sql for log output.
     *
     * @return string
     */
    public function emulate() : string
    {
        $sql = $this->sql;
        foreach ($this->params as $i => $value) {
            $sql = str_replace("?/*{$i}*/", $this->convertToSql($value), $sql);
        }

        return "/* Emulated SQL */ ".$sql;
    }

    /**
     * 値をSQL文字列用にコンバートします。
     *
     * @param mixed $value PHPの値
     * @return string
     */
    protected function convertToSql($value)
    {
        if ($value === null || $value === '') {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return "{$value}";
        }

        if (is_bool($value)) {
            return $value ? '1' : '0' ;
        }

        if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
            return "'".$value->format("Y-m-d H:i:s")."'";
        }

        return "'".Dao::escape($value)."'";
    }

    /**
     * SQLクエリを文字列化します。
     *
     * @return string
     */
    public function __toString()
    {
        return $this->emulate();
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
class PageInfo
{
    /**
     * 検索ヒット件数
     * @var int
     */
    public $hit_count;

    /**
     * ページ
     * @var int
     */
    public $page;

    /**
     * ページサイズ
     * @var int
     */
    public $page_size;

    /**
     * 最終ページ
     * @var int
     */
    public $max_page;

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
     * @param int $page      ページ
     * @param int $page_size ページサイズ
     * @param int $hit_count ヒット件数
     */
    public function __construct($page, $page_size, $hit_count)
    {
        $max_page = (int) floor($hit_count / $page_size) + ($hit_count % $page_size == 0 ? 0 : 1);
        $max_page = $max_page == 0 ? 1 : $max_page ;
        $page     = (empty($page) || $page < 1) ? 1 : $page ;
        $page     = $max_page < $page ? $max_page : $page ;
        $offset   = ($page - 1) * $page_size;
        $limit    = $offset + $page_size - 1;
        $limit    = $hit_count < $limit ? $hit_count - 1 : $limit ;
        $limit    = $limit < 0 ? 0 : $limit ;

        $this->page      = $page;
        $this->page_size = $page_size;
        $this->hit_count = $hit_count;
        $this->max_page  = $max_page;
        $this->offset    = $offset;
        $this->limit     = $limit;
    }

    /**
     * 検索結果が空かチェックします。
     *
     * @return bool true : 空である／false : 空でない
     */
    public function isEmpty()
    {
        return $this->hit_count == 0;
    }

    /**
     * ページが複数存在するかチェックします。
     *
     * @return bool true : 存在する／false : 存在しない
     */
    public function isMultiPage()
    {
        return $this->max_page != 1;
    }

    /**
     * 最初のページかチェックします。
     *
     * @return bool true : 最初のページである／false : 最初のページではない
     */
    public function isFirstPage()
    {
        return $this->page == 1;
    }

    /**
     * 前のページが存在するチェックします。
     *
     * @return bool true : 存在する／false : 存在しない
     */
    public function hasPrevPage()
    {
        return $this->page > 1 ;
    }

    /**
     * 次のページが存在するかチェックします。
     *
     * @return bool true : 存在する／false : 存在しない
     */
    public function hasNextPage()
    {
        return $this->page < $this->max_page ;
    }

    /**
     * 最後のページかチェックします。
     *
     * @return bool true : 最後のページである／false : 最後のページではない
     */
    public function isLastPage()
    {
        return $this->page == $this->max_page;
    }

    /**
     * 近隣のページ番号リストを取得します。
     *
     * @param  int $size ページ番号リストのサイズ（奇数のみ）
     * @return int[] 現在のページの近隣ページ番号リスト
     * @throws InvalidArgumentException
     */
    public function getNeighborPages($size)
    {
        if ($size % 2 == 0) {
            throw new InvalidArgumentException('size must be odd number');
        }

        $start = intval($this->page - floor($size / 2));
        $end   = intval($this->page + floor($size / 2));
        if ($start < 1) {
            $end   = $end - $start + 1 < $this->max_page ? $end - $start + 1 : $this->max_page ;
            $start = 1;
        }
        if ($end > $this->max_page) {
            $start = $start - ($end - $this->max_page) > 1 ? $start - ($end - $this->max_page) : 1 ;
            $end   = $this->max_page;
        }

        $list = [];
        for ($i = $start ; $i <= $end ; $i++) {
            $list[] = $i;
        }

        return $list;
    }
}
