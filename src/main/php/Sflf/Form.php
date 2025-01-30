<?php
// namespace App\Core; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 Validation 機能付きフォーム 基底クラス
 *
 * 【使い方】
 * require_once "/path/to/Form.php"; // or use AutoLoader
 *
 * class UserForm extends Form {
 *     public $user_id;
 *     public $name;
 *     public $mail_address;
 *     public $password;
 *     public $password_confirm;
 *     public $avatar;
 *     public $sex;
 *     public $birthday;
 *
 *     public $bank;
 *     public $shipping_addresses;
 *
 *     // サブフォーム定義
 *     // ※HTML name 属性は bank[xxxxx] （ bank[name], bank[branch], bank[holder], bank[number] ）
 *     const SUB_FORM = [
 *         'bank' => BankForm.class // or function($parent){ $bank = new BankForm(); ... something to init sub form ... ; return $bank; }
 *     ];
 *
 *     // サブフォームリスト定義
 *     // ※HTML name 属性は shipping_addresses[{$index}][xxxxx] （ shipping_addresses[0][zip], shipping_addresses[1][zip], shipping_addresses[1][street] ）
 *     const SUB_FORM_LIST = [
 *         'shipping_addresses' => AddressForm.class // or function($parent){ $address = new AddressForm(); ... something to init sub form ... ; return $address; }
 *     ];
 *
 *     // ファイルフォーム定義
 *     const FILES = ['avatar'];
 *
 *     // フォームラベル定義
 *     protected function labels() {
 *         return [
 *             'user_id'            => '会員ID',
 *             'name'               => '氏名',
 *             'mail_address'       => 'メールアドレス',
 *             'password'           => 'パスワード',
 *             'password_confirm'   => 'パスワード(確認)',
 *             'avatar'             => 'アバター画像',
 *             'sex'                => '性別',
 *             'birthday'           => '生年月日',
 *             'bank'               => '口座情報',
 *             'shipping_addresses' => '配送先'
 *         ];
 *     }
 *
 *     // Validation ルール定義
 *     // 定義済みの validation は Form::VALID_* で確認できます。（各種定義済み validation のパラメータは phpdoc を参照）
 *     // また、大枠では下記のような命名規則になっていますので目的の validation を見つける際の参考にして下さい。
 *     //
 *     // 　中断制御系　　　　　　： From::VALID_EXIT_*
 *     // 　アップロードファイル系： Form::VALID_FILE_*
 *     // 　サブフォーム系　　　　： Form::VALID_SUB_FORM_*
 *     // 　相互関係チェック系　　： Form::VALID_RELATION_* （相互関係チェック系の validation はフィールド指定されている自身の value をチェックしません）
 *     // 　フィールド比較系　　　： Form::VALID_*_INPUTTED
 *     // 　通常系　　　　　　　　： 上記以外の Form::VALID_*
 *     // 　カスタム系　　　　　　： 任意の文字列 （valid_{任意の文字列}($field, $label, $value [, $param1, $param2, ...]) で validation メソッドを実装）
 *     protected function rules() {
 *         return [
 *             'user_id' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_REFER | Form::EXIT_ON_FAILED]
 *             ],
 *             'name' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_MAX_LENGTH, 20, Form::APPLY_SAVE],
 *                 [Form::VALID_DEPENDENCE_CHAR, Form::APPLY_SAVE]
 *             ],
 *             'mail_address' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_MAIL_ADDRESS, Form::APPLY_SAVE],
 *                 ['mail_address_exists', Form::APPLY_SAVE | Form::EXIT_IF_ALREADY_HAS_ERROR] // カスタム Validation の実行
 *             ],
 *             'password' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_CREATE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_MIN_LENGTH, 8, Form::APPLY_SAVE ]
 *             ],
 *             'password_confirm' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_CREATE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_SAME_AS_INPUTTED, 'password', Form::APPLY_SAVE ]
 *             ],
 *             'avatar' => [
 *                 [Form::VALID_FILE_SIZE, 2 * UploadFile::MB, Form::APPLY_SAVE],
 *                 [Form::VALID_FILE_WEB_IMAGE_SUFFIX, Form::APPLY_SAVE]
 *             ],
 *             'sex' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_CONTAINS, Sex::values(), Form::APPLY_SAVE ]
 *             ],
 *             'birthday' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_DATETIME, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_AGE_GREATER_EQUAL, 18, Form::APPLY_SAVE ],
 *                 [Form::VALID_AGE_LESS_EQUAL, 100, Form::APPLY_CREATE ]
 *             ],
 *             'shipping_addresses' => [
 *                 [Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_MAX_SELECT_COUNT, 5, Form::APPLY_SAVE | Form::EXIT_ON_FAILED],
 *                 [Form::VALID_SUB_FORM_SERIAL_NO, 'shipping_no', Form::APPLY_SAVE]
 *             ]
 *         );
 *     }
 *
 *     // カスタム Validation の定義
 *     protected function valid_mail_address_exists($field, $label, $value) {
 *         if($this->_empty($value)) { return null; }
 *         if(Dao::exists(
 *             "SELECT * FROM user WHERE mail_address=:mail_address" . (!empty($this->user_id) ? " AND user_id<>:user_id" : ""),
 *             [':mail_address' => $value, ':user_id' => $this->user_id]
 *         )) {
 *             return "ご指定の{$label}は既に存在しています。";
 *         }
 *         return null;
 *     }
 *
 *     // validation に成功したら、生年月日を DateTime型 に変換
 *     protected function complete($apply) {
 *         list($this->birthday, ) = $this->_createDateTime($this->birthday);
 *     }
 * }
 *
 * $form = new UserForm();
 * $form->popurate($_REQUEST, $_FILES);
 *
 * $errors = array();
 * $form->validate($errors, Form::APPLY_CREATE);
 * if(!empty($errors)) {
 *     $view->assign('errors', $errors);
 *     // Soemthing to do
 * }
 *
 * // for confirm action
 * $form->avatar->saveTemporary("/path/to/temporary/dir"); // You can skip this step if your app doesn't have confirm action.
 *
 * // for complete action
 * $now = new \DateTime();
 * $user = $form->describe(UserEntity::class);  // or $user = new UserEntity(); $form->inject($user); <- this way you can use code completion by IDE
 * $user->avatar_file   = $form->avatar->getPublishFileName();
 * $user->registered_at = $now;
 * $userId = Dao::insert('user', $user);
 * $form->avatar->publish("/path/to/publish/dir/{$userId}");
 *
 * if(!empty($form->bank)) {
 *     $bank = $form->bank->describe(BankEntity::class); // or $bank = new BankEntity(); $form->bank->inject($bank);
 *     $bank->user_id       = $userId;
 *     $bank->registered_at = $now;
 *     Dao::insert('bank', $bank);
 * }
 *
 * Dao::query('DELETE FROM shipping_address WHERE user_id = :user_id', [':user_id' => $userId]);
 * foreach($form->shipping_addresses AS $i => $addressForm) {
 *     $address = $addressForm->describe(AddressEntity::class); // or $address = new AddressEntity(); $addressForm->inject($address);
 *     $address->user_id       = $userId;
 *     $address->shipping_no   = $i + 1;
 *     $address->registered_at = $now;
 *     Dao::insert('shipping_address', $address);
 * }
 *
 *
 * @todo multiple file form 対応
 * @todo sub form の file form / multiple file form 対応
 *
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.hiddens.php    hiddenタグ出力用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.errors.php     エラーメッセージ出力用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/block.if_errors.php     エラー有無分岐用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/block.unless_errors.php エラー有無分岐用 Smarty タグ
 *
 * @package   SFLF
 * @version   v2.2.2
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
abstract class Form
{
    // ----------------------------------------------------
    // オプション定義：適用範囲
    // ----------------------------------------------------
    /** @var int CRUD 分類による validation 適用範囲：[C]登録 */
    public const APPLY_CREATE = 1;
    /** @var int CRUD 分類による validation 適用範囲：[R]参照 */
    public const APPLY_READ = 2;
    /** @var int CRUD 分類による validation 適用範囲：[U]更新 */
    public const APPLY_UPDATE = 4;
    /** @var int CRUD 分類による validation 適用範囲：[D]削除 */
    public const APPLY_DELETE = 8;

    /** @var int CRUD 分類による validation 適用範囲（複合）：[CU]保存 */
    public const APPLY_SAVE = self::APPLY_CREATE | self::APPLY_UPDATE;
    /** @var int CRUD 分類による validation 適用範囲（複合）：[RUD]参照前提 */
    public const APPLY_REFER = self::APPLY_READ | self::APPLY_UPDATE | self::APPLY_DELETE;
    /** @var int CRUD 分類による validation 適用範囲（複合）：[CRUD]全て */
    public const APPLY_ALL = self::APPLY_CREATE | self::APPLY_READ | self::APPLY_UPDATE | self::APPLY_DELETE;

    // ----------------------------------------------------
    // オプション定義：中断挙動
    // ----------------------------------------------------
    /** @var int validation 中断挙動に関するオプション：このチェックがエラーになった場合、以降のチェックを中断する */
    public const EXIT_ON_FAILED = 1024;
    /** @var int validation 中断挙動に関するオプション：このチェックが通った場合、以降のチェックをスキップする */
    public const EXIT_ON_SUCCESS = 2048;
    /** @var int validation 中断挙動に関するオプション：既にエラーが存在する場合、このチェックを含む以降のチェックを中断する */
    public const EXIT_IF_ALREADY_HAS_ERROR = 4096;

    /** @var string validation 中断用の特殊コマンド：この値が返ると以降の validate を 中断 する */
    public const VALIDATE_COMMAND_EXIT = "@EXIT@";

    // ----------------------------------------------------
    // サブフォーム定義
    // ----------------------------------------------------
    /**
     * 単独のサブフォーム定義
     * サブフォームを使用する場合、サブクラスでサブフォームを定義して下さい
     *
     * 例）
     * - 'field_name' => SubForm.class
     * - 'field_name' => function($parent){ $sub_form = new SubForm(); ... something to init sub form ... ; return $sub_form; }
     *
     * @var array<string, class-string<Form>|callable(Form $parent):Form>
     */
    public const SUB_FORM = [];

    /**
     * 配列のサブフォーム定義
     * サブフォームを使用する場合、サブクラスでサブフォームを定義して下さい
     *
     * 例）
     * - 'field_name' => SubForm.class
     * - 'field_name' => function($parent){ $sub_form = new SubForm(); ... something to init sub form ... ; return $sub_form; }
     *
     * @var array<string, class-string<Form>|callable(Form $parent):Form>
     */
    public const SUB_FORM_LIST = [];

    // ----------------------------------------------------
    // ファイルフォーム定義
    // ----------------------------------------------------
    /**
     * ファイルフォームを使用する場合、サブクラスでファイルフォームを定義して下さい
     * 例） 'banner', 'avater'
     *
     * @var string[]
     */
    public const FILES = [];

    // ----------------------------------------------------
    // 日付フォーマット
    // ----------------------------------------------------
    /**
     * 受入れ可能な日付／日時フォーマットのリスト
     * 日付／日時 validation で個別のフォーマットを指定しない場合、本フォーマットが受入れ対象となります。
     * なお、本定義はサブクラスにてオーバーライド可能です。
     *
     * @var string[]
     */
    public const ACCEPTABLE_DATETIME_FORMAT = [
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
     * このフォームがサブフォームの場合、親フォームが格納される
     *
     * @var Form
     */
    protected $_parent_;

    /**
     * デフォルト入力コンバータ（同じフィールド名同士でコピーするコンバータ）を取得します。
     * ※必要に応じてサブクラスでオーバーライドして下さい
     *
     * @return callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed 入力用コンバータ
     */
    protected function converterInputDefault()
    {
        return function ($field, $defined, $src, $value, $form, $origin) { return $defined ? $value : $origin ; };
    }

    /**
     * 指定フィールドをコピー対象から除外するコンバータを取得します。
     *
     * @param string ...$excludes コピー対象外フィールド名
     * @return callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed 入力用コンバータ
     */
    public static function converterInputExcludes(...$excludes)
    {
        return function ($field, $defined, $src, $value, $form, $origin) use ($excludes) {
            if (in_array($field, $excludes)) {
                return $origin;
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * 指定フィールドのみをコピー対象とするコンバータを取得します。
     *
     * @param string ...$includes コピー対象フィールド名
     * @return callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed 入力用コンバータ
     */
    public static function converterInputIncludes(...$includes)
    {
        return function ($field, $defined, $src, $value, $form, $origin) use ($includes) {
            if (!in_array($field, $includes)) {
                return $origin;
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * 任意フィールドの値を別フィールドの値でコピーします。
     * ※指定の無いフィールドは通常通りコピーされます。
     * ※['field' => null] とすることでコピー対象から除外することも可能です。
     * ※['name' => function($src){ return "{$src->last_name} {$src->first_name}"; }] とすることで複数フィールドを対象にしたコピーなども可能です。
     *
     * @param array<string, null|string|callable(array<string, mixed>|object $src):mixed> $aliases コピー対象の別名指定連想配列
     * @return callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed 入力用コンバータ
     */
    public static function converterInputAlias(array $aliases)
    {
        return function ($field, $defined, $src, $value, $form, $origin) use ($aliases) {
            if (isset($aliases[$field])) {
                $alias = $aliases[$field];
                if (is_callable($alias)) {
                    return $alias($src);
                }
                return static::_get($src, $alias);
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * デフォルト出力コンバータ（同じフィールド名同士でコピーするコンバータ）を取得します。
     * ※必要に応じてサブクラスでオーバーライドして下さい
     *
     * @return callable(string $field, bool $defined, Form $form, mixed $value, array<string, mixed>|object $dest, mixed $origin):mixed 出力用コンバータ
     */
    protected function converterOutputDefault()
    {
        return function ($field, $defined, $form, $value, $dest, $origin) { return $defined ? $value : $origin ; };
    }

    /**
     * 指定のフィールドをコピー対象から除外するコンバータを取得します。
     *
     * @param string ...$excludes コピー対象外フィールド名
     * @return callable(string $field, bool $defined, Form $form, mixed $value, array<string, mixed>|object $dest, mixed $origin):mixed 出力用コンバータ
     */
    public static function converterOutputExcludes(...$excludes)
    {
        return function ($field, $defined, $form, $value, $dest, $origin) use ($excludes) {
            if (in_array($field, $excludes)) {
                return $origin;
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * 指定のフィールドのみをコピー対象とするコンバータを取得します。
     *
     * @param string ...$includes コピー対象フィールド名
     * @return callable(string $field, bool $defined, Form $form, mixed $value, array<string, mixed>|object $dest, mixed $origin):mixed 出力用コンバータ
     */
    public static function converterOutputIncludes(...$includes)
    {
        return function ($field, $defined, $form, $value, $dest, $origin) use ($includes) {
            if (!in_array($field, $includes)) {
                return $origin;
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * 任意フィールドの値を別フィールドの値でコピーします。
     * ※指定の無いフィールドは通常通りコピーされます。
     * ※['field' => null] とすることでコピー対象から除外することも可能です。
     * ※['name' => function($form){ return "{$form->last_name} {$form->first_name}"; }] とすることで複数フィールドを対象にしたコピーなども可能です。
     *
     * @param array<string, string|null|callable(Form $form):mixed> $aliases コピー対象の別名指定連想配列
     * @return callable(string $field, bool $defined, Form $form, mixed $value, array<string, mixed>|object $dest, mixed $origin):mixed 出力用コンバータ
     */
    public static function converterOutputAlias(array $aliases)
    {
        return function ($field, $defined, $form, $value, $dest, $origin) use ($aliases) {
            if (isset($aliases[$field])) {
                $alias = $aliases[$field];
                if (is_callable($alias)) {
                    return $alias($form);
                }
                return static::_get($form, $alias);
            }
            return $defined ? $value : $origin ;
        };
    }

    /**
     * リクエストデータ又は Dto オブジェクトから自身のインスタンス変数に値をコピーします。
     *
     * @param array<string, mixed>|object                                                                                                  $src       コピー元データ。リクエストデータ(=$_REQUEST)又はDtoオブジェクト
     * @param array<string, mixed>|null                                                                                                    $files     アップロードファイル情報(=$_FILES) (default: null)
     * @param callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed|null $converter 入力コンバータの戻り値が設定されます (default: null = Form::converterInputDefault())
     * @return void
     * @see Form::converterInputDefault()
     * @see Form::converterInputExcludes()
     * @see Form::converterInputIncludes()
     * @see Form::converterInputAlias()
     */
    public function popurate($src, $files = null, $converter = null)
    {
        if (empty($src) && empty($files)) {
            return;
        }

        if (empty($converter)) {
            $converter = $this->converterInputDefault();
        }

        $clazz = get_class($this);
        foreach (get_object_vars($this) as $field => $origin) {
            // サブフォームの解析
            if (array_key_exists($field, static::SUB_FORM)) {
                $this->$field = $this->_genarateSubForm(static::SUB_FORM[$field], static::_get($src, $field), $converter);
                continue;
            }

            // サブフォームリストの解析
            if (array_key_exists($field, static::SUB_FORM_LIST)) {
                $this->$field = [];
                $items        = static::_get($src, $field);
                if (empty($items)) {
                    continue;
                }
                foreach ($items as $item) {
                    $this->$field[] = $this->_genarateSubForm(static::SUB_FORM_LIST[$field], $item, $converter);
                }
                continue;
            }

            $this->$field = $converter($field, static::_has($src, $field), $src, static::_get($src, $field), $this, $origin);

            if (isset($files[$field])) {
                $this->$field = new UploadFile($clazz, $field, $files[$field]);
            } else {
                if (UploadFile::exists($clazz, $field)) {
                    $this->$field = UploadFile::load($clazz, $field);
                } elseif (in_array($field, static::FILES) && empty($this->$field)) {
                    $this->$field = UploadFile::createEmpty($clazz, $field);
                }
            }
        }
    }

    /**
     * サブフォームを生成します。
     *
     * @param class-string<Form>|callable(Form $parent):Form                                                                          $generator サブフォームクラス名 or サブフォーム生成関数
     * @param array<string, mixed>|object                                                                                             $src       入力データ
     * @param callable(string $field, bool $defined, array<string, mixed>|object $src, mixed $value, Form $form, mixed $origin):mixed $converter 入力コンバータの戻り値が設定されます
     * @return Form
     */
    private function _genarateSubForm($generator, $src, $converter)
    {
        $sub_form           = is_callable($generator) ? $generator($this) : new $generator() ;
        $sub_form->_parent_ = $this;
        $sub_form->popurate($src, null, $converter);
        return $sub_form;
    }

    /**
     * 指定の DTO オブジェクトに、自身の値をコピーします。
     * ※サブフォームは処理されません
     *
     * @template T of object
     * @param T                                                                                                   &$dto      コピー対象DTOオブジェクト
     * @param callable(string $field, bool $defined, Form $form, mixed $value, T $dest, mixed $origin):mixed|null $converter 出力用コンバータの戻り値が設定されます (default: null = Form::converterOutputDefault())
     * @return T
     * @see Form::converterOutputDefault()
     * @see Form::converterOutputExcludes()
     * @see Form::converterOutputIncludes()
     * @see Form::converterOutputAlias()
     */
    public function inject(&$dto, $converter = null)
    {
        if (empty($converter)) {
            $converter = $this->converterOutputDefault();
        }

        $this_clazz = get_class($this);
        foreach (get_object_vars($dto) as $field => $origin) {
            if (array_key_exists($field, static::SUB_FORM) || array_key_exists($field, static::SUB_FORM_LIST)) {
                continue;
            }
            $dto->$field = $converter($field, property_exists($this_clazz, $field), $this, static::_get($this, $field), $dto, $origin);
        }

        return $dto;
    }

    /**
     * 指定の DTO オブジェクトを生成し、自身の値をコピーします。
     * ※サブフォームは処理されません
     *
     * @template T of object
     * @param class-string<T>                                                                                     $clazz     DTOオブジェクトクラス名
     * @param callable(string $field, bool $defined, Form $form, mixed $value, T $dest, mixed $origin):mixed|null $converter 出力用コンバータの戻り値が設定されます (default: null = Form::converterOutputDefault())
     * @return T
     * @see Form::converterOutputDefault()
     * @see Form::converterOutputExcludes()
     * @see Form::converterOutputIncludes()
     * @see Form::converterOutputAlias()
     */
    public function describe($clazz, $converter = null)
    {
        $entity = new $clazz();
        $this->inject($entity, $converter);
        return $entity;
    }

    /**
     * 配列又はオブジェクトから値を取得します。
     *
     * @param array<array-key, mixed>|object $obj     配列 or オブジェクト
     * @param array-key                      $key     キー名
     * @param mixed|null                     $default デフォルト値 (default: null)
     * @return mixed|null 値
     */
    protected static function _get($obj, $key, $default = null)
    {
        if ($obj == null) {
            return $default;
        }
        if (is_array($obj)) {
            return isset($obj[$key]) ? $obj[$key] : $default ;
        }
        if (!($obj instanceof \stdClass)) {
            $clazz = get_class($obj);
            if (!property_exists($clazz, $key)) {
                return $default;
            }
        } else {
            if (!property_exists($obj, $key)) {
                return $default;
            }
        }
        return $obj->$key === null ? $default : $obj->$key ;
    }

    /**
     * 配列又はオブジェクトが指定のプロパティを持つか判定します
     *
     * @param array<array-key, mixed>|object $obj 配列 or オブジェクト
     * @param array-key                      $key キー名
     * @return bool true : 持つ, false : 持たない
     */
    protected static function _has($obj, $key)
    {
        if ($obj == null) {
            return false;
        }
        if (is_array($obj)) {
            return isset($obj[$key]);
        }
        if (!($obj instanceof \stdClass)) {
            $clazz = get_class($obj);
            return property_exists($clazz, $key);
        }
        return isset($obj->$key);
    }

    /**
     * 指定の配列から重複した値のリストを取得します。
     *
     * @param array<array-key, mixed> $array
     * @return mixed[]
     */
    protected function _duplicate($array)
    {
        $duplicate = [];
        if (empty($array)) {
            return $duplicate;
        }
        foreach (array_count_values($array) as $v => $c) {
            if (1 < $c) {
                $duplicate[] = $v;
            }
        }
        return $duplicate;
    }

    /**
     * 指定フィールドのラベルを取得します。
     *
     * @param string $field フィールド名
     * @return string フィールドラベル
     */
    protected function _label($field)
    {
        return static::_get($this->labels(), $field, $field);
    }

    /**
     * 指定のルールに従って validation を実施します。
     *
     * @param  array<string, string[]|mixed[]> &$errors エラー情報格納オブジェクト
     * @param  int                             $apply   Form::APPLY_* の Form オプションクラス定数の論理和
     * @return void
     * @throws InvalidValidateRuleException
     */
    public function validate(&$errors, $apply)
    {
        $this->_validate($errors, $apply);
    }

    /**
     * 指定のルールに従って validation を実施します。
     *
     * @param array<string, string[]|mixed[]> &$errors     エラー情報格納オブジェクト
     * @param int                             $apply       Form::APPLY_* の Form オプションクラス定数の論理和
     * @param string                          $parent_name サブフォーム時の親 name 名
     * @param int                             $index       サブフォームリスト時のインデックス (default: null)
     * @return void
     * @throws InvalidValidateRuleException
     */
    protected function _validate(&$errors, $apply, $parent_name = '', $index = null)
    {
        $this->before($apply);

        $labels = $this->labels();
        $rules  = $this->rules();
        if (empty($rules)) {
            $this->complete($apply);
            return;
        }

        $hasError = false;
        $clazz    = get_class($this);
        foreach ($rules as $target => $validations) {
            foreach ($validations as $validate) {
                // 定義内容チェック
                if (empty($validate)) {
                    continue;
                }
                $size = count($validate);
                if ($size < 2) {
                    throw new InvalidValidateRuleException("Validate rule has at least 2 or more options [ 'check_name', Form::APPLY_* | Form::EXIT_* ]");
                }

                // Validate 内容取得
                $check = $validate[0];

                // 引数取得
                $args   = [];
                $args[] = $target ;
                $args[] = isset($labels[$target]) ? $labels[$target] : $target ;
                $args[] = property_exists($clazz, $target) ? $this->$target : null ;
                if ($size > 2) {
                    $args = array_merge($args, array_slice($validate, 1, $size - 2));
                }

                // エラーキー構築
                $error_key = empty($parent_name) ? $target : "{$parent_name}[{$target}]" ;

                // オプション取得
                $option = $validate[$size - 1];

                // オプション処理
                if (!($option & $apply)) {
                    continue;
                }
                if ($option & Form::EXIT_IF_ALREADY_HAS_ERROR && isset($errors[$error_key]) && !empty($errors[$error_key])) {
                    break;
                }

                // Validation 実行
                $method  = "valid_{$check}";
                $invoker = new \ReflectionMethod($clazz, $method);
                $invoker->setAccessible(true);

                $error = $invoker->invokeArgs($this, $args);
                if (!empty($error)) {
                    if ($error == self::VALIDATE_COMMAND_EXIT) {
                        break;
                    }

                    $hasError = true;
                    if (!isset($errors[$error_key])) {
                        $errors[$error_key] = [];
                    }
                    if (is_array($error)) {
                        $errors[$error_key] = array_merge($errors[$error_key], $error);
                    } else {
                        $errors[$error_key][] = $error;
                    }

                    if ($option & Form::EXIT_ON_FAILED) {
                        break;
                    }
                } else {
                    if ($option & Form::EXIT_ON_SUCCESS) {
                        break;
                    }
                }
            }
        }

        // サブフォームを処理
        foreach (array_keys(static::SUB_FORM) as $field) {
            $sub_form = $this->$field;
            if (!empty($sub_form) && $sub_form instanceof Form) {
                $sub_form->_validate($errors, $apply, empty($parent_name) ? $field : "{$parent_name}[{$field}]");
            }
        }

        // サブフォームリストを処理
        foreach (array_keys(static::SUB_FORM_LIST) as $field) {
            if (!empty($this->$field)) {
                foreach ($this->$field as $i => $sub_form) {
                    if (!empty($sub_form) && $sub_form instanceof Form) {
                        $sub_form->_validate($errors, $apply, empty($parent_name) ? "{$field}[{$i}]" : "{$parent_name}[{$field}][{$i}]", $i);
                    }
                }
            }
        }

        if ($hasError) {
            $this->failed($errors, $apply);
        } else {
            $this->complete($apply);
        }

        return;
    }

    /**
     * フィールドラベル名を返します。
     * ※詳細はクラスコメントの【使い方】を参照
     *
     * @return array<string, string> フィールド名 と ラベル の連想配列
     */
    abstract protected function labels();

    /**
     * Validate ルールを返します。
     * ※詳細はクラスコメントの【使い方】を参照
     *
     * @return array<string, array<mixed[]>> フィールド名 と ルール配列 の連想配列
     */
    abstract protected function rules();

    /**
     * Validate 実行前処理
     * ※必要に応じてサブクラスでオーバーライド
     *
     * @param int $apply 適用オプション
     * @return void
     */
    protected function before($apply)
    {
        // 何もしない
    }

    /**
     * Validate 成功時処理
     * ※必要に応じてサブクラスでオーバーライド
     *
     * @param int $apply 適用オプション
     * @return void
     */
    protected function complete($apply)
    {
        // 何もしない
    }

    /**
     * Validate 失敗時処理
     * ※必要に応じてサブクラスでオーバーライド
     *
     * @param array<string, string[]> &$errors エラー情報
     * @param int                     $apply   適用オプション
     * @return void
     */
    protected function failed(&$errors, $apply)
    {
        // 何もしない
    }

    /**
     * 未入力の定義
     * 各種 valid_* の validation メソッドでの入力判定は本メソッドを使用して下さい。
     *
     * @param mixed|null $value
     * @return bool
     */
    protected function _empty($value)
    {
        if ($value instanceof UploadFile) {
            return $value->isEmpty();
        }
        if (is_array($value)) {
            return empty(array_filter($value, function ($i) { return $i !== null && $i !== ''; }));
        }
        return $value === null || $value === '';
    }

    //##########################################################################
    // 以下、validation メソッド定義
    //##########################################################################

    //--------------------------------------------------------------------------
    /**
     * 処理中断：指定のフィールドが空の場合
     *
     * ex)
     * - [Form::VALID_EXIT_EMPTY, 'target_field', Form::APPLY_*]
     *
     * @var string
     */
    public const VALID_EXIT_EMPTY = 'exit_empty';

    /**
     * 処理中断：指定のフィールドが空の場合
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 指定のフィールド
     * @return Form::VALIDATE_COMMAND_EXIT|null null: 処理継続, Form::VALIDATE_COMMAND_EXIT: 処理中断
     */
    protected function valid_exit_empty($field, $label, $value, $other)
    {
        if ($this->_empty($this->$other)) {
            return self::VALIDATE_COMMAND_EXIT;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 処理中断：指定のフィールドが空でない場合
     *
     * ex)
     * - [Form::VALID_EXIT_NOT_EMPTY, 'target_field', Form::APPLY_*]
     *
     * @var string
     */
    public const VALID_EXIT_NOT_EMPTY = 'exit_not_empty';

    /**
     * 処理中断：指定のフィールドが空でない場合
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 指定のフィールド
     * @return Form::VALIDATE_COMMAND_EXIT|null null: 処理継続, Form::VALIDATE_COMMAND_EXIT: 処理中断
     */
    protected function valid_exit_not_empty($field, $label, $value, $other)
    {
        if (!$this->_empty($this->$other)) {
            return self::VALIDATE_COMMAND_EXIT;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 処理中断：指定のフィールドが指定の値の場合
     *
     * ex)
     * - [Form::VALID_EXIT_IF, 'target_field', expect_value, Form::APPLY_*]
     *
     * @var string
     */
    public const VALID_EXIT_IF = 'exit_if';

    /**
     * 処理中断：指定のフィールドが指定の値の場合
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @param string      $other  指定のフィールド
     * @param string|null $expect 指定の値
     * @return Form::VALIDATE_COMMAND_EXIT|null null: 処理継続, Form::VALIDATE_COMMAND_EXIT: 処理中断
     */
    protected function valid_exit_if($field, $label, $value, $other, $expect)
    {
        if ($this->$other == $expect) {
            return self::VALIDATE_COMMAND_EXIT;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 処理中断：指定のフィールドが指定の値以外の場合
     *
     * ex)
     * - [Form::VALID_EXIT_UNLESS, 'target_field', expect_value, Form::APPLY_*]
     *
     * @var string
     */
    public const VALID_EXIT_UNLESS = 'exit_unless';

    /**
     * 処理中断：指定のフィールドが指定の値以外の場合
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @param string      $other  指定のフィールド
     * @param string|null $expect 指定の値
     * @return Form::VALIDATE_COMMAND_EXIT|null null: 処理継続, Form::VALIDATE_COMMAND_EXIT: 処理中断
     */
    protected function valid_exit_unless($field, $label, $value, $other, $expect)
    {
        if ($this->$other != $expect) {
            return self::VALIDATE_COMMAND_EXIT;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 処理中断：指定のフィールドが指定の値の何れかの場合
     *
     * ex)
     * - [Form::VALID_EXIT_IN, 'target_field', [expect_value, ...], Form::APPLY_*]
     *
     * @var string
     */
    public const VALID_EXIT_IN = 'exit_in';

    /**
     * 処理中断：指定のフィールドが指定の値の何れかの場合
     *
     * @param string      $field   検査対象フィールド名
     * @param string      $label   検査対象フィールドのラベル名
     * @param string|null $value   検索対象フィールドの値
     * @param string      $other   指定のフィールド
     * @param string[]    $expects 指定の値（複数）
     * @return Form::VALIDATE_COMMAND_EXIT|null null: 処理継続, Form::VALIDATE_COMMAND_EXIT: 処理中断
     */
    protected function valid_exit_in($field, $label, $value, $other, $expects)
    {
        if (in_array($this->$other, $expects)) {
            return self::VALIDATE_COMMAND_EXIT;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 相互関係：条件付き必須入力：指定フィールドの内、少なくともN項目以上入力すること
     *
     * ex)
     * - [Form::VALID_RELATION_REQUIRED_AT_LEAST_IN, 2, 'target1,target2,...', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_RELATION_REQUIRED_AT_LEAST_IN, 2, ['target1', 'target2', ...], Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * @var string
     */
    public const VALID_RELATION_REQUIRED_AT_LEAST_IN = 'relation_required_at_least_in';

    /**
     * 相互関係：条件付き必須入力：指定フィールドの内、少なくともN項目以上入力すること
     *
     * @param string          $field   検査対象フィールド名（相互関係チェックでは任意の名前）
     * @param string          $label   検査対象フィールドのラベル名（相互関係チェックでは通常は未定義）
     * @param string|null     $value   検索対象フィールドの値（相互関係チェックでは通常はnull）
     * @param int             $count   入力必要項目数
     * @param string|string[] $depends 指定フィールド（カンマ区切りのフィールド名 or フィールド名の配列）
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_relation_required_at_least_in($field, $label, $value, $count, $depends)
    {
        $labels       = $this->labels();
        $dependsLabel = [];
        $setCount     = 0;
        foreach (is_array($depends) ? $depends : explode(',', $depends) as $depend) {
            if (!$this->_empty($this->$depend)) {
                $setCount++;
            }
            $dependsLabel[] = isset($labels[$depend]) ? $labels[$depend] : $depend ;
        }
        if ($setCount < $count) {
            return join(', ', $dependsLabel)." の内 {$count} 項目以上を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 相互関係：重複不可
     *
     * ex)
     * - [Form::VALID_RELATION_UNIQUE, 'target1,target2,...', Form::APPLY_SAVE]
     * - [Form::VALID_RELATION_UNIQUE, ['target1', 'target2', ...], Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_UNIQUE          単一フィールドによる multiple セレクトの重複チェック
     * @see Form::VALID_SUB_FORM_UNIQUE 複数のサブフォームを跨る指定フィールドの重複チェック
     */
    public const VALID_RELATION_UNIQUE = 'relation_unique';

    /**
     * 相互関係：重複不可
     *
     * @param string          $field   検査対象フィールド名（相互関係チェックでは任意の名前）
     * @param string          $label   検査対象フィールドのラベル名（相互関係チェックでは通常は未定義）
     * @param string|null     $value   検索対象フィールドの値（相互関係チェックでは通常はnull）
     * @param string|string[] $depends 指定フィールド（カンマ区切りのフィールド名 or フィールド名の配列）
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_relation_unique($field, $label, $value, $depends)
    {
        $labels       = $this->labels();
        $dependsLabel = [];
        $values       = [];
        $emptyAll     = true;
        foreach (is_array($depends) ? $depends : explode(',', $depends) as $depend) {
            $emptyAll &= $this->_empty($this->$depend);
            $values[]       = $this->$depend;
            $dependsLabel[] = isset($labels[$depend]) ? $labels[$depend] : $depend ;
        }
        if ($emptyAll) {
            return null;
        }

        $duplicate = $this->_duplicate($values);
        if (!empty($duplicate)) {
            return join(', ', $dependsLabel)." にはそれぞれ異なる値を入力して下さい。[ ".join(',', $duplicate)." ] が重複しています。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 必須入力
     *
     * ex)
     * - [Form::VALID_REQUIRED, Form::APPLY_REFER | Form::EXIT_ON_FAILED]
     *
     * @var string
     */
    public const VALID_REQUIRED = 'required';

    /**
     * 必須入力
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_required($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return "{$label}を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 条件付き必須入力：指定フィールドの何れかが入力されている場合
     *
     * ex)
     * - [Form::VALID_REQUIRED_IF_INPUTTED_IN, 'target_field', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_REQUIRED_IF_INPUTTED_IN, 'target1,target2,...', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_REQUIRED_IF_INPUTTED_IN, ['target1', 'target2', ...], Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * @var string
     */
    public const VALID_REQUIRED_IF_INPUTTED_IN = 'required_if_inputted_in';

    /**
     * 条件付き必須入力：指定フィールドの何れかが入力されている場合
     *
     * @param string          $field   検査対象フィールド名
     * @param string          $label   検査対象フィールドのラベル名
     * @param string|null     $value   検索対象フィールドの値
     * @param string|string[] $depends 指定フィールド（単独のフィールド名 or カンマ区切りのフィールド名 or フィールド名の配列）
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_required_if_inputted_in($field, $label, $value, $depends)
    {
        $isset = false;
        foreach (is_array($depends) ? $depends : explode(',', $depends) as $depend) {
            $isset |= !$this->_empty($this->$depend);
        }
        if (!$isset) {
            return null;
        }
        if ($this->_empty($value)) {
            return "{$label}を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 条件付き必須入力：指定フィールドが指定の値の場合
     *
     * ex)
     * - [Form::VALID_REQUIRED_IF, 'target_field', expect_value, Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_REQUIRED_IF, 'target_field', [expect_value_1, expect_value_2, ...], Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * @var string
     */
    public const VALID_REQUIRED_IF = 'required_if';

    /**
     * 条件付き必須入力：指定フィールドが指定の値の場合
     *
     * @param string          $field  検査対象フィールド名
     * @param string          $label  検査対象フィールドのラベル名
     * @param string|null     $value  検索対象フィールドの値
     * @param string          $depend 指定フィールド名
     * @param string|string[] $expect 指定の値（配列の場合は何れかの値）
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_required_if($field, $label, $value, $depend, $expect)
    {
        if ($this->_empty($this->$depend)) {
            return null;
        }
        if (is_array($expect) && !in_array($this->$depend, $expect)) {
            return null;
        }
        if (!is_array($expect) && $this->$depend != $expect) {
            return null;
        }
        if ($this->_empty($value)) {
            return "{$label}を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 条件付き必須入力：指定フィールが指定の値以外の場合
     *
     * ex)
     * - [Form::VALID_REQUIRED_UNLESS, 'target_field', expect_value, Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_REQUIRED_UNLESS, 'target_field', [expect_value_1, expect_value_2, ...], Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * @var string
     */
    public const VALID_REQUIRED_UNLESS = 'required_unless';

    /**
     * 条件付き必須入力：指定フィールが指定の値以外の場合
     *
     * @param string          $field  検査対象フィールド名
     * @param string          $label  検査対象フィールドのラベル名
     * @param string|null     $value  検索対象フィールドの値
     * @param string          $depend 指定フィールド名
     * @param string|string[] $expect 指定の値（配列の場合は全ての値）
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_required_unless($field, $label, $value, $depend, $expect)
    {
        if ($this->_empty($this->$depend)) {
            return null;
        }
        if (is_array($expect) && in_array($this->$depend, $expect)) {
            return null;
        }
        if ($this->$depend == $expect) {
            return null;
        }
        if ($this->_empty($value)) {
            return "{$label}を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 条件付き空欄必須：指定フィールが指定の値の場合
     *
     * ex)
     * - [Form::VALID_EMPTY_IF, 'target_field', expect_value, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_EMPTY_IF = 'empty_if';

    /**
     * 条件付き空欄必須：指定フィールが指定の値の場合
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @param string      $depend 指定フィールド名
     * @param string      $expect 指定の値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_empty_if($field, $label, $value, $depend, $expect)
    {
        if ($this->_empty($this->$depend) || $this->$depend != $expect) {
            return null;
        }
        if (!$this->_empty($value)) {
            return "{$label}を空にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 条件付き空欄必須：指定フィールが指定の値以外の場合
     *
     * ex)
     * - [Form::VALID_EMPTY_UNLESS, 'target_field', expect_value, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_EMPTY_UNLESS = 'empty_unless';

    /**
     * 条件付き空欄必須：指定フィールが指定の値以外の場合
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @param string      $depend 指定フィールド名
     * @param string      $expect 指定の値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_empty_unless($field, $label, $value, $depend, $expect)
    {
        if ($this->_empty($this->$depend) || $this->$depend == $expect) {
            return null;
        }
        if (!$this->_empty($value)) {
            return "{$label}を空にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 一致
     *
     * ex)
     * - [Form::VALID_SAME_AS, expect_value, Form::APPLY_SAVE]
     * - [Form::VALID_SAME_AS, expect_value, 'expect_label', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_SAME_AS = 'same_as';

    /**
     * 一致
     *
     * @param string      $field        検査対象フィールド名
     * @param string      $label        検査対象フィールドのラベル名
     * @param string|null $value        検索対象フィールドの値
     * @param string      $expect       指定の値
     * @param string|null $expect_label エラーメッセージに記載される値のラベル名（未指定の場合は $expect を使用） (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_same_as($field, $label, $value, $expect, $expect_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if ($value != $expect) {
            return "{$label}は ".(empty($expect_label) ? $expect : $expect_label)." を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 不一致
     *
     * ex)
     * - [Form::VALID_NOT_SAME_AS, expect_value, Form::APPLY_SAVE]
     * - [Form::VALID_NOT_SAME_AS, expect_value, 'expect_label', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_NOT_SAME_AS = 'not_same_as';

    /**
     * 不一致
     *
     * @param string      $field        検査対象フィールド名
     * @param string      $label        検査対象フィールドのラベル名
     * @param string|null $value        検索対象フィールドの値
     * @param string      $expect       指定の値
     * @param string|null $expect_label エラーメッセージに記載される値のラベル名（未指定の場合は $expect を使用） (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_not_same_as($field, $label, $value, $expect, $expect_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if ($value == $expect) {
            return "{$label}は ".(empty($expect_label) ? $expect : $expect_label)." 以外を入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 正規表現：マッチのみ
     *
     * ex)
     * - [Form::VALID_REGEX, 'pattern', 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_REGEX = 'regex';

    /**
     * 正規表現：マッチのみ
     *
     * @param string      $field         検査対象フィールド名
     * @param string      $label         検査対象フィールドのラベル名
     * @param string|null $value         検索対象フィールドの値
     * @param string      $pattern       正規表現
     * @param string|null $pattern_label エラーメッセージに記載される正規表現のラベル名
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_regex($field, $label, $value, $pattern, $pattern_label)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        if (!preg_match($pattern, $value)) {
            return "{$label}は{$pattern_label}で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 正規表現：マッチ以外
     *
     * ex)
     * - [Form::VALID_REGEX, 'pattern', 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_NOT_REGEX = 'not_regex';

    /**
     * 正規表現：マッチ以外
     *
     * @param string      $field         検査対象フィールド名
     * @param string      $label         検査対象フィールドのラベル名
     * @param string|null $value         検索対象フィールドの値
     * @param string      $pattern       正規表現
     * @param string|null $pattern_label エラーメッセージに記載される正規表現のラベル名
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_not_regex($field, $label, $value, $pattern, $pattern_label)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        if (preg_match($pattern, $value)) {
            return "{$label}は{$pattern_label}以外で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 文字列長：最大
     *
     * ex)
     * - [Form::VALID_MAX_LENGTH, length, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MAX_LENGTH = 'max_length';

    /**
     * 文字列長：最大
     *
     * @param string               $field  検査対象フィールド名
     * @param string               $label  検査対象フィールドのラベル名
     * @param string|string[]|null $value  検索対象フィールドの値
     * @param int                  $length 最大文字数
     * @return string|string[]|null null: OK, string|string[]: NG = エラーメッセージ
     */
    protected function valid_max_length($field, $label, $value, $length)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));

        if (is_array($value)) {
            $errors = [];
            foreach ($value as $i => $v) {
                if (mb_strlen($v) > $length) {
                    $errors[] = ($i + 1)."番目の{$label}「{$v}」は{$length}文字以下で入力して下さい。";
                }
            }
            return $errors;
        }

        if (mb_strlen($value) > $length) {
            return "{$label}は{$length}文字以下で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 文字列長：一致
     *
     * ex)
     * - [Form::VALID_LENGTH, length, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_LENGTH = 'length';

    /**
     * 文字列長：一致
     *
     * @param string               $field  検査対象フィールド名
     * @param string               $label  検査対象フィールドのラベル名
     * @param string|string[]|null $value  検索対象フィールドの値
     * @param int                  $length 文字数
     * @return string|string[]|null null: OK, string|string[]: NG = エラーメッセージ
     */
    protected function valid_length($field, $label, $value, $length)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));

        if (is_array($value)) {
            $errors = [];
            foreach ($value as $i => $v) {
                if (mb_strlen($v) != $length) {
                    $errors[] = ($i + 1)."番目の{$label}「{$v}」は{$length}文字で入力して下さい。";
                }
            }
            return $errors;
        }

        if (mb_strlen($value) != $length) {
            return "{$label}は{$length}文字で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 文字列長：最小
     *
     * ex)
     * - [Form::VALID_MIN_LENGTH, length, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MIN_LENGTH = 'min_length';

    /**
     * 文字列長：最小
     *
     * @param string               $field  検査対象フィールド名
     * @param string               $label  検査対象フィールドのラベル名
     * @param string|string[]|null $value  検索対象フィールドの値
     * @param int                  $length 最小文字数
     * @return string|string[]|null null: OK, string|string[]: NG = エラーメッセージ
     */
    protected function valid_min_length($field, $label, $value, $length)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));

        if (is_array($value)) {
            $errors = [];
            foreach ($value as $i => $v) {
                if (mb_strlen($v) < $length) {
                    $errors[] = ($i + 1)."番目の{$label}「{$v}」は{$length}文字以上で入力して下さい。";
                }
            }
            return $errors;
        }

        if (mb_strlen($value) < $length) {
            return "{$label}は{$length}文字以上で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 数値
     *
     * ex)
     * - [Form::VALID_NUMBER, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_NUMBER = 'number';

    /**
     * 数値
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_number($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return null;
        }
        return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]*[\.]?[0-9]+$/u", "数値");
    }

    //--------------------------------------------------------------------------
    /**
     * 整数
     *
     * ex)
     * ― [Form::VALID_INTEGER, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_INTEGER = 'integer';

    /**
     * 整数
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param string|null $value  検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_integer($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return null;
        }
        return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]+$/u", "整数");
    }

    //--------------------------------------------------------------------------
    /**
     * 実数：小数点N桁まで
     *
     * ex)
     * - [Form::VALID_FLOAT, decimal, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FLOAT = 'float';

    /**
     * 実数：小数点N桁まで
     *
     * @param string      $field   検査対象フィールド名
     * @param string      $label   検査対象フィールドのラベル名
     * @param string|null $value   検索対象フィールドの値
     * @param int         $decimal 小数点桁数
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_float($field, $label, $value, $decimal)
    {
        if ($this->_empty($value)) {
            return null;
        }
        return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]+([\.][0-9]{0,{$decimal}})?$/u", "実数（小数点{$decimal}桁まで）");
    }

    //--------------------------------------------------------------------------
    /**
     * 整数範囲：最大
     *
     * ex)
     * - [Form::VALID_MAX_RANGE, max, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MAX_RANGE = 'max_range';

    /**
     * 整数範囲：最大
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param int|double  $max   最大値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_max_range($field, $label, $value, $max)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        if (doubleval($value) > $max) {
            return "{$label}は{$max}以下で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 整数範囲：最小
     *
     * ex)
     * - [Form::VALID_MIN_RANGE, min, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MIN_RANGE = 'min_range';

    /**
     * 整数範囲：最小
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param int|double  $min   最小値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_min_range($field, $label, $value, $min)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        if (doubleval($value) < $min) {
            return "{$label}は{$min}以上で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * メールアドレス：厳格なチェック
     *
     * ex)
     * - [Form::VALID_MAIL_ADDRESS, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MAIL_ADDRESS = 'mail_address';

    /**
     * メールアドレス：厳格なチェック
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_mail_address($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "{$label}はメールアドレス形式で入力して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * メールアドレス：緩いチェック
     * ※本 validation は過去に日本のキャリアにて作成できたRFCに準拠しないメールアドレス形式も許容します。
     *
     * ex)
     * - [Form::VALID_MAIL_ADDRESS_LOOSE, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MAIL_ADDRESS_LOOSE = 'mail_address_loose';

    /**
     * メールアドレス：緩いチェック
     * ※本 validation は過去に日本のキャリアにて作成できたRFCに準拠しないメールアドレス形式も許容します。
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_mail_address_loose($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, "/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,64}/", "メールアドレス形式");
    }

    //--------------------------------------------------------------------------
    /**
     * URL
     *
     * ex)
     * - [Form::VALID_URL, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_URL = 'url';

    /**
     * URL
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_url($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, "/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/u", "URL形式");
    }

    //--------------------------------------------------------------------------
    /**
     * IPv4(CIDR)アドレス
     *
     * ex)
     * - [Form::VALID_IP_V4_ADDRESS, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_IP_V4_ADDRESS = 'ip_v4_address';
    /** @var string IPv4の正規表現 */
    public const IP_V4_PATTERN = '/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/u';

    /**
     * IPv4(CIDR)アドレス
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_ip_v4_address($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, self::IP_V4_PATTERN, 'IPアドレス(CIDR)形式');
    }

    //--------------------------------------------------------------------------
    /**
     * IPv4(CIDR)アドレスリスト（デフォルト区切り：改行)
     *
     * ex)
     * - [Form::VALID_IP_V4_ADDRESS_LIST, Form::APPLY_SAVE]
     * - [Form::VALID_IP_V4_ADDRESS_LIST, 'delimiter', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_IP_V4_ADDRESS_LIST = 'ip_v4_address_list';

    /**
     * IPv4(CIDR)アドレスリスト（デフォルト区切り：改行)
     *
     * @param string           $field     検査対象フィールド名
     * @param string           $label     検査対象フィールドのラベル名
     * @param string|null      $value     検索対象フィールドの値
     * @param non-empty-string $delimiter 区切り文字 (default: PHP_EOL)
     * @return string[]|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_ip_v4_address_list($field, $label, $value, $delimiter = PHP_EOL)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        $errors = [];
        foreach (explode($delimiter, $value) as $i => $ip) {
            $ip = trim($ip);
            if (!empty($ip) && !preg_match(self::IP_V4_PATTERN, $ip)) {
                $errors[] = ($i + 1)." 行目の{$label} [ {$ip} ] はIPアドレス(CIDR)形式で入力して下さい。";
            }
        }
        return $errors;
    }

    //--------------------------------------------------------------------------
    /**
     * 半角数字
     *
     * ex)
     * - [Form::VALID_HALF_DIGIT, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_HALF_DIGIT = 'half_digit';

    /**
     * 半角数字
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_half_digit($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, "/^[0-9]+$/u", "半角数字");
    }

    //--------------------------------------------------------------------------
    /**
     * 半角英字
     *
     * ex)
     * - [Form::VALID_HALF_ALPHA, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_HALF_ALPHA = 'half_alpha';

    /**
     * 半角英字
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_half_alpha($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, "/^[a-zA-Z]+$/u", "半角英字");
    }

    //--------------------------------------------------------------------------
    /**
     * 半角英数字
     *
     * ex)
     * - [Form::VALID_HALF_ALPHA_DIGIT, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_HALF_ALPHA_DIGIT = 'half_alpha_digit';

    /**
     * 半角英数字
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_half_alpha_digit($field, $label, $value)
    {
        return $this->valid_regex($field, $label, $value, "/^[a-zA-Z0-9]+$/u", "半角英数字");
    }

    //--------------------------------------------------------------------------
    /**
     * 半角英数記号(デフォルト記号：!"#$%&'()*+,-./:;<=>?@[\]^_`{|}~ )
     *
     * ex)
     * - [Form::VALID_HALF_ALPHA_DIGIT_MARK, Form::APPLY_SAVE]
     * - [Form::VALID_HALF_ALPHA_DIGIT_MARK, 'mark', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_HALF_ALPHA_DIGIT_MARK = 'half_alpha_digit_mark';

    /**
     * 半角英数記号
     * 
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $mark  記号 (default: '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ')
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_half_alpha_digit_mark($field, $label, $value, $mark = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ')
    {
        return $this->valid_regex($field, $label, $value, "/^[a-zA-Z0-9".preg_quote($mark)."]+$/u", "半角英数記号（{$mark}を含む）");
    }

    //--------------------------------------------------------------------------
    /**
     * 全角ひらがな
     *
     * ex)
     * - [Form::VALID_HIRAGANA, Form::APPLY_SAVE]
     * - [Form::VALID_HIRAGANA, '（）', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_HIRAGANA = 'hiragana';

    /**
     * 全角ひらがな
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $extra 追加許可文字 (default: '')
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_hiragana($field, $label, $value, $extra = '')
    {
        return $this->valid_regex($field, $label, $value, "/^[\p{Hiragana}ー{$extra}]+$/u", "全角ひらがな");
    }

    //--------------------------------------------------------------------------
    /**
     * 全角カタカナ
     *
     * ex)
     * - [Form::VALID_FULL_KANA, Form::APPLY_SAVE]
     * - [Form::VALID_FULL_KANA, '（）', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FULL_KANA = 'full_kana';

    /**
     * 全角カタカナ
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $extra 追加許可文字 (default: '')
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_full_kana($field, $label, $value, $extra = '')
    {
        return $this->valid_regex($field, $label, $value, "/^[ァ-ヾ{$extra}]+$/u", "全角カタカナ");
    }

    //--------------------------------------------------------------------------
    /**
     * 機種依存文字（デフォルトチェックエンコード：sjis-win）
     *
     * ex)
     * - [Form::VALID_DEPENDENCE_CHAR, Form::APPLY_SAVE]
     * - [Form::VALID_DEPENDENCE_CHAR, 'encode', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_DEPENDENCE_CHAR = 'dependence_char';

    /**
     * 機種依存文字（デフォルトチェックエンコード：sjis-win）
     *
     * @param string               $field 検査対象フィールド名
     * @param string               $label 検査対象フィールドのラベル名
     * @param string|string[]|null $value 検索対象フィールドの値
     * @param string               $encode エンコード (default: 'sjis-win')
     * @return string|string[]|null null: OK, string|string[]: NG = エラーメッセージ
     */
    protected function valid_dependence_char($field, $label, $value, $encode = 'sjis-win')
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));

        if (is_array($value)) {
            $errors = [];
            foreach ($value as $i => $v) {
                $dependences = $this->_checkDependenceChar($v, $encode);
                if (!empty($dependences)) {
                    $errors[] = ($i + 1)."番目の{$label}「{$v}」に機種依存文字 [".join(", ", $dependences)."] が含まれます。機種依存文字を除去又は代替文字に変更して下さい。";
                }
            }
            return $errors;
        }

        $dependences = $this->_checkDependenceChar($value, $encode);
        if (!empty($dependences)) {
            return "{$label}に機種依存文字 [".join(", ", $dependences)."] が含まれます。機種依存文字を除去又は代替文字に変更して下さい。";
        }
        return null;
    }

    /**
     * 機種依存文字を抽出します。
     *
     * @param string|null $text   チェック対象文字列
     * @param string      $encode 機種依存判定用文字コード (default: 'sjis-win')
     * @return string[] 機種依存文字のリスト
     */
    private function _checkDependenceChar($text, $encode = 'sjis-win')
    {
        if ($text === null) {
            return [];
        }
        $org  = $text;
        $conv = mb_convert_encoding(mb_convert_encoding($text, $encode, 'UTF-8'), 'UTF-8', $encode);
        if (strlen($org) != strlen($conv)) {
            $diff = array_diff(preg_split("//u", $org, -1, PREG_SPLIT_NO_EMPTY) ?: [], preg_split("//u", $conv, -1, PREG_SPLIT_NO_EMPTY) ?: []);
            return $diff;
        }

        return [];
    }

    //--------------------------------------------------------------------------
    /**
     * NGワード
     *
     * $ng_words は 配列 又は ワードリストのファイルパス。
     * ワードリストは改行区切りで定義。
     *
     *  - 英数字は半角小文字
     *  - 日本語は全角カタカナと漢字
     *
     * で登録すると曖昧検索になります。
     * なお、短い単語は ^〇〇$ と定義することで全体一致検索にできます
     *
     * ex)
     * - [Form::VALID_NG_WORD, 'ng_words_file_path', Form::APPLY_SAVE]
     * - [Form::VALID_NG_WORD, ['ng_words', ...], Form::APPLY_SAVE]
     * - [Form::VALID_NG_WORD, ng_words, separate_letter_pattern, blank_letter_pattern, blank_apply_length, blank_apply_ratio, Form::APPLY_SAVE]
     * -----
     * - separate_letter_pattern : 区切り文字パターン／ここで指定した文字は区切り文字としてチェック時に無視されます (default: [\p{Common}])
     *   - ex) d.u.m.m.y や d u m m y を dummy にマッチさせる為の '.' や ' ' に該当するパターンを指定
     * - blank_letter_pattern : 伏字文字パターン／ここで指定した文字は伏字としてチェック時に考慮されます (default: [\p{M}\p{S}〇*＊_＿])
     *   - ex) d〇mmy や dum*y を dummy にマッチさせる為の '〇' や '*' に該当するパターンを指定
     * - blank_apply_length : 伏字文字パターンチェックを適用する最低NGワード文字数 (default: 3)
     * - blank_apply_ratio : 伏字文字パターンチェックを適用するNGワードに対する伏字の割合 (default: 0.4)
     *   - ex) 0.4 設定の場合、 s〇x, dum〇y, d〇m〇y はそれぞれ sex, dummy にマッチするが 〇e〇, d〇〇〇y はマッチしない
     *
     * @var string
     */
    public const VALID_NG_WORD = 'ng_word';

    /**
     * NGワード
     *
     * @param string          $field                   検査対象フィールド名
     * @param string          $label                   検査対象フィールドのラベル名
     * @param string|null     $value                   検索対象フィールドの値
     * @param string|string[] $ng_words                string: NGワードを記載したファイルパス, string[]: NGワード
     * @param string          $separate_letter_pattern 区切り文字パターン／ここで指定した文字は区切り文字としてチェック時に無視されます (default: [\p{Common}])
     * @param string          $blank_letter_pattern    伏字文字パターン／ここで指定した文字は伏字としてチェック時に考慮されます (default: [\p{M}\p{S}〇*＊_＿])
     * @param int             $blank_apply_length      伏字文字パターンチェックを適用する最低NGワード文字数 (default: 3)
     * @param double          $blank_apply_ratio       伏字文字パターンチェックを適用するNGワードに対する伏字の割合 (default: 0.4)
     * @return string|string[]|null null: OK, string|string[]: NG = エラーメッセージ
     */
    protected function valid_ng_word($field, $label, $value, $ng_words, $separate_letter_pattern = '[\p{Common}]', $blank_letter_pattern = '[\p{M}\p{S}〇*＊_＿]', $blank_apply_length = 3, $blank_apply_ratio = 0.4)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        if (!is_array($ng_words)) {
            $ng_words = file($ng_words, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }

        // 伏字の考慮
        $len               = mb_strlen($value);
        $blank_leter_index = [];
        if ($len >= $blank_apply_length) {
            $index = 0;
            foreach (preg_split("//u", $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $letter) {
                if (preg_match('/^'.$blank_letter_pattern.'$/u', $letter)) {
                    $blank_leter_index[] = $index++;
                    continue;
                }
                if (preg_match('/^'.$separate_letter_pattern.'$/u', $letter)) {
                    continue;
                }
                $index++;
            }
        }
        if ($len * $blank_apply_ratio < count($blank_leter_index)) {
            $blank_leter_index = [];
        }

        // NGワードチェック
        $matches = [];
        foreach ($ng_words as $word) {
            if (mb_strlen(trim($word, '^$')) > $len) {
                continue;
            }
            $regex = $this->_ngWordToMatcher($word, $separate_letter_pattern, $blank_letter_pattern, $blank_leter_index);
            if (preg_match($regex, $value, $matches)) {
                return "{$label} に利用できない単語「{$matches[0]}」が含まれます。";
            }
        }

        return null;
    }

    /**
     * NGワードを検索用正規表現に変換します。
     *
     * @param string $word                    単語
     * @param string $separate_letter_pattern 区切り文字パターン
     * @param string $blank_letter_pattern    伏字文字パターン
     * @param int[]  $blank_leter_index       伏字インデックス
     * @return string 正規表現
     */
    private function _ngWordToMatcher($word, $separate_letter_pattern, $blank_letter_pattern, $blank_leter_index)
    {
        $regex = '';
        $i     = 0;
        foreach (preg_split("//u", $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $letter) {
            $letter_pattern = isset(self::$_LETTER_TO_REGEX[$letter]) ? self::$_LETTER_TO_REGEX[$letter] : preg_quote($letter, '/') ;
            if (in_array($letter_pattern, ['^', '$'])) {
                $regex .= $letter_pattern.$separate_letter_pattern.'*';
                continue;
            }
            $regex .= in_array($i++, $blank_leter_index) ? $blank_letter_pattern.'?'.$letter_pattern.'?' : $letter_pattern ;
            $regex .= $separate_letter_pattern.'*';
        }
        $regex = mb_substr($regex, 0, mb_strlen($regex) - mb_strlen($separate_letter_pattern.'*'));
        return '/'.$regex.'/u';
    }

    /**
     * 文字の曖昧検索用正規表現変換マップ
     *
     * @var array<array-key, string>
     */
    private static $_LETTER_TO_REGEX = [
        "^" => "^",
        "$" => "$",
        "a" => "([aAａＡⒶⓐ🄰🅐🅰@＠])",
        "b" => "([bBｂＢⒷⓑ🄱🅑🅱])",
        "c" => "([cCｃＣⒸⓒ🄲🅒🅲©])",
        "d" => "([dDｄＤⒹⓓ🄳🅓🅳])",
        "e" => "([eEｅＥⒺⓔ🄴🅔🅴])",
        "f" => "([fFｆＦⒻⓕ🄵🅕🅵])",
        "g" => "([gGｇＧⒼⓖ🄶🅖🅶])",
        "h" => "([hHｈＨⒽⓗ🄷🅗🅷])",
        "i" => "([iIｉＩⒾⓘ🄸🅘🅸])",
        "j" => "([jJｊＪⒿⓙ🄹🅙🅹])",
        "k" => "([kKｋＫⓀⓚ🄺🅚🅺])",
        "l" => "([lLｌＬⓁⓛ🄻🅛🅻])",
        "m" => "([mMｍＭⓂⓜ🄼🅜🅼])",
        "n" => "([nNｎＮⓃⓝ🄽🅝🅽])",
        "o" => "([oOｏＯⓄⓞ🄾🅞🅾])",
        "p" => "([pPｐＰⓅⓟ🄿🅟🅿℗])",
        "q" => "([qQｑＱⓆⓠ🅀🅠🆀])",
        "r" => "([rRｒＲⓇⓡ🅁🅡🆁®])",
        "s" => "([sSｓＳⓈⓢ🅂🅢🆂])",
        "t" => "([tTｔＴⓉⓣ🅃🅣🆃])",
        "u" => "([uUｕＵⓊⓤ🅄🅤🆄])",
        "v" => "([vVｖＶⓋⓥ🅅🅥🆅])",
        "w" => "([wWｗＷⓌⓦ🅆🅦🆆])",
        "x" => "([xXｘＸⓍⓧ🅇🅧🆇])",
        "y" => "([yYｙＹⓎⓨ🅈🅨🆈])",
        "z" => "([zZｚＺⓏⓩ🅉🅩🆉])",
        "0" => "([0０⓿])",
        "1" => "([1１①⓵❶➀➊㊀一壱壹弌🈩])",
        "2" => "([2２②⓶❷➁➋㊁二弐貳弎🈔])",
        "3" => "([3３③⓷❸➂➌㊂三参參弎🈪])",
        "4" => "([4４④⓸❹➃➍㊃四肆])",
        "5" => "([5５⑤⓹❺➄➎㊄五伍])",
        "6" => "([6６⑥⓺❻➅➏㊅六陸])",
        "7" => "([7７⑦⓻❼➆➐㊆七漆柒質])",
        "8" => "([8８⑧⓼❽➇➑㊇八捌])",
        "9" => "([9９⑨⓽❾➈➒㊈九玖])",
        'ア' => '([アｱ㋐あァｧぁ])',
        'イ' => '([イｲ㋑㋼いィｨぃヰゐ])',
        'ウ' => '([ウｳ㋒うゥｩぅヱゑ])',
        'エ' => '([エｴ㋓㋽えェｪぇ])',
        'オ' => '([オｵ㋔おォｫぉ])',
        'カ' => '([カｶ㋕かヵゕ])',
        'キ' => '([キｷ㋖き])',
        'ク' => '([クｸ㋗く])',
        'ケ' => '([ケｹ㋘けヶ])',
        'コ' => '([コｺ㋙こ])',
        'サ' => '([サｻ㋚さ🈂])',
        'シ' => '([シｼ㋛し])',
        'ス' => '([スｽ㋜す])',
        'セ' => '([セｾ㋝せ])',
        'ソ' => '([ソｿ㋞そ])',
        'タ' => '([タﾀ㋟た])',
        'チ' => '([チﾁ㋠ち])',
        'ツ' => '([ツﾂ㋡つッｯっ])',
        'テ' => '([テﾃ㋢て])',
        'ト' => '([トﾄ㋣と])',
        'ナ' => '([ナﾅ㋤な])',
        'ニ' => '([ニﾆ㊁㋥に🈔])',
        'ヌ' => '([ヌﾇ㋦ぬ])',
        'ネ' => '([ネﾈ㋧ね])',
        'ノ' => '([ノﾉ㋨の])',
        'ハ' => '([ハﾊ㋩は])',
        'ヒ' => '([ヒﾋ㋪ひ])',
        'フ' => '([フﾌ㋫ふ])',
        'ヘ' => '([ヘﾍ㋬へ])',
        'ホ' => '([ホﾎ㋭ほ])',
        'マ' => '([マﾏ㋮ま])',
        'ミ' => '([ミﾐ㋯み])',
        'ム' => '([ムﾑ㋰む])',
        'メ' => '([メﾒ㋱め])',
        'モ' => '([モﾓ㋲も])',
        'ヤ' => '([ヤﾔ㋳やャｬゃ])',
        'ユ' => '([ユﾕ㋴ゆュｭゅ])',
        'ヨ' => '([ヨﾖ㋵よョｮょ])',
        'ラ' => '([ラﾗ㋶ら])',
        'リ' => '([リﾘ㋷り])',
        'ル' => '([ルﾙ㋸る])',
        'レ' => '([レﾚ㋹れ])',
        'ロ' => '([ロﾛ㋺ろ])',
        'ワ' => '([ワﾜ㋻わヮゎ])',
        'ヲ' => '([ヲｦ㋾を])',
        'ン' => '([ンﾝん])',
        'ガ' => '([ガが]|[カヵｶか][゛ﾞ])',
        'ギ' => '([ギぎ]|[キｷき][゛ﾞ])',
        'グ' => '([グぐ]|[クｸく][゛ﾞ])',
        'ゲ' => '([ゲげ]|[ケヶｹけ][゛ﾞ])',
        'ゴ' => '([ゴご]|[コｺこ][゛ﾞ])',
        'ザ' => '([ザざ]|[サｻさ][゛ﾞ])',
        'ジ' => '([ジじ]|[シｼし][゛ﾞ])',
        'ズ' => '([ズず]|[スｽす][゛ﾞ])',
        'ゼ' => '([ゼぜ]|[セｾせ][゛ﾞ])',
        'ゾ' => '([ゾぞ]|[ソｿそ][゛ﾞ])',
        'ダ' => '([ダだ]|[タﾀた][゛ﾞ])',
        'ヂ' => '([ヂぢ]|[チﾁち][゛ﾞ])',
        'ヅ' => '([ヅづ]|[ツッﾂつっ][゛ﾞ])',
        'デ' => '([デで]|[テﾃて][゛ﾞ])',
        'ド' => '([ドど]|[トﾄと][゛ﾞ])',
        'バ' => '([バば]|[ハﾊは][゛ﾞ])',
        'ビ' => '([ビび]|[ヒﾋひ][゛ﾞ])',
        'ブ' => '([ブぶ]|[フﾌふ][゛ﾞ])',
        'ベ' => '([ベべ]|[ヘﾍへ][゛ﾞ])',
        'ボ' => '([ボぼ]|[ホﾎほ][゜ﾟ])',
        'パ' => '([パぱ]|[ハﾊは][゜ﾟ])',
        'ピ' => '([ピぴ]|[ヒﾋひ][゜ﾟ])',
        'プ' => '([プぷ]|[フﾌふ][゜ﾟ])',
        'ペ' => '([ペぺ]|[ヘﾍへ][゜ﾟ])',
        'ポ' => '([ポぽ]|[ホﾎほ][゜ﾟ])',
        'ヴ' => '(ヴ|[ウゥｳうぅ][゛ﾞ])',
        'ァ' => '([アｱ㋐あァｧぁ])',
        'ィ' => '([イｲ㋑㋼いィｨぃヰゐ])',
        'ゥ' => '([ウｳ㋒うゥｩぅヱゑ])',
        'ェ' => '([エｴ㋓㋽えェｪぇ])',
        'ォ' => '([オｵ㋔おォｫぉ])',
        'ヵ' => '([カｶ㋕かヵゕ])',
        'ヶ' => '([ケｹ㋘けヶ])',
        'ッ' => '([ツﾂ㋡つッｯっ])',
        'ャ' => '([ヤﾔ㋳やャｬゃ])',
        'ュ' => '([ユﾕ㋴ゆュｭゅ])',
        'ョ' => '([ヨﾖ㋵よョｮょ])',
        'ヮ' => '([ワﾜ㋻わヮゎ])',
        '゛' => '([゛ﾞ])',
        '゜' => '([゜ﾟ])',
        'ー' => '([ー-])'
    ];

    //--------------------------------------------------------------------------
    /**
     * リスト含有
     *
     * ex)
     * - [Form::VALID_CONTAINS, [expect, ...], Form::APPLY_SAVE]
     * - [Form::VALID_CONTAINS, XxxxDomain::values(), Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_CONTAINS = 'contains';

    /**
     * リスト含有
     *
     * @param string               $field 検査対象フィールド名
     * @param string               $label 検査対象フィールドのラベル名
     * @param string|string[]|null $value 検索対象フィールドの値
     * @param mixed[]              $list  リスト
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_contains($field, $label, $value, $list)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!in_array($v, $list)) {
                    return "{$label}は指定の一覧から選択して下さい。";
                }
            }
        } else {
            if (!in_array($value, $list)) {
                return "{$label}は指定の一覧から選択して下さい。";
            }
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * リスト選択数：下限
     *
     * ex)
     * - [Form::VALID_MIN_SELECT_COUNT, min, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MIN_SELECT_COUNT = 'min_select_count';

    /**
     * リスト選択数：下限
     *
     * @param string        $field 検査対象フィールド名
     * @param string        $label 検査対象フィールドのラベル名
     * @param string[]|null $value 検索対象フィールドの値
     * @param int           $min   下限選択数
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_min_select_count($field, $label, $value, $min)
    {
        $size = $this->_empty($value) ? 0 : (is_array($value) ? count($value) : 1) ;
        if ($size < $min) {
            return "{$label}を {$min} 個以上選択して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * リスト選択数：一致
     *
     * ex)
     * - [Form::VALID_SELECT_COUNT, count, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_SELECT_COUNT = 'select_count';

    /**
     * リスト選択数：一致
     *
     * @param string        $field 検査対象フィールド名
     * @param string        $label 検査対象フィールドのラベル名
     * @param string[]|null $value 検索対象フィールドの値
     * @param int           $count 選択件数
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_select_count($field, $label, $value, $count)
    {
        $size = $this->_empty($value) ? 0 : (is_array($value) ? count($value) : 1) ;
        if ($size != $count) {
            return "{$label}を {$count} 個選択して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * リスト選択数：上限
     *
     * ex)
     * - [Form::VALID_MAX_SELECT_COUNT, max, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_MAX_SELECT_COUNT = 'max_select_count';

    /**
     * リスト選択数：上限
     *
     * @param string        $field 検査対象フィールド名
     * @param string        $label 検査対象フィールドのラベル名
     * @param string[]|null $value 検索対象フィールドの値
     * @param int           $max   上限選択数
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_max_select_count($field, $label, $value, $max)
    {
        $size = $this->_empty($value) ? 0 : (is_array($value) ? count($value) : 1) ;
        if ($size > $max) {
            return "{$label}は {$max} 個以下で選択して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * リスト選択：重複不可
     *
     * ex)
     * - [Form::VALID_UNIQUE, Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_RELATION_UNIQUE 複数フィールドに跨る重複チェック
     * @see Form::VALID_SUB_FORM_UNIQUE 複数のサブフォームを跨る指定フィールドの重複チェック
     */
    public const VALID_UNIQUE = 'unique';

    /**
     * リスト選択：重複不可
     *
     * @param string        $field 検査対象フィールド名
     * @param string        $label 検査対象フィールドのラベル名
     * @param string[]|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_unique($field, $label, $value)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        $duplicate = $this->_duplicate($value);
        if (!empty($duplicate)) {
            return "{$label}には異なる値を入力して下さい。[ ".join(',', $duplicate)." ] が重複しています。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時フォーマット
     *
     * ex)
     * - [Form::VALID_DATETIME, Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * @var string
     *
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_DATETIME = 'datetime';

    /**
     * 日時フォーマット
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_datetime($field, $label, $value, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $date = $this->_createDateTime($value, $main_format);
        if (empty($date)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        return null;
    }

    /**
     * DateTime オブジェクトを解析します。
     * ※本メソッドは _analyzeDateTime() から日付フォーマット情報を除外して日時のみを返す簡易メソッドです。
     *
     * @param string|\DateTime|null $value       日時文字列
     * @param string|null           $main_format 優先受入れフォーマット (default: null)
     * @return \DateTime|null 解析後の日付
     */
    protected function _createDateTime($value, $main_format = null)
    {
        list($date, ) = $this->_analyzeDateTime($value, $main_format);
        return $date;
    }

    /**
     * DateTime オブジェクトを解析します。
     * ※本メソッドは解析に成功した日付フォーマットも返します
     *
     * @param string|\DateTime|null $value       日時文字列
     * @param string|null           $main_format 優先受入れフォーマット (default: null)
     * @return array{0: \DateTime|null, 1: string|null} [日時, 実際に解析に利用されたフォーマット]
     */
    protected function _analyzeDateTime($value, $main_format = null)
    {
        if ($this->_empty($value)) {
            return [null, null];
        }
        assert(!is_null($value));
        if ($value instanceof \DateTime) {
            return [$value, null];
        }

        $formats = static::ACCEPTABLE_DATETIME_FORMAT ;
        if (!empty($main_format)) {
            array_unshift($formats, $main_format);
        }

        $date         = null;
        $apply_format = null;
        foreach ($formats as $format) {
            $date = $this->_tryToParseDateTime($value, $format);
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
     * @param string $value  日時文字列
     * @param string $format 日時フォーマット
     * @return \DateTime|null
     */
    private function _tryToParseDateTime($value, $format)
    {
        $date = \DateTime::createFromFormat("!{$format}", $value);
        $le   = \DateTime::getLastErrors();
        return $date === false || !empty($le['errors']) || !empty($le['warnings']) ? null : $date->setTimezone(new \DateTimeZone(date_default_timezone_get())) ;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：未来日(当日含まず)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_FUTURE_THAN, 'now', Form::APPLY_SAVE]
     * - [Form::VALID_FUTURE_THAN, 'now', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_FUTURE_THAN = 'future_than';

    /**
     * 日時：未来日(当日含まず)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $point_time  起点日時文字列 (ex: 'now')
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_future_than($field, $label, $value, $point_time, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        list($target, $apply_format) = $this->_analyzeDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = new \DateTime($point_time);
        if ($target <= $point) {
            return "{$label}は ".$point->format($main_format ? $main_format : ($apply_format ? $apply_format : 'Y-m-d H:i:s'))." よりも未来日を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：未来日(当日含む)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_FUTURE_EQUAL, 'now', Form::APPLY_SAVE]
     * - [Form::VALID_FUTURE_EQUAL, 'now', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_FUTURE_EQUAL = 'future_equal';

    /**
     * 日時：未来日(当日含む)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $point_time  起点日時文字列 (ex: 'now')
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_future_equal($field, $label, $value, $point_time, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        list($target, $apply_format) = $this->_analyzeDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = new \DateTime($point_time);
        if ($target < $point) {
            return "{$label}は ".$point->format($main_format ? $main_format : ($apply_format ? $apply_format : 'Y-m-d H:i:s'))." よりも未来日(当日含む)を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：過去日(当日含まず)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_PAST_THAN, 'now', Form::APPLY_SAVE]
     * - [Form::VALID_PAST_THAN, 'now', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_PAST_THAN = 'past_than';

    /**
     * 日時：過去日(当日含まず)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $point_time  起点日時文字列 (ex: 'now')
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_past_than($field, $label, $value, $point_time, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        list($target, $apply_format) = $this->_analyzeDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = new \DateTime($point_time);
        if ($target >= $point) {
            return "{$label}は ".$point->format($main_format ? $main_format : ($apply_format ? $apply_format : 'Y-m-d H:i:s'))." よりも過去日を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：過去日(当日含む)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_PAST_EQUAL, 'now', Form::APPLY_SAVE]
     * - [Form::VALID_PAST_EQUAL, 'now', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_PAST_EQUAL = 'past_equal';

    /**
     * 日時：過去日(当日含む)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $point_time  起点日時文字列 (ex: 'now')
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_past_equal($field, $label, $value, $point_time, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        list($target, $apply_format) = $this->_analyzeDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = new \DateTime($point_time);
        if ($target > $point) {
            return "{$label}は ".$point->format($main_format ? $main_format : ($apply_format ? $apply_format : 'Y-m-d H:i:s'))." よりも過去日(当日含む)を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：年齢制限：以上
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_AGE_GREATER_EQUAL, age, Form::APPLY_SAVE]
     * - [Form::VALID_AGE_GREATER_EQUAL, age, 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_AGE_GREATER_EQUAL = 'age_greater_equal';

    /**
     * 日時：年齢制限：以上
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param int         $age         年齢
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_age_greater_equal($field, $label, $value, $age, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = new \DateTime("-{$age} year");
        if ($target > $point) {
            return "{$age}歳未満の方はご利用頂けません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * 日時：年齢制限：以下
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_AGE_LESS_EQUAL, age, Form::APPLY_CREATE]
     * - [Form::VALID_AGE_LESS_EQUAL, age, 'main_format', Form::APPLY_CREATE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_AGE_LESS_EQUAL = 'age_less_equal';

    /**
     * 日時：年齢制限：以下
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param int         $age         年齢
     * @param string|null $main_format 優先する日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_age_less_equal($field, $label, $value, $age, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $invalid_age = $age + 1;
        $point       = new \DateTime("-{$invalid_age} year");
        if ($target <= $point) {
            return "{$invalid_age}歳以上の方はご利用頂けません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：サイズ
     *
     * ex)
     * - [Form::VALID_FILE_SIZE, size, Form::APPLY_SAVE]
     * - [Form::VALID_FILE_SIZE, size, 'label_of_size', Form::APPLY_SAVE]
     * - [Form::VALID_FILE_SIZE, 2 * UploadFile::MB, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_SIZE = 'file_size';

    /**
     * アップロードファイル：サイズ
     *
     * @param string          $field      検査対象フィールド名
     * @param string          $label      検査対象フィールドのラベル名
     * @param UploadFile|null $value      検索対象フィールドの値
     * @param int             $size       ファイルサイズ[byte]
     * @param string|null     $size_label サイズを表すラベル (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_size($field, $label, $value, $size, $size_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->size >= $size) {
            return "{$label}のファイルサイズ [ ".$value->size." byte ] が ".($size_label ? $size_label : "{$size} byte")." を超えています。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：ファイル名
     *
     * ex)
     * - [Form::VALID_FILE_NAME_MATCH, pattern, Form::APPLY_SAVE]
     * - [Form::VALID_FILE_NAME_MATCH, pattern, 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_NAME_MATCH = 'file_name_match';

    /**
     * アップロードファイル：ファイル名
     *
     * @param string          $field         検査対象フィールド名
     * @param string          $label         検査対象フィールドのラベル名
     * @param UploadFile|null $value         検索対象フィールドの値
     * @param string          $pattern       ファイル名の正規表現
     * @param string|null     $pattern_label ファイル名形式（正規表現）のラベル (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_name_match($field, $label, $value, $pattern, $pattern_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if (!$value->matchFileName($pattern)) {
            return "{$label}のファイル名が ".($pattern_label ? $pattern_label : $pattern)." ではありません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：ファイル名：条件判定（指定フィールドが指定の値の場合）
     *
     * ex)
     * - [Form::VALID_FILE_NAME_MATCH_IF, other_field, assumed, pattern, Form::APPLY_SAVE]
     * - [Form::VALID_FILE_NAME_MATCH_IF, other_field, assumed, pattern, 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_NAME_MATCH_IF = 'file_name_match_if';

    /**
     * アップロードファイル：ファイル名：条件判定（指定フィールドが指定の値の場合）
     *
     * @param string          $field         検査対象フィールド名
     * @param string          $label         検査対象フィールドのラベル名
     * @param UploadFile|null $value         検索対象フィールドの値
     * @param string          $other_field   対象フィールド名
     * @param string          $assumed       対象フィールドの指定値
     * @param string          $pattern       ファイル名の正規表現
     * @param string|null     $pattern_label ファイル名形式（正規表現）のラベル (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_name_match_if($field, $label, $value, $other_field, $assumed, $pattern, $pattern_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if ($this->{$other_field} != $assumed) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if (!$value->matchFileName($pattern)) {
            return "{$label}のファイル名が ".($pattern_label ? $pattern_label : $pattern)." ではありません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：拡張子
     *
     * ex)
     * - [Form::VALID_FILE_SUFFIX_MATCH, pattern, Form::APPLY_SAVE]
     * - [Form::VALID_FILE_SUFFIX_MATCH, pattern, 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_SUFFIX_MATCH = 'file_suffix_match';

    /**
     * アップロードファイル：拡張子
     *
     * @param string          $field         検査対象フィールド名
     * @param string          $label         検査対象フィールドのラベル名
     * @param UploadFile|null $value         検索対象フィールドの値
     * @param string          $pattern       サフィックスの正規表現
     * @param string|null     $pattern_label サフィックス形式（正規表現）のラベル (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_suffix_match($field, $label, $value, $pattern, $pattern_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if (!$value->matchFileSuffix($pattern)) {
            return "{$label}のファイル拡張子が ".($pattern_label ? $pattern_label : $pattern)." ではありません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：MimeType
     *
     * ex)
     * - [Form::VALID_FILE_MIME_TYPE_MATCH, pattern, Form::APPLY_SAVE]
     * - [Form::VALID_FILE_MIME_TYPE_MATCH, pattern, 'label_of_pattern', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_MIME_TYPE_MATCH = 'file_mime_type_match';

    /**
     * アップロードファイル：MimeType
     *
     * @param string          $field         検査対象フィールド名
     * @param string          $label         検査対象フィールドのラベル名
     * @param UploadFile|null $value         検索対象フィールドの値
     * @param string          $pattern       マイムタイプの正規表現
     * @param string|null     $pattern_label マイムタイプ形式（正規表現）のラベル (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_mime_type_match($field, $label, $value, $pattern, $pattern_label = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if (!$value->matchMimeType($pattern)) {
            return "{$label}の形式が ".($pattern_label ? $pattern_label : $pattern)." ではありません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：WEB画像拡張子
     *
     * 許可される拡張子パターンは以下の通りです
     * - /^(jpe?g|gif|png)$/iu
     *
     * ex)
     * - [Form::VALID_FILE_WEB_IMAGE_SUFFIX, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_WEB_IMAGE_SUFFIX = 'file_web_image_suffix';

    /**
     * アップロードファイル：WEB画像拡張子
     *
     * @param string          $field 検査対象フィールド名
     * @param string          $label 検査対象フィールドのラベル名
     * @param UploadFile|null $value 検索対象フィールドの値
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_web_image_suffix($field, $label, $value)
    {
        return $this->valid_file_suffix_match($field, $label, $value, '/^(jpe?g|gif|png)$/iu', '画像形式[jpg, jpeg, gif, png]');
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：幅：最大値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_MAX_WIDTH, width, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_MAX_WIDTH = 'file_image_max_width';

    /**
     * アップロードファイル：画像：幅：最大値
     *
     * @param string          $field 検査対象フィールド名
     * @param string          $label 検査対象フィールドのラベル名
     * @param UploadFile|null $value 検索対象フィールドの値
     * @param int             $width 最大幅
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_max_width($field, $label, $value, $width)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->width > $width) {
            return "{$label}の幅 [ ".$value->width." px ] を {$width} px 以下にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：幅：指定値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_WIDTH, width, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_WIDTH = 'file_image_width';

    /**
     * アップロードファイル：画像：幅：指定値
     *
     * @param string          $field 検査対象フィールド名
     * @param string          $label 検査対象フィールドのラベル名
     * @param UploadFile|null $value 検索対象フィールドの値
     * @param int             $width 指定幅
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_width($field, $label, $value, $width)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->width != $width) {
            return "{$label}の幅 [ ".$value->width." px ] を {$width} px にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：幅：最小値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_MIN_WIDTH, width, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_MIN_WIDTH = 'file_image_min_width';

    /**
     * アップロードファイル：画像：幅：最小値
     *
     * @param string          $field 検査対象フィールド名
     * @param string          $label 検査対象フィールドのラベル名
     * @param UploadFile|null $value 検索対象フィールドの値
     * @param int             $width 最小幅
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_min_width($field, $label, $value, $width)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->width < $width) {
            return "{$label}の幅 [ ".$value->width." px ] を {$width} px 以上にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：高さ：最大値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_MAX_HEIGHT, height, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_MAX_HEIGHT = 'file_image_max_height';

    /**
     * アップロードファイル：画像：高さ：最大値
     *
     * @param string          $field  検査対象フィールド名
     * @param string          $label  検査対象フィールドのラベル名
     * @param UploadFile|null $value  検索対象フィールドの値
     * @param int             $height 最大高
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_max_height($field, $label, $value, $height)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->height > $height) {
            return "{$label}の高さ [ ".$value->height." px ] を {$height} px 以下にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：高さ：指定値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_HEIGHT, height, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_HEIGHT = 'file_image_height';

    /**
     * アップロードファイル：画像：高さ：指定値
     *
     * @param string          $field  検査対象フィールド名
     * @param string          $label  検査対象フィールドのラベル名
     * @param UploadFile|null $value  検索対象フィールドの値
     * @param int             $height 指定高
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_height($field, $label, $value, $height)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->height != $height) {
            return "{$label}の高さ [ ".$value->height." px ] を {$height} px にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * アップロードファイル：画像：高さ：最小値
     *
     * ex)
     * - [Form::VALID_FILE_IMAGE_MIN_HEIGHT, height, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_FILE_IMAGE_MIN_HEIGHT = 'file_image_min_height';

    /**
     * アップロードファイル：画像：高さ：最小値
     *
     * @param string          $field  検査対象フィールド名
     * @param string          $label  検査対象フィールドのラベル名
     * @param UploadFile|null $value  検索対象フィールドの値
     * @param int             $height 最小高
     * @return string|null null: OK, string: NG = エラーメッセージ
     * @throws InvalidValidateRuleException
     */
    protected function valid_file_image_min_height($field, $label, $value, $height)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if (!($value instanceof UploadFile)) {
            throw new InvalidValidateRuleException("{$label} in not UploadFile.");
        }
        if ($value->height < $height) {
            return "{$label}の高さ [ ".$value->height." px ] を {$height} px 以上にして下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：同じ値(再入力)
     *
     * ex)
     * - [Form::VALID_SAME_AS_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_SAME_AS_INPUTTED = 'same_as_inputted';

    /**
     * フィールド比較：同じ値(再入力)
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_same_as_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if ($value != $this->$other) {
            $labels = $this->labels();
            return "{$label}の値が{$labels[$other]}の値と異なります。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：異なる値
     *
     * ex)
     * - [Form::VALID_NOT_SAME_AS_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_NOT_SAME_AS_INPUTTED = 'not_same_as_inputted';

    /**
     * フィールド比較：異なる値
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_not_same_as_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        if ($value == $this->$other) {
            $labels = $this->labels();
            return "{$label}の値に{$labels[$other]}と同じ値は指定できません。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：数値 (自身 >= 比較対象)
     *
     * ex)
     * - [Form::VALID_GRATER_EQUAL_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_GRATER_EQUAL_INPUTTED = 'grater_equal_inputted';

    /**
     * フィールド比較：数値 (自身 >= 比較対象)
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_grater_equal_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        $sv = doubleval($value);
        $ov = doubleval($this->$other);
        if ($sv < $ov) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}以上の値を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：数値 (自身 > 比較対象)
     *
     * ex)
     * - [Form::VALID_GRATER_THAN_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_GRATER_THAN_INPUTTED = 'grater_than_inputted';

    /**
     * フィールド比較：数値 (自身 > 比較対象)
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_grater_than_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        $sv = doubleval($value);
        $ov = doubleval($this->$other);
        if ($sv <= $ov) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}超過の値を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：数値 (自身 <= 比較対象)
     *
     * ex)
     * - [Form::VALID_LESS_EQUAL_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_LESS_EQUAL_INPUTTED = 'less_equal_inputted';

    /**
     * フィールド比較：数値 (自身 <= 比較対象)
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_less_equal_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        $sv = doubleval($value);
        $ov = doubleval($this->$other);
        if ($sv > $ov) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}以下の値を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：数値 (自身 < 比較対象)
     *
     * ex)
     * - [Form::VALID_LESS_THAN_INPUTTED, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_LESS_THAN_INPUTTED = 'less_than_inputted';

    /**
     * フィールド比較：数値 (自身 < 比較対象)
     *
     * @param string      $field 検査対象フィールド名
     * @param string      $label 検査対象フィールドのラベル名
     * @param string|null $value 検索対象フィールドの値
     * @param string      $other 比較対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_less_than_inputted($field, $label, $value, $other)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $pre_check = $this->valid_number($field, $label, $value);
        if (!empty($pre_check)) {
            return $pre_check;
        }
        $sv = doubleval($value);
        $ov = doubleval($this->$other);
        if ($sv >= $ov) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}超過の値を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：日時：未来日(当日含まず)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_FUTURE_THAN_INPUTTED, 'target_field', Form::APPLY_SAVE]
     * - [Form::VALID_FUTURE_THAN_INPUTTED, 'target_field', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_FUTURE_THAN_INPUTTED = 'future_than_inputted';

    /**
     * フィールド比較：日時：未来日(当日含まず)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $other       比較対象フィールド
     * @param string      $main_format 優先日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_future_than_inputted($field, $label, $value, $other, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = $this->_createDateTime($this->$other, $main_format);
        if (!empty($point) && !($point < $target)) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}よりも未来日を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：日時：未来日(当日含む)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_FUTURE_EQUAL_INPUTTED, 'target_field', Form::APPLY_SAVE]
     * - [Form::VALID_FUTURE_EQUAL_INPUTTED, 'target_field', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_FUTURE_EQUAL_INPUTTED = 'future_equal_inputted';

    /**
     * フィールド比較：日時：未来日(当日含む)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $other       比較対象フィールド
     * @param string      $main_format 優先日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_future_equal_inputted($field, $label, $value, $other, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = $this->_createDateTime($this->$other, $main_format);
        if (!empty($point) && !($point <= $target)) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}よりも未来日(当日含む)を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：日時：過去日(当日含まず)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_PAST_THAN_INPUTTED, 'target_field', Form::APPLY_SAVE]
     * - [Form::VALID_PAST_THAN_INPUTTED, 'target_field', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_PAST_THAN_INPUTTED = 'past_than_inputted';

    /**
     * フィールド比較：日時：過去日(当日含まず)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $other       比較対象フィールド
     * @param string      $main_format 優先日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_past_than_inputted($field, $label, $value, $other, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = $this->_createDateTime($this->$other, $main_format);
        if (!empty($point) && !($target < $point)) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}よりも過去日を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * フィールド比較：日時：過去日(当日含む)
     *
     * 日時系 validation は指定フォーマットによる DateTime への型変換に失敗した場合、型変換失敗のエラーメッセージを個別に設定します。
     * これにより複数の日時系 validation が設定されている項目において型変換に失敗すると同一のエラーメッセージが複数表示されてしまいます。
     * その為、日時系 validation では以下の Form::EXIT_ON_FAILED 付きの validation チェックを事前に実施することが望ましいです。
     * - [Form::VALID_DATETIME, 'main_format', Form::APPLY_SAVE | Form::EXIT_ON_FAILED]
     *
     * ex)
     * - [Form::VALID_PAST_EQUAL_INPUTTED, 'target_field', Form::APPLY_SAVE]
     * - [Form::VALID_PAST_EQUAL_INPUTTED, 'target_field', 'main_format', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_DATETIME
     * @see Form::ACCEPTABLE_DATETIME_FORMAT
     */
    public const VALID_PAST_EQUAL_INPUTTED = 'past_equal_inputted';

    /**
     * フィールド比較：日時：過去日(当日含む)
     *
     * @param string      $field       検査対象フィールド名
     * @param string      $label       検査対象フィールドのラベル名
     * @param string|null $value       検索対象フィールドの値
     * @param string      $other       比較対象フィールド
     * @param string      $main_format 優先日時フォーマット (default: null)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_past_equal_inputted($field, $label, $value, $other, $main_format = null)
    {
        if ($this->_empty($value)) {
            return null;
        }
        $target = $this->_createDateTime($value, $main_format);
        if (empty($target)) {
            return "{$label}は".($main_format ? " {$main_format} 形式（例：".(new \DateTime())->format($main_format)."）" : "正しい日付／日時")." で入力して下さい。";
        }
        $point = $this->_createDateTime($this->$other, $main_format);
        if (!empty($point) && !($target <= $point)) {
            $labels = $this->labels();
            return "{$label}は{$labels[$other]}よりも過去日(当日含む)を指定して下さい。";
        }
        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * サブフォーム：重複不可
     *
     * ex)
     * - [Form::VALID_SUB_FORM_UNIQUE, 'target_field', Form::APPLY_SAVE]
     *
     * @var string
     *
     * @see Form::VALID_UNIQUE          単一フィールドによる multiple セレクトの重複チェック
     * @see Form::VALID_RELATION_UNIQUE 複数フィールドに跨る重複チェック
     */
    public const VALID_SUB_FORM_UNIQUE = 'sub_form_unique';

    /**
     * サブフォーム：重複不可
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param Form[]|null $value  検索対象フィールドの値
     * @param string      $target サブフォームの対象フィールド
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_sub_form_unique($field, $label, $value, $target)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        $sub_values = [];
        $sub_labels = [];
        foreach ($value as $sf) {
            if (empty($sub_labels)) {
                $sub_labels = $sf->labels();
            }
            $sub_value = static::_get($sf, $target);
            if ($this->_empty($sub_value)) {
                continue;
            }
            if (is_array($sub_value)) {
                $sub_values = array_merge($sub_values, $sub_value);
            } else {
                $sub_values[] = $sub_value;
            }
        }

        $duplicate = $this->_duplicate($sub_values);
        if (!empty($duplicate)) {
            $sub_label = static::_get($sub_labels, $target, $target);
            return "{$label}：{$sub_label}にはそれぞれ異なる値を入力して下さい。[ ".join(',', $duplicate)." ] が重複しています。";
        }

        return null;
    }

    //--------------------------------------------------------------------------
    /**
     * サブフォーム：連番
     *
     * 指定のフィールドが指定番号からの連番で構成されているかチェックします。
     * - start のデフォルトは 1
     * - step のデフォルトは 1
     *
     * ex)
     * - [Form::VALID_SUB_FORM_SERIAL_NO, 'target_field', Form::APPLY_SAVE]
     * - [Form::VALID_SUB_FORM_SERIAL_NO, 'target_field', start, Form::APPLY_SAVE]
     * - [Form::VALID_SUB_FORM_SERIAL_NO, 'target_field', start, step, Form::APPLY_SAVE]
     *
     * @var string
     */
    public const VALID_SUB_FORM_SERIAL_NO = 'sub_form_serial_no';

    /**
     * サブフォーム：連番
     *
     * @param string      $field  検査対象フィールド名
     * @param string      $label  検査対象フィールドのラベル名
     * @param Form[]|null $value  検索対象フィールドの値
     * @param string      $target サブフォームの対象フィールド
     * @param int         $start  開始位置 (default: 1)
     * @param int         $step   ステップ数 (default: 1)
     * @return string|null null: OK, string: NG = エラーメッセージ
     */
    protected function valid_sub_form_serial_no($field, $label, $value, $target, $start = 1, $step = 1)
    {
        if ($this->_empty($value)) {
            return null;
        }
        assert(!is_null($value));
        $sub_values = [];
        $sub_labels = [];
        foreach ($value as $sf) {
            if (empty($sub_labels)) {
                $sub_labels = $sf->labels();
            }
            $sub_value = static::_get($sf, $target);
            if ($this->_empty($sub_value)) {
                continue;
            }
            if (is_array($sub_value)) {
                $sub_values = array_merge($sub_values, $sub_value);
            } else {
                $sub_values[] = $sub_value;
            }
        }

        sort($sub_values, SORT_NUMERIC);
        $expect = $start;
        foreach ($sub_values as $v) {
            if ($expect != $v) {
                $sub_label = static::_get($sub_labels, $target, $target);
                return "{$label}：{$sub_label}が {$start} から始まる {$step} 刻みの連番になっていません。";
            }
            $expect += $step;
        }

        return null;
    }
}

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 アップロードファイル クラス（Form付帯クラス）
 *
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class UploadFile
{
    //** @var int サイズ指定計算用の定数：GB */
    public const GB = 1073741824;
    //** @var int サイズ指定計算用の定数：MB */
    public const MB = 1048576;
    //** @var int サイズ指定計算用の定数：KB */
    public const KB = 1024;

    /**
     * フォーム名称
     * @var string
     */
    public $form_name;

    /**
     * フィールド名称
     * @var string
     */
    public $field;

    /**
     * アップロードファイル：ファイル名
     * @var string
     */
    public $name;

    /**
     * アップロードファイル：MimeType
     * @var string
     */
    public $type;

    /**
     * アップロードファイル：サイズ
     * @var int
     */
    public $size;

    /**
     * アップロードファイル：テンポラリファイル名
     * @var string
     */
    public $tmp_name;

    /**
     * アップロードファイル：エラー情報
     * @var string|string[]
     */
    public $error;

    /**
     * アップロードファイルサフィックス
     * @var string|null
     */
    public $suffix;

    /**
     * 幅（画像の場合のみ）
     * @var int|null
     */
    public $width;

    /**
     * 高さ（画像の場合のみ）
     * @var int|null
     */
    public $height;

    /**
     * アップロードファイルオブジェクトを構築します。
     *
     * @param string               $form_name フォーム名
     * @param string               $field    フィールド名
     * @param array<string, mixed> $file     ファイルリクエスト(=$_FILE) 又はそれに類する情報
     * @return UploadFile
     */
    public function __construct($form_name, $field, $file)
    {
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = isset($file[$key]) ? $file[$key] : null ;
        }

        $this->form_name = $form_name;
        $this->field     = $field;
        $this->suffix    = null;
        if (!empty($this->name)) {
            $pi           = pathinfo($this->name);
            $this->suffix = isset($pi['extension']) ? strtolower($pi['extension']) : null ;
        }
        if (!empty($this->type) && strrpos($this->type, "image/", -strlen($this->type)) !== false) {
            try {
                $imagesize    = getimagesize($this->tmp_name);
                $this->width  = isset($imagesize[0]) ? $imagesize[0] : null ;
                $this->height = isset($imagesize[1]) ? $imagesize[1] : null ;
            } catch (\ErrorException $e) {
                $this->width  = null ;
                $this->height = null ;
            }
        }
    }

    /**
     * アップロードファイルデータが空か否かチェックします。
     *
     * @return bool true : 空である／false : 空でない
     */
    public function isEmpty()
    {
        return $this->size == 0 || empty($this->tmp_name);
    }

    /**
     * エラーがあるかチェックします。
     *
     * @return bool true : エラー有り／false : エラー無し
     */
    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * ファイル名が指定の条件にマッチするかチェックします。
     *
     * @param string $pattern ファイル名を表す正規表現
     * @return bool true : マッチ／false : アンマッチ
     */
    public function matchFileName($pattern)
    {
        return preg_match($pattern, $this->name) === 1;
    }

    /**
     * 拡張子が指定の条件にマッチするかチェックします。
     *
     * @param  string $pattern 拡張子を表す正規表現
     * @return bool true : マッチ／false : アンマッチ
     */
    public function matchFileSuffix($pattern)
    {
        return $this->suffix !== null && preg_match($pattern, $this->suffix) === 1;
    }

    /**
     * MimeType が指定の条件にマッチするかチェックします
     *
     * @param string $pattern MimeType を表す正規表現
     * @return bool true : マッチ／false : アンマッチ
     */
    public function matchMimeType($pattern)
    {
        return preg_match($pattern, $this->type) === 1;
    }

    /**
     * アップロードデータを確認領域に保存します。
     * ※セーブしたアップロードファイル情報はセッションに保存され、UploadFile::load で再読み込みできます。
     *
     * @param string $dir テンポラリディレクトリ
     * @return string|null 保存されたファイル名
     */
    public function saveTemporary($dir)
    {
        $fileId = self::_fileId($this->form_name, $this->field);
        if ($this->isEmpty()) {
            $_SESSION[$fileId] = serialize($this);
            return null;
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = "{$fileId}.".$this->suffix;
        $path = "{$dir}/{$file}";

        if (is_uploaded_file($this->tmp_name)) {
            move_uploaded_file($this->tmp_name, $path);
            chmod($path, 0664);
        }

        $this->tmp_name    = $path;
        $_SESSION[$fileId] = serialize($this);

        return $file;
    }

    /**
     * 公開用のファイル名を取得します。
     *
     * @param string $baseName 公開用ファイルベース名 (デフォルト： フォームフィールド名)
     * @return string 公開ファイル名
     */
    public function getPublishFileName($baseName = null)
    {
        return empty($baseName) ? "{$this->field}.{$this->suffix}" : "{$baseName}.{$this->suffix}" ;
    }

    /**
     * アップロードデータを公開領域に保存します。
     *
     * @param string $dir      公開ディレクトリ
     * @param string $baseName 公開用ファイルベース名
     * @return string|null 公開ファイル名
     */
    public function publish($dir, $baseName = null)
    {
        if ($this->isEmpty()) {
            return null;
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $this->getPublishFileName($baseName);
        $path = "{$dir}/{$file}";
        rename($this->tmp_name, $path);
        chmod($path, 0664);
        unset($_SESSION[self::_fileId($this->form_name, $this->field)]);

        return $file;
    }

    /**
     * アップロードデータを削除します。
     *
     * @return void
     */
    public function remove()
    {
        unset($_SESSION[self::_fileId($this->form_name, $this->field)]);

        if (file_exists($this->tmp_name)) {
            unlink($this->tmp_name);
        }
    }

    /**
     * 現在作業中のテンポラリデータが存在するかチェックします。
     *
     * @param string $form_name  フォーム名
     * @param string $field_name フィールド名
     * @return bool true: 存在する／false: 存在しない
     */
    public static function exists($form_name, $field_name)
    {
        return isset($_SESSION[self::_fileId($form_name, $field_name)]);
    }

    /**
     * 現在作業中のテンポラリファイルデータをロードします。
     *
     * @param string $form_name  フォーム名
     * @param string $field_name フィールド名
     * @return UploadFile
     */
    public static function load($form_name, $field_name)
    {
        return self::exists($form_name, $field_name) ? unserialize($_SESSION[self::_fileId($form_name, $field_name)]) : new UploadFile($form_name, $field_name, []) ;
    }

    /**
     * 空のアップロードファイルデータを生成します。
     *
     * @param string $form_name  フォーム名
     * @param string $field_name フィールド名
     * @return UploadFile 空のアップロードファイル
     */
    public static function createEmpty($form_name, $field_name)
    {
        return new UploadFile($form_name, $field_name, []);
    }

    /**
     * テンポラリデータ用のファイルIDを取得します。
     *
     * @param string $form_name  フォーム名
     * @param string $field_name フィールド名
     * @return string テンポラリ用ファイルID
     */
    private static function _fileId($form_name, $field_name)
    {
        $form_name = str_replace('\\', '_', $form_name);
        return "SFLF_UPLOAD_FILE_{$form_name}_{$field_name}_".session_id();
    }
}

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 Validation関連エラー クラス（Form付帯クラス）
 *
 * Validation ルールの記述間違いなどに関するエラー
 *
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class InvalidValidateRuleException extends \RuntimeException
{
    /**
     * @param string          $message  エラーメッセージ
     * @param int             $code     エラーコード (default: 0)
     * @param \Throwable|null $previous 原因例外 (default: null)
     * @return InvalidValidateRuleException
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
