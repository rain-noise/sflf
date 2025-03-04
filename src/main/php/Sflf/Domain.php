<?php
// namespace App\Core; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

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
 * // 定数不要系
 * class Sex extends Domain {
 *     protected static function generate() {
 *         return array(
 *              new Sex('M', '男性')
 *             ,new Sex('F', '女性')
 *         );
 *     }
 * }
 *
 * // メソッド拡張系
 * class Sex extends Domain {
 *     protected static function generate() {
 *         return array(
 *              new Sex(1, '男性')
 *             ,new Sex(2, '女性')
 *         );
 *     }
 *
 *     public function isMale()  { return $this->value === 1; }
 *     public function isFeale() { return $this->value === 2; }
 * }
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
 * // 匿名クラス拡張系
 * abstract class JobOfferCsvFormat extends Domain {
 *     protected static function generate() {
 *         return [
 *              new class(1, '求人サイトA') extends JobOfferCsvFormat {
 *                  public function convert($row){
 *                      $form = new UserForm();
 *                      (snip)
 *                      $form->name = "{$row[0]} {$row[1]}"; // combine first name and last name column.
 *                      (snip)
 *                      return $form;
 *                  }
 *              },
 *              new class(2, '求人サイトB') extends JobOfferCsvFormat {
 *                  public function convert($row){
 *                      $form = new UserForm();
 *                      (snip)
 *                      $form->name = $row[5]; // just use full name column.
 *                      (snip)
 *                      return $form;
 *                  }
 *              }
 *         ];
 *     }
 *
 *     public abstract function convert($row);
 * }
 *
 * // DBマスタ参照系
 * class Prefecture extends Domain {
 *     public function __construct() {
 *         parent::__construct(null, null);
 *     }
 *
 *     protected static function generate() {
 *         return Dao::select('SELECT prefecture_id AS value, name AS label FROM prefecture ORDER BY prefecture_id ASC', [], Prefecture::class);
 *     }
 * }
 *
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/domain/*.php 汎用ドメイン
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.domains.php ドメイン整形出力用 Smarty タグ
 *
 * @package   SFLF
 * @version   v4.0.0
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
abstract class Domain
{
    /**
     * 値
     *
     * @var mixed
     */
    public $value;

    /**
     * ラベル
     *
     * @var string|null
     */
    public $label;

    /**
     * ドメインリストのキャッシュ
     *
     * @var array<class-string<static>, static[]>
     */
    private static $DOMAIN_LIST_CACHE = [];

    /**
     * ドメインマップのキャッシュ
     *
     * @var array<array-key, static[]>
     */
    private static $DOMAIN_MAP_CACHE = [];

    /**
     * ドメイン生成
     *
     * @param mixed|null  $value 値
     * @param string|null $label ラベル
     * @return static
     */
    protected function __construct($value, $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    /**
     * ドメインの値を検証します。
     *
     * @param mixed $value 値
     * @return bool true: 一致 / false: 不一致
     */
    public function equals($value)
    {
        return $this->value == $value;
    }

    /**
     * ドメインが指定の配列内に含まれるか検証します。
     *
     * @param mixed[] $array 値のリスト
     * @return bool true: 含まれる / false: 含まれない
     */
    public function in(array $array)
    {
        return in_array($this->value, $array);
    }

    /**
     * ドメインを文字列します。
     *
     * @return string ドメイン文字列
     */
    public function __toString()
    {
        return (string)$this->label;
    }

    /**
     * ドメインの一覧を生成します。
     *
     * @return static[] 生成されたドメインの一覧
     */
    protected static function generate()
    {
        $rc   = new \ReflectionClass(get_called_class());
        $list = [];
        foreach ($rc->getStaticProperties() as $domain) {
            if (empty($domain)) {
                throw new \Exception('Domain field not initialzed.');
            }
            $list[] = $domain;
        }
        return $list;
    }

    /**
     * ドメイン定数の一覧 array(Domain) を取得します。
     *
     * @param null|callable(static $domain):bool $matcher リストに含めるドメインの条件 (default: null)
     *
     * @return static[]
     */
    public static function lists($matcher = null)
    {
        if ($matcher == null) {
            return static::listAll();
        }

        $lists = [];
        foreach (self::listAll() as $domain) {
            if ($matcher == null || $matcher($domain)) {
                $lists[] = $domain;
            }
        }
        return $lists;
    }

    /**
     * ドメイン定数の全件一覧を取得します。
     * ※ドメインクラス名単位で generate されたドメイン一覧をキャッシュし、再利用します。
     *
     * @return static[]
     */
    protected static function listAll()
    {
        $clazz = get_called_class();
        if (isset(self::$DOMAIN_LIST_CACHE[$clazz])) {
            return self::$DOMAIN_LIST_CACHE[$clazz];
        }
        self::$DOMAIN_LIST_CACHE[$clazz] = static::generate();
        return self::$DOMAIN_LIST_CACHE[$clazz];
    }

    /**
     * $domain->$field ⇒ $domain の連想配列を取得します。
     * ※同じ値を持つドメインが存在する場合、 Domain::lists() の順序で後勝ちとなります
     *
     * @param string $field 変換元にするフィールド名 (default: value)
     * @param null|callable(static $domain):bool $matcher リストに含めるドメインの条件 (default: null)
     * @return array<array-key, static>
     */
    public static function maps($field = 'value', $matcher = null)
    {
        $key = get_called_class()."@".$field;
        if (isset(self::$DOMAIN_MAP_CACHE[$key])) {
            return self::$DOMAIN_MAP_CACHE[$key];
        }

        $maps = [];
        foreach (self::lists($matcher) as $domain) {
            $maps[$domain->$field] = $domain;
        }
        self::$DOMAIN_MAP_CACHE[$key] = $maps;

        return $maps;
    }

    /**
     * 対象の値を持つドメインを取得します。
     * ※同じ値を持つドメインが存在する場合、 Domain::lists() の順序で後勝ちとなります
     *
     * @param mixed $value 値
     * @return static|null 指定の値を持つドメイン
     */
    public static function valueOf($value)
    {
        return self::fieldOf('value', $value);
    }

    /**
     * 対象のラベルを持つドメインを取得します。
     * ※同じ値を持つドメインが存在する場合、 Domain::lists() の順序で後勝ちとなります
     *
     * @param string  $label ラベル名
     * @return static|null 指定のラベルを持つドメイン
     */
    public static function labelOf($label)
    {
        return self::fieldOf('label', $label);
    }

    /**
     * 指定フィールドの値を持つドメインを取得します。
     * ※同じ値を持つドメインが存在する場合、 Domain::lists() の順序で後勝ちとなります
     *
     * @param string $field フィールド
     * @param mixed  $value 値
     * @return static|null 指定の値を持つドメイン
     */
    public static function fieldOf($field, $value)
    {
        if ($value instanceof static) {
            return $value;
        }
        $maps = self::maps($field);
        return isset($maps[$value]) ? $maps[$value] : null ;
    }

    /**
     * 値の一覧を配列で取得します。
     *
     * @param null|callable(static $domain):bool $matcher リストに含めるドメインの条件 (default: null)
     * @return mixed[]
     */
    public static function values($matcher = null)
    {
        return self::listOf('value', $matcher);
    }

    /**
     * ラベルの一覧を配列で取得します。
     *
     * @param null|callable(static $domain):bool $matcher リストに含めるドメインの条件 (default: null)
     * @return string[]
     */
    public static function labels($matcher = null)
    {
        return self::listOf('label', $matcher);
    }

    /**
     * 指定フィールドの一覧を配列で取得します。
     *
     * @param string $name 対象フィールド名
     * @param null|callable(static $domain):bool $matcher リストに含めるドメインの条件 (default: null)
     * @return mixed[]
     */
    public static function listOf($name, $matcher = null)
    {
        $values = [];
        foreach (self::lists($matcher) as $domain) {
            $values[] = $domain->$name;
        }
        return $values;
    }

    /**
     * ワークフロー：指定の状況(case)に応じたあるドメイン(current)から遷移可能な次のドメイン一覧を取得します。
     * 必要に応じてサブクラスでオーバーライドして下さい。
     *
     * @param mixed      $current 現在の値
     * @param mixed|null $case    場合分け情報 (default: null)
     * @return static[]
     */
    public static function nexts($current, $case = null)
    {
        return self::listAll();
    }

    /**
     * ワークフロー：指定フィールドの一覧を配列で取得します。
     * @param string     $name 指定フィールド名
     * @param mixed      $current 現在の値
     * @param mixed|null $case 場合分け情報 (default: null)
     * @return mixed[]
     */
    public static function nextOf($name, $current, $case = null)
    {
        $values = [];
        foreach (static::nexts($current, $case) as $domain) {
            $values[] = $domain->$name;
        }
        return $values;
    }

    /**
     * ワークフロー：値の一覧を配列で取得します。
     *
     * @param mixed      $current 現在の値
     * @param mixed|null $case 場合分け情報 (default: null)
     * @return mixed[]
     */
    public static function nextValues($current, $case = null)
    {
        return self::nextOf('value', $current, $case);
    }

    /**
     * ワークフロー：ラベルの一覧を配列で取得します。
     *
     * @param mixed      $current 現在の値
     * @param mixed|null $case 場合分け情報 (default: null)
     * @return string[]
     */
    public static function nextLabels($current, $case = null)
    {
        return self::nextOf('label', $current, $case);
    }
}
