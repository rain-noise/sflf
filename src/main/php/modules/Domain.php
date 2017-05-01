<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 ドメイン クラス
 * 
 * 【使い方】
 * require_once "/path/to/Domain.php"; // or use AutoLoader
 * 
 * // 基本系
 * class Sex extends Domain {
 *     public static $MALE;
 *     public static $FEMALE;
 *     
 *     public static function init() {
 *         self::$MALE   = new Sex(1, '男性');
 *         self::$FEMALE = new Sex(2, '女性');
 *     }
 * }
 * Sex::init();
 * 
 * // フィールド拡張系
 * class UserSortOrder extends Domain {
 *     public static $NAME_ASC;
 *     public static $NAME_DESC;
 *     public static $MAIL_ADDRESS_ASC;
 *     public static $MAIL_ADDRESS_DESC;
 *     
 *     public static function init() {
 *         self::$NAME_ASC          = new UserSortOrder(1, '名前[↓]', 'name ASC');
 *         self::$NAME_DESC         = new UserSortOrder(2, '名前[↑]', 'name DESC');
 *         self::$MAIL_ADDRESS_ASC  = new UserSortOrder(3, 'メールアドレス[↓]', 'mail_address ASC');
 *         self::$MAIL_ADDRESS_DESC = new UserSortOrder(4, 'メールアドレス[↑]', 'mail_address DESC');
 *     }
 *     
 *     public $sql;
 * 
 *     protected function __construct($value, $label, $sql) {
 *         parent::__construct($value, $label);
 *         $this->sql = $sql;
 *     }
 * }
 * UserSortOrder::init();
 * 
 * // 定数不要系
 * class Sex extends Domain {
 *     protected static function generate() {
 *         return array(
 *              new Sex(1, '男性')
 *             ,new Sex(2, '女性')
 *         );
 *     }
 * }
 * 
 * // DBマスタ参照系
 * class Prefecture extends Domain {
 *     public function __construct() {
 *         parent::__construct(null, null);
 *     }
 *     
 *     protected static function generate() {
 *         return Dao::select('SELECT prefecture_id AS value, name AS label FROM prefecture ORDER BY prefecture_id ASC', array(), Prefecture::class);
 *     }
 * }
 * 
 * // 数値範囲系
 * class Month extends RangeDomain {
 *     public static function start() { return  1; }
 *     public static function step()  { return  1; }
 *     public static function end()   { return 12; }
 *     public static function format($i){ return sprintf("%02s月",$i); }
 * }
 * 
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/domain/*.php 汎用ドメイン
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.domains.php ドメイン整形出力用 Smarty タグ
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
abstract class Domain {
	
	public $value;
	public $label;
	
	private static $DOMAIN_LIST_CACHE = array();
	
	/**
	 * ドメイン生成
	 */
	protected function __construct($value, $label) {
		$this->value = $value;
		$this->label = $label;
	}

	/**
	 * ドメインの値を検証します。
	 * @param boolean true: 一致 / false: 不一致
	 */
	public function is($value) {
		return $this->value == $value;
	}
	
	/**
	 * ドメインが指定の配列内に含まれるか検証します。
	 * @param boolean true: 含まれる / false: 含まれない
	 */
	public function in(array $array) {
		return in_array($this->value, $array);
	}
	
	/**
	 * ドメインを文字列します。
	 */
	public function __toString() {
		return $this->label;
	}
	
	/**
	 * ドメインの一覧を生成します。
	 */
	protected static function generate() {
		$rc   = new ReflectionClass(get_called_class());
		$list = array();
		foreach ($rc->getStaticProperties() AS $domain) {
			if(empty($domain)) {
				throw new Exception('Domain field not initialzed.');
			}
			$list[] = $domain;
		}
		return $list;
	}
	
	/**
	 * ドメイン定数の一覧 array(Domain) を取得します。
	 * ※ドメインクラス名単位で generate されたドメイン一覧をキャッシュし、再利用します。
	 */
	public static function lists() {
		$clazz = get_called_class();
		if(isset(self::$DOMAIN_LIST_CACHE[$clazz])){ return self::$DOMAIN_LIST_CACHE[$clazz]; }
		self::$DOMAIN_LIST_CACHE[$clazz] = static::generate();
		return self::$DOMAIN_LIST_CACHE[$clazz];
	}
	
	/**
	 * $domain->value ⇒ $domain の連想配列を取得します。
	 */
	public static function maps() {
		$maps = array();
		
		foreach (self::lists() AS $domain) {
			$maps[$domain->value] = $domain;
		}
		
		return $maps;
	}
	
	/**
	 * 対象の値を持つドメインを取得します。
	 */
	public static function valueOf($value) {
		$maps = self::maps();
		return isset($maps[$value]) ? $maps[$value] : null ;
	}
	
	/**
	 * 値の一覧を配列で取得します。
	 */
	public static function values($matcher=null) {
		return self::listOf('value', $matcher);
	}
	
	/**
	 * ラベルの一覧を配列で取得します。
	 */
	public static function labels($matcher=null) {
		return self::listOf('label', $matcher);
	}
	
	/**
	 * 指定フィールドの一覧を配列で取得します。
	 * @param string $name
	 */
	public static function listOf($name, $matcher=null) {
		$values = array();
		foreach (self::lists() AS $domain) {
			if($matcher == null || $matcher($domain)) {
				$values[] = $domain->$name;
			}
		}
		return $values;
	}
}

/**
 * 範囲ドメイン
*/
abstract class RangeDomain extends Domain
{
	/**
	 * 開始の数値を指定します
	 */
	abstract public static function start();
	
	/**
	 * 変動値を指定します
	 */
	abstract public static function step();
	
	/**
	 * 終了の数値を指定します
	 */
	abstract public static function end();
	
	/**
	 * ラベルを value の数値でフォーマットして返します。
	 * @return string
	 */
	abstract public static function format($i);
	
	/**
	 * ドメインの一覧を生成します。
	 */
	protected static function generate() {
		$list = array();
		for ($i = static::start() ; $i <= static::end() ; $i += static::step()) {
			$list[] = new static($i, static::format($i));
		}
		return $list;
	}
}
