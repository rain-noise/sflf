<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 Validation機能付きフォーム 基底クラス
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
 *     
 *     protected function labels() {
 *         return array(
 *              'user_id'          => '会員ID'
 *             ,'name'             => '氏名'
 *             ,'mail_address'     => 'メールアドレス'
 *             ,'password'         => 'パスワード'
 *             ,'password_confirm' => 'パスワード(確認)'
 *             ,'avatar'           => 'アバター画像'
 *         );
 *     }
 *     
 *     protected function files() {
 *         return array('avatar');
 *     }
 *     
 *     protected function rules() {
 *         return array(
 *              'user_id' => array(
 *                   array(Form::VALID_REQUIRED, Form::APPLY_REFER | Form::EXIT_ON_FAILED)
 *             )
 *             ,'name' => array(
 *                   array(Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED)
 *                  ,array(Form::VALID_MAX_LENGTH, 20, Form::APPLY_SAVE)
 *                  ,array(Form::VALID_DEPENDENCE_CHAR, Form::APPLY_SAVE)
 *             )
 *             ,'mail_address' => array(
 *                  array(Form::VALID_REQUIRED, Form::APPLY_SAVE | Form::EXIT_ON_FAILED)
 *                 ,array(Form::VALID_MAIL_ADDRESS, Form::APPLY_SAVE)
 *                 ,array('mail_address_exists', Form::APPLY_SAVE | Form::EXIT_IF_ALREADY_HAS_ERROR) // カスタム Validation の実行
 *             )
 *             ,'password' => array(
 *                  array( Form::VALID_REQUIRED, Form::APPLY_CREATE | Form::EXIT_ON_FAILED)
 *                 ,array( Form::VALID_MIN_LENGTH, 8, Form::APPLY_SAVE )
 *             )
 *             ,'password_confirm' => array(
 *                  array( Form::VALID_REQUIRED, Form::APPLY_CREATE | Form::EXIT_ON_FAILED)
 *                 ,array( Form::VALID_FIELD_SAME, 'password', Form::APPLY_SAVE )
 *             )
 *             ,'avatar' => array(
 *                  array(Form::VALID_FILE_SIZE, 2 * UploadFile::MB, Form::APPLY_SAVE)
 *                 ,array(Form::VALID_FILE_WEB_IMAGE_SUFFIX, Form::APPLY_SAVE)
 *             )
 *         );
 *     }
 *     
 *     // カスタム Validation の定義
 *     protected function valid_mail_address_exists($field, $label, $value) {
 *         if($this->_empty($value)) { return null; }
 *         if(Dao::exists(
 *              "SELECT * FROM user WHERE mail_address=:mail_address" . (!empty($this->user_id) ? " AND user_id<>:user_id" : "")
 *             ,array(':mail_address' => $value, ':user_id' => $this->user_id))
 *         ) {
 *             return "ご指定の{$label}は既に存在しています。";
 *         }
 *         return null;
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
 * $form->avatar->saveTemporary("/path/to/temporary/dir");
 * 
 * // for complete action
 * $user = $form->describe(UserEntity::class);
 * $user->avatar_file   = $form->avatar->publish("/path/to/publish/dir/{$userId}", "avater");
 * $user->registered_at = new DateTime();
 * $userId = Dao::insert('user', $user);
 * 
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.hiddens.php    hiddenタグ出力用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/function.errors.php     エラーメッセージ出力用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/block.if_errors.php     エラー有無分岐用 Smarty タグ
 * @see https://github.com/rain-noise/sflf/blob/master/src/main/php/extensions/smarty/plugins/block.unless_errors.php エラー有無分岐用 Smarty タグ
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
abstract class Form
{
	// ----------------------------------------------------
	// オプションフラグ定義
	// ----------------------------------------------------
	// CRUD 分類による validation 適用範囲
	const APPLY_CREATE = 1;
	const APPLY_READ   = 2;
	const APPLY_UPDATE = 4;
	const APPLY_DELETE = 8;
	
	const APPLY_SAVE  = self::APPLY_CREATE | self::APPLY_UPDATE;
	const APPLY_REFER = self::APPLY_READ | self::APPLY_UPDATE | self::APPLY_DELETE;
	const APPLY_ALL   = self::APPLY_CREATE |self::APPLY_READ | self::APPLY_UPDATE | self::APPLY_DELETE;
	
	// validation 中断挙動に関するオプション
	const EXIT_ON_FAILED            = 1024; // このチェックがエラーになった場合、以降のチェックを中断する
	const EXIT_ON_SUCCESS           = 2048; // このチェックが通った場合、以降のチェックをスキップする
	const EXIT_IF_ALREADY_HAS_ERROR = 4096; // 既にエラーが存在する場合、このチェックを含む以降のチェックを中断する
	
	// validation 中断用の特殊コマンド
	const VALIDATE_COMMAND_EXIT = "@EXIT@"; // この値が返ると以降の validate を 中断 する
	
	/**
	 * リクエストデータ又はDtoオブジェクトから自身のインスタンス変数に値をコピーします。
	 * 
	 * @param array|obj $src       コピー元データ。リクエストデータ(=$_REQUEST)又はDtoオブジェクト
	 * @param array     $files     アップロードファイル情報(=$_FILES)
	 * @param function  $converter コンバータの戻り値が設定されます ： デフォルト function($field, $defined, $src, $value, $form, $origin) { return $defined ? $value : $origin ; } 
	 */
	public function popurate($src, $files = null, $converter = null) {
		if(empty($src) && empty($files)) { return; }
		
		if(empty($converter)) {
			$converter = function($field, $defined, $src, $value, $form, $origin) { return $defined ? $value : $origin ; };
		}
		
		$clazz          = get_class($this);
		$fileFormFields = $this->files();
		foreach ($this AS $field => $origin) {
			$this->$field = $converter($field, $this->_has($src, $field), $src, $this->_get($src, $field), $this, $origin);
			
			if(isset($files[$field])) {
				$this->$field = new UploadFile($clazz, $field, $files[$field]);
			} else {
				if(UploadFile::exists($clazz, $field)) {
					$this->$field = UploadFile::load($clazz, $field);
				} else if(in_array($field, $fileFormFields) && empty($this->$field)) {
					$this->$field = UploadFile::createEmpty($clazz, $field);
				}
			}
		}
	}
	
	/**
	 * 指定の DTO オブジェクトに、自身の値をコピーします。
	 *
	 * @param obj      $dto コピー対象DTOオブジェクト
	 * @param function  $converter コンバータの戻り値が設定されます ： デフォルト function($field, $defined, $form, $value, $dto, $origin) { return $defined ? $value : $origin ; } 
	 */
	public function inject($dto, $converter = null) {
		if(empty($converter)) {
			$converter = function($field, $defined, $form, $value, $dto, $origin) { return $defined ? $value : $origin ; };
		}
		
		$thisClazz = get_class($this);
		foreach ($dto AS $field => $origin) {
			$dto->$field = $converter($field, property_exists($thisClazz, $field), $this, $this->_get($this, $field), $dto, $origin);
		}
		
		return $dto;
	}
	
	/**
	 * 指定の DTO オブジェクトを生成し、自身の値をコピーします。
	 *
	 * @param string    $clazz     DTOオブジェクトクラス名
	 * @param function  $converter コンバータの戻り値が設定されます ： デフォルト function($field, $defined, $form, $value, $dto, $origin) { return $defined ? $value : $origin ; } 
	 */
	public function describe($clazz, $converter = null) {
		return $this->inject(new $clazz(), $converter);
	}
	
	/**
	 * 配列又はオブジェクトから値を取得します。
	 * 
	 * @param  array|obj $obj     配列 or オブジェクト
	 * @param  mixed     $key     キー名
	 * @param  mixed     $default デフォルト値
	 * @return mixed 値
	 */
	private function _get($obj, $key, $default = null) {
		if($obj == null) { return $default; }
		if(is_array($obj)) {
			if(!isset($obj[$key])) { return $default; }
			return $obj[$key] === null ? $default : $obj[$key] ;
		}
		if(!($obj instanceof stdClass)) {
			$clazz = get_class($obj);
			if(!property_exists($clazz, $key)) { return $default; }
		}
		return $obj->$key === null ? $default : $obj->$key ;
	}
	
	/**
	 * 配列又はオブジェクトが指定のプロパティを持つか判定します
	 * 
	 * @param  array|obj $obj     配列 or オブジェクト
	 * @param  mixed     $key     キー名
	 * @return boolean true : 持つ, false : 持たない
	 */
	private function _has($obj, $key) {
		if($obj == null) { return false; }
		if(is_array($obj)) {
			return isset($obj[$key]);
		}
		if(!($obj instanceof stdClass)) {
			$clazz = get_class($obj);
			return property_exists($clazz, $key);
		}
		return isset($obj->$key);
	}
	
	/**
	 * 指定のルールに従って validation を実施します。
	 * 
	 * @param  array $errors エラー情報格納オブジェクト
	 * @param  int   $option Form::APPLY_* 及び Form::EXIT_* の Form オプションクラス定数の論理和
	 * @return void
	 * @throws InvalidValidateRuleException
	 */
	public function validate(&$errors, $apply) {
		$this->before($apply);
		
		$labels = $this->labels();
		$rules  = $this->rules();
		if(empty($rules)) {
			$this->complete($apply);
			return;
		}
		
		$hasError = false;
		$clazz = get_class($this);
		foreach ($rules AS $target => $validations) {
			foreach ($validations AS $validate) {
				// 定義内容チェック
				$size = count($validate);
				if($size < 2) { throw new InvalidValidateRuleException("Validate rule has at least 2 or more options [ 'check_name', Form::APPLY_* | Form::EXIT_* ]"); }
				
				// Validate 内容取得
				$check = $validate[0];
				
				// 引数取得
				$args   = array();
				$args[] = $target ;
				$args[] = isset($labels[$target]) ? $labels[$target] : $target ;
				$args[] = property_exists($clazz, $target) ? $this->$target : null ;
				if($size > 2) {
					$args = array_merge($args, array_slice($validate, 1, $size-2));
				}
				
				// オプション取得
				$option = $validate[$size-1];
				
				// オプション処理
				if(!($option & $apply)) { continue; }
				if($option & Form::EXIT_IF_ALREADY_HAS_ERROR && isset($errors[$target]) && !empty($errors[$target])) { break; }
				
				// Validation 実行
				$method  = "valid_{$check}";
				$invoker = new ReflectionMethod($clazz, $method);
				$invoker->setAccessible(true);
				
				$error = $invoker->invokeArgs($this, $args);
				if(!empty($error)) {
					if($error == self::VALIDATE_COMMAND_EXIT) { break; }
					
					$hasError = true;
					if(!isset($errors[$target])) {
						$errors[$target] = array();
					}
					if(is_array($error)) {
						$errors[$target] = array_merge($errors[$target], $error);
					} else {
						$errors[$target][] = $error;
					}
					
					if($option & Form::EXIT_ON_FAILED) { break; }
				} else {
					if($option & Form::EXIT_ON_SUCCESS) { break; }
				}
			}
		}
		
		if($hasError) {
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
	 * @return array フィールド名 と ラベル の連想配列
	 */
	abstract protected function labels();
	
	/**
	 * ファイルアップロードフォーム名を返します。
	 * ※詳細はクラスコメントの【使い方】を参照
	 * 
	 * @return array ファイルアップロードフォーム名の配列
	 */
	protected function files() {
		return array();
	}
	
	/**
	 * Validate ルールを返します。
	 * ※詳細はクラスコメントの【使い方】を参照
	 * 
	 * @return array フィールド名 と ルール配列 の連想配列
	 */
	abstract protected function rules();
	
	/**
	 * Validate 実行前処理
	 * ※必要に応じてサブクラスでオーバーライド
	 * 
	 * @param  $apply 適用オプション
	 * @return void
	 */
	protected function before($apply) {
		// 何もしない
	}
	
	/**
	 * Validate 成功時処理
	 * ※必要に応じてサブクラスでオーバーライド
	 * 
	 * @param  $apply 適用オプション
	 * @return void
	 */
	protected function complete($apply) {
		// 何もしない
	}
	
	/**
	 * Validate 失敗時処理
	 * ※必要に応じてサブクラスでオーバーライド
	 * 
	 * @param  $errors エラー情報
	 * @param  $apply  適用オプション
	 * @return void
	 */
	protected function failed(&$errors, $apply) {
		// 何もしない
	}
	
	//#########################################################################
	// 以下、validation メソッド定義
	//#########################################################################
	
	//-------------------------------------------------------------------------
	// 未入力の定義
	//-------------------------------------------------------------------------
	protected function _empty($value) {
		if($value instanceof UploadFile) { return $value->isEmpty(); }
		return $value == null || $value == '';
	}
	
	//-------------------------------------------------------------------------
	// 処理中断：指定のフィールドが空の場合
	//-------------------------------------------------------------------------
	const VALID_EXIT_EMPTY = 'exit_empty';
	protected function valid_exit_empty($field, $label, $value, $other) {
		if($this->_empty($this->$other)) { return self::VALIDATE_COMMAND_EXIT; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 処理中断：指定のフィールドが空でない場合
	//-------------------------------------------------------------------------
	const VALID_EXIT_NOT_EMPTY = 'exit_not_empty';
	protected function valid_exit_not_empty($field, $label, $value, $other) {
		if(!$this->_empty($this->$other)) { return self::VALIDATE_COMMAND_EXIT; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 処理中断：指定のフィールドが指定の値の場合
	//-------------------------------------------------------------------------
	const VALID_EXIT_IF = 'exit_if';
	protected function valid_exit_if($field, $label, $value, $other, $except) {
		if($this->$other == $except) { return self::VALIDATE_COMMAND_EXIT; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// SKIP：指定のフィールドが指定の値以外の場合
	//-------------------------------------------------------------------------
	const VALID_EXIT_UNLESS = 'exit_unless';
	protected function valid_exit_unless($field, $label, $value, $other, $except) {
		if($this->$other != $except) { return self::VALIDATE_COMMAND_EXIT; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// SKIP：指定のフィールドが指定の値の何れかの場合
	//-------------------------------------------------------------------------
	const VALID_EXIT_IN = 'exit_in';
	protected function valid_exit_in($field, $label, $value, $other, $excepts) {
		if(in_array($this->$other, $excepts)) { return self::VALIDATE_COMMAND_EXIT; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 必須入力
	//-------------------------------------------------------------------------
	const VALID_REQUIRED = 'required';
	protected function valid_required($field, $label, $value) {
		if($this->_empty($value)) { return "{$label}を入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 必須入力(指定フィールドが入力されている場合)
	//-------------------------------------------------------------------------
	const VALID_REQUIRED_DEPEND = 'required_depend';
	protected function valid_required_depend($field, $label, $value, $depends) {
		$isset = false;
		foreach (explode(',', $depends) AS $depend) {
			$isset |= !$this->_empty($this->$depend);
		}
		if(!$isset) { return; }
		if($this->_empty($value)) { return "{$label}を入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 必須入力(指定フィールドが入力されている場合)
	//-------------------------------------------------------------------------
	const VALID_REQUIRED_AT_LEAST = 'required_at_least';
	protected function valid_required_at_least($field, $label, $value, $count, $depends) {
		$labels       = $this->labels();
		$dependsLabel = array();
		$setCount     = 0;
		foreach (explode(',', $depends) AS $depend) {
			if(!$this->_empty($this->$depend)) { $setCount++; }
			$dependsLabel[] = isset($labels[$depend]) ? $labels[$depend] : $depend ;
		}
		if($setCount < $count) { return join(', ', $dependsLabel)." の内 {$count} 項目以上を入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 必須入力(指定フィールが指定の値の場合)
	//-------------------------------------------------------------------------
	const VALID_REQUIRED_IF = 'required_if';
	protected function valid_required_if($field, $label, $value, $depend, $except) {
		if($this->_empty($this->$depend) || $this->$depend != $except) { return; }
		if($this->_empty($value)) { return "{$label}を入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 必須入力(指定フィールが指定の値以外の場合)
	//-------------------------------------------------------------------------
	const VALID_REQUIRED_UNLESS = 'required_unless';
	protected function valid_required_unless($field, $label, $value, $depend, $except) {
		if($this->_empty($this->$depend) || $this->$depend == $except) { return; }
		if($this->_empty($value)) { return "{$label}を入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 空欄必須(指定フィールが指定の値の場合)
	//-------------------------------------------------------------------------
	const VALID_EMPTY_IF = 'empty_if';
	protected function valid_empty_if($field, $label, $value, $depend, $except) {
		if($this->_empty($this->$depend) || $this->$depend != $except) { return; }
		if(!$this->_empty($value)) { return "{$label}を空にして下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 空欄必須(指定フィールが指定の値以外の場合)
	//-------------------------------------------------------------------------
	const VALID_EMPTY_UNLESS = 'empty_unless';
	protected function valid_empty_unless($field, $label, $value, $depend, $except) {
		if($this->_empty($this->$depend) || $this->$depend == $except) { return; }
		if(!$this->_empty($value)) { return "{$label}を空にして下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 正規表現
	//-------------------------------------------------------------------------
	const VALID_REGEX = 'regex';
	protected function valid_regex($field, $label, $value, $pattern, $patternLabel) {
		if($this->_empty($value)) { return null; }
		if(!preg_match($pattern, $value)) { return "{$label}は{$patternLabel}で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 文字列長：最大
	//-------------------------------------------------------------------------
	const VALID_MAX_LENGTH = 'max_length';
	protected function valid_max_length($field, $label, $value, $length) {
		if($this->_empty($value)) { return null; }
		if(mb_strlen($value) > $length) { return "{$label}は{$length}文字以下で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 文字列長：一致
	//-------------------------------------------------------------------------
	const VALID_LENGTH = 'length';
	protected function valid_length($field, $label, $value, $length) {
		if($this->_empty($value)) { return null; }
		if(mb_strlen($value) != $length) { return "{$label}は{$length}文字で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 文字列長：最小
	//-------------------------------------------------------------------------
	const VALID_MIN_LENGTH = 'min_length';
	protected function valid_min_length($field, $label, $value, $length) {
		if($this->_empty($value)) { return null; }
		if(mb_strlen($value) < $length) { return "{$label}は{$length}文字以上で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 数値
	//-------------------------------------------------------------------------
	const VALID_NUMBER = 'number';
	protected function valid_number($field, $label, $value) {
		if($this->_empty($value)) { return null; }
		return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]*[\.]?[0-9]+$/u", "数値");
	}
	
	//-------------------------------------------------------------------------
	// 整数
	//-------------------------------------------------------------------------
	const VALID_INTEGER = 'integer';
	protected function valid_integer($field, $label, $value) {
		if($this->_empty($value)) { return null; }
		return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]+$/u", "整数");
	}
	
	//-------------------------------------------------------------------------
	// 実数（小数点N桁まで）
	//-------------------------------------------------------------------------
	const VALID_FLOAT = 'float';
	protected function valid_float($field, $label, $value, $decimal) {
		if($this->_empty($value)) { return null; }
		return $this->valid_regex($field, $label, $value, "/^[+-]?[0-9]+([\.][0-9]{0,{$decimal}})?$/u", "実数（小数点{$decimal}桁まで）");
	}
	
	
	//-------------------------------------------------------------------------
	// 整数範囲：最大
	//-------------------------------------------------------------------------
	const VALID_MAX_RANGE = 'max_range';
	protected function valid_max_range($field, $label, $value, $max) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		if(doubleval($value) > $max) { return "{$label}は{$max}以下で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 整数範囲：最小
	//-------------------------------------------------------------------------
	const VALID_MIN_RANGE = 'min_range';
	protected function valid_min_range($field, $label, $value, $min) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		if(doubleval($value) < $min) { return "{$label}は{$min}以上で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// メールアドレス
	//-------------------------------------------------------------------------
	const VALID_MAIL_ADDRESS = 'mail_address';
	protected function valid_mail_address($field, $label, $value) {
		if($this->_empty($value)) { return null; }
		if(!filter_var($value, FILTER_VALIDATE_EMAIL)) { return "{$label}はメールアドレス形式で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// URL
	//-------------------------------------------------------------------------
	const VALID_URL = 'url';
	protected function valid_url($field, $label, $value) {
		return $this->valid_regex($field, $label, $value, "/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/u", "URL形式");
	}
	
	//-------------------------------------------------------------------------
	// IPv4アドレス
	//-------------------------------------------------------------------------
	const IP_V4_PATTERN       = '/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/u';
	const VALID_IP_V4_ADDRESS = 'ip_v4_address';
	protected function valid_ip_v4_address($field, $label, $value) {
		return $this->valid_regex($field, $label, $value, self::IP_V4_PATTERN, 'IPアドレス(CIDR)形式');
	}
	
	//-------------------------------------------------------------------------
	// IPv4アドレスリスト(デフォルト区切り：改行)
	//-------------------------------------------------------------------------
	const VALID_IP_V4_ADDRESS_LIST = 'ip_v4_address_list';
	protected function valid_ip_v4_address_list($field, $label, $value, $delimiter=PHP_EOL) {
		if($this->_empty($value)) { return null; }
		$errors = array();
		foreach (explode($delimiter, $value) AS $i => $ip) {
			$ip = trim($ip);
			if(!empty($ip) && !preg_match(self::IP_V4_PATTERN, $ip)) {
				$errors[] = ($i+1)." 行目の{$label} [ {$ip} ] はIPアドレス(CIDR)形式で入力して下さい。";
			}
		}
		return $errors;
	}
	
	//-------------------------------------------------------------------------
	// 半角数字
	//-------------------------------------------------------------------------
	const VALID_HALF_DIGIT = 'half_digit';
	protected function valid_half_digit($field, $label, $value) {
		return $this->valid_regex($field, $label, $value, "/^[0-9]+$/u", "半角数字");
	}
	
	//-------------------------------------------------------------------------
	// 半角英字
	//-------------------------------------------------------------------------
	const VALID_HALF_ALPHA = 'half_alpha';
	protected function valid_half_alpha($field, $label, $value) {
		return $this->valid_regex($field, $label, $value, "/^[a-zA-Z]+$/u", "半角英字");
	}
	
	//-------------------------------------------------------------------------
	// 半角英数字
	//-------------------------------------------------------------------------
	const VALID_HALF_ALPHA_DIGIT = 'half_digit_num';
	protected function valid_half_alpha_digit($field, $label, $value) {
		return $this->valid_regex($field, $label, $value, "/^[a-zA-Z0-9]+$/u", "半角英数字");
	}
	
	//-------------------------------------------------------------------------
	// 半角英数記号(デフォルト記号：!"#$%&'()*+,-./:;<=>?@[\]^_`{|}~ )
	//-------------------------------------------------------------------------
	const VALID_HALF_ALPHA_DIGIT_MARK = 'half_alpha_digit_mark';
	protected function valid_half_alpha_digit_mark($field, $label, $value, $mark='!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~ ') {
		return $this->valid_regex($field, $label, $value, "/^[a-zA-Z0-9".preg_quote($mark)."]+$/u", "半角英数記号（{$mark}を含む）");
	}
	
	//-------------------------------------------------------------------------
	// 全角ひらがな
	//-------------------------------------------------------------------------
	const VALID_HIRAGANA = 'hiragana';
	protected function valid_hiragana($field, $label, $value, $extra='') {
		return $this->valid_regex($field, $label, $value, "/^[\p{Hiragana}ー{$extra}]+$/u", "全角ひらがな");
	}
	
	//-------------------------------------------------------------------------
	// 全角カタカナ
	//-------------------------------------------------------------------------
	const VALID_FULL_KANA = 'full_kana';
	protected function valid_full_kana($field, $label, $value, $extra='') {
		return $this->valid_regex($field, $label, $value, "/^[ァ-ヾ{$extra}]+$/u", "全角カタカナ");
	}
	
	//-------------------------------------------------------------------------
	// 機種依存文字
	//-------------------------------------------------------------------------
	const VALID_DEPENDENCE_CHAR = 'dependence_char';
	protected function valid_dependence_char($field, $label, $value, $encode='sjis-win') {
		if($this->_empty($value)) { return null; }
		$dependences = $this->_checkDependenceChar($value, $encode);
		if(!empty($dependences)) { return "{$label}に機種依存文字 [".join(", ",$dependences)."] が含まれます。機種依存文字を除去又は代替文字に変更して下さい。"; }
		return null;
	}
	
	/**
	 * 機種依存文字を抽出します。
	 * 
	 * @param string $text   チェック対象文字列
	 * @param string $encode 機種依存判定用文字コード - デフォルト 'sjis-win'
	 */
	private function _checkDependenceChar($text, $encode='sjis-win') {
		$org  = $text;
		$conv = mb_convert_encoding(mb_convert_encoding($text,$encode,'UTF-8'),'UTF-8',$encode);
		if(strlen($org) != strlen($conv)) {
			$diff = array_diff(preg_split("//u", $org, -1, PREG_SPLIT_NO_EMPTY), preg_split("//u", $conv, -1, PREG_SPLIT_NO_EMPTY));
			return $diff;
		}
		
		return array();
	}
	
	//-------------------------------------------------------------------------
	// NGワード
	// $ng_words は 配列 又は ワードリストのファイルパス
	// ワードリストは改行区切りで定義。
	// 
	//  - 英数字は半角小文字
	//  - 日本語は全角カタカナと漢字
	//
	// で登録すると曖昧検索になります。
	// なお、短い単語は ^〇〇$ と定義することで全体一致検索にできます
	//-------------------------------------------------------------------------
	const VALID_NG_WORD = 'ng_word';
	protected function valid_ng_word($field, $label, $value, $ng_words, $separateLetterPattern='[\p{Common}]', $blankLetterPattern='[\p{M}\p{S}〇*＊_＿]', $blankApplyLength = 3, $blankApplyRatio = 0.4) {
		if($this->_empty($value)) { return null; }
		if(!is_array($ng_words)) {
			$ng_words = file($ng_words, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		}
		
		// 伏字の考慮
		$len             = mb_strlen($value);
		$blankLeterIndex = array();
		if($len >= $blankApplyLength) {
			$index = 0;
			foreach (preg_split("//u", $value, -1, PREG_SPLIT_NO_EMPTY) AS $letter) {
				if(preg_match('/^'.$blankLetterPattern.'$/u', $letter)){
					$blankLeterIndex[] = $index++;
					continue;
				}
				if(preg_match('/^'.$separateLetterPattern.'$/u', $letter)){ continue; }
				$index++;
			}
		}
		if($len * $blankApplyRatio < count($blankLeterIndex)) {
			$blankLeterIndex = array();
		}
		
		// NGワードチェック
		$matches = array();
		foreach ($ng_words AS $word) {
			if(mb_strlen(trim($word,'^$')) > $len) { continue; }
			$regex = $this->_ngWordToMatcher($word, $separateLetterPattern, $blankLetterPattern, $blankLeterIndex);
			if(preg_match($regex, $value, $matches)){
				return "{$label} に利用できない単語「{$matches[0]}」が含まれます。";
			}
		}
		
		return null;
	}
	
	/**
	 * NGワードを検索用正規表現に変換します。
	 * @param string $word
	 */
	private function _ngWordToMatcher($word, $separateLetterPattern, $blankLetterPattern, $blankLeterIndex) {
		$regex = '';
		$i = 0;
		foreach (preg_split("//u", $word, -1, PREG_SPLIT_NO_EMPTY) AS $letter) {
			$letterPattern = isset(self::$_LETTER_TO_REGEX[$letter]) ? self::$_LETTER_TO_REGEX[$letter] : preg_quote($letter, '/') ;
			if(in_array($letterPattern, array('^','$'))) {
				$regex .= $letterPattern.$separateLetterPattern.'*';
				continue;
			}
			$regex .= in_array($i++, $blankLeterIndex) ? $blankLetterPattern.'?'.$letterPattern.'?' : $letterPattern ;
			$regex .= $separateLetterPattern.'*';
		}
		$regex = mb_substr($regex, 0, mb_strlen($regex) - mb_strlen($separateLetterPattern.'*'));
		return '/'.$regex.'/u';
	}
	
	/**
	 * 文字の曖昧検索用正規表現変換マップ
	 * @var unknown
	 */
	private static $_LETTER_TO_REGEX = array(
		 "^" => "^"
		,"$" => "$"
		,"a" => "([aAａＡⒶⓐ🄰🅐🅰@＠])"
		,"b" => "([bBｂＢⒷⓑ🄱🅑🅱])"
		,"c" => "([cCｃＣⒸⓒ🄲🅒🅲©])"
		,"d" => "([dDｄＤⒹⓓ🄳🅓🅳])"
		,"e" => "([eEｅＥⒺⓔ🄴🅔🅴])"
		,"f" => "([fFｆＦⒻⓕ🄵🅕🅵])"
		,"g" => "([gGｇＧⒼⓖ🄶🅖🅶])"
		,"h" => "([hHｈＨⒽⓗ🄷🅗🅷])"
		,"i" => "([iIｉＩⒾⓘ🄸🅘🅸])"
		,"j" => "([jJｊＪⒿⓙ🄹🅙🅹])"
		,"k" => "([kKｋＫⓀⓚ🄺🅚🅺])"
		,"l" => "([lLｌＬⓁⓛ🄻🅛🅻])"
		,"m" => "([mMｍＭⓂⓜ🄼🅜🅼])"
		,"n" => "([nNｎＮⓃⓝ🄽🅝🅽])"
		,"o" => "([oOｏＯⓄⓞ🄾🅞🅾])"
		,"p" => "([pPｐＰⓅⓟ🄿🅟🅿℗])"
		,"q" => "([qQｑＱⓆⓠ🅀🅠🆀])"
		,"r" => "([rRｒＲⓇⓡ🅁🅡🆁®])"
		,"s" => "([sSｓＳⓈⓢ🅂🅢🆂])"
		,"t" => "([tTｔＴⓉⓣ🅃🅣🆃])"
		,"u" => "([uUｕＵⓊⓤ🅄🅤🆄])"
		,"v" => "([vVｖＶⓋⓥ🅅🅥🆅])"
		,"w" => "([wWｗＷⓌⓦ🅆🅦🆆])"
		,"x" => "([xXｘＸⓍⓧ🅇🅧🆇])"
		,"y" => "([yYｙＹⓎⓨ🅈🅨🆈])"
		,"z" => "([zZｚＺⓏⓩ🅉🅩🆉])"
		,"0" => "([0０⓿])"
		,"1" => "([1１①⓵❶➀➊㊀一壱壹弌🈩])"
		,"2" => "([2２②⓶❷➁➋㊁二弐貳弎🈔])"
		,"3" => "([3３③⓷❸➂➌㊂三参參弎🈪])"
		,"4" => "([4４④⓸❹➃➍㊃四肆])"
		,"5" => "([5５⑤⓹❺➄➎㊄五伍])"
		,"6" => "([6６⑥⓺❻➅➏㊅六陸])"
		,"7" => "([7７⑦⓻❼➆➐㊆七漆柒質])"
		,"8" => "([8８⑧⓼❽➇➑㊇八捌])"
		,"9" => "([9９⑨⓽❾➈➒㊈九玖])"
		,'ア' => '([アｱ㋐あァｧぁ])'
		,'イ' => '([イｲ㋑㋼いィｨぃヰゐ])'
		,'ウ' => '([ウｳ㋒うゥｩぅヱゑ])'
		,'エ' => '([エｴ㋓㋽えェｪぇ])'
		,'オ' => '([オｵ㋔おォｫぉ])'
		,'カ' => '([カｶ㋕かヵゕ])'
		,'キ' => '([キｷ㋖き])'
		,'ク' => '([クｸ㋗く])'
		,'ケ' => '([ケｹ㋘けヶ])'
		,'コ' => '([コｺ㋙こ])'
		,'サ' => '([サｻ㋚さ🈂])'
		,'シ' => '([シｼ㋛し])'
		,'ス' => '([スｽ㋜す])'
		,'セ' => '([セｾ㋝せ])'
		,'ソ' => '([ソｿ㋞そ])'
		,'タ' => '([タﾀ㋟た])'
		,'チ' => '([チﾁ㋠ち])'
		,'ツ' => '([ツﾂ㋡つッｯっ])'
		,'テ' => '([テﾃ㋢て])'
		,'ト' => '([トﾄ㋣と])'
		,'ナ' => '([ナﾅ㋤な])'
		,'ニ' => '([ニﾆ㊁㋥に🈔])'
		,'ヌ' => '([ヌﾇ㋦ぬ])'
		,'ネ' => '([ネﾈ㋧ね])'
		,'ノ' => '([ノﾉ㋨の])'
		,'ハ' => '([ハﾊ㋩は])'
		,'ヒ' => '([ヒﾋ㋪ひ])'
		,'フ' => '([フﾌ㋫ふ])'
		,'ヘ' => '([ヘﾍ㋬へ])'
		,'ホ' => '([ホﾎ㋭ほ])'
		,'マ' => '([マﾏ㋮ま])'
		,'ミ' => '([ミﾐ㋯み])'
		,'ム' => '([ムﾑ㋰む])'
		,'メ' => '([メﾒ㋱め])'
		,'モ' => '([モﾓ㋲も])'
		,'ヤ' => '([ヤﾔ㋳やャｬゃ])'
		,'ユ' => '([ユﾕ㋴ゆュｭゅ])'
		,'ヨ' => '([ヨﾖ㋵よョｮょ])'
		,'ラ' => '([ラﾗ㋶ら])'
		,'リ' => '([リﾘ㋷り])'
		,'ル' => '([ルﾙ㋸る])'
		,'レ' => '([レﾚ㋹れ])'
		,'ロ' => '([ロﾛ㋺ろ])'
		,'ワ' => '([ワﾜ㋻わヮゎ])'
		,'ヲ' => '([ヲｦ㋾を])'
		,'ン' => '([ンﾝん])'
		,'ガ' => '([ガが]|[カヵｶか][゛ﾞ])'
		,'ギ' => '([ギぎ]|[キｷき][゛ﾞ])'
		,'グ' => '([グぐ]|[クｸく][゛ﾞ])'
		,'ゲ' => '([ゲげ]|[ケヶｹけ][゛ﾞ])'
		,'ゴ' => '([ゴご]|[コｺこ][゛ﾞ])'
		,'ザ' => '([ザざ]|[サｻさ][゛ﾞ])'
		,'ジ' => '([ジじ]|[シｼし][゛ﾞ])'
		,'ズ' => '([ズず]|[スｽす][゛ﾞ])'
		,'ゼ' => '([ゼぜ]|[セｾせ][゛ﾞ])'
		,'ゾ' => '([ゾぞ]|[ソｿそ][゛ﾞ])'
		,'ダ' => '([ダだ]|[タﾀた][゛ﾞ])'
		,'ヂ' => '([ヂぢ]|[チﾁち][゛ﾞ])'
		,'ヅ' => '([ヅづ]|[ツッﾂつっ][゛ﾞ])'
		,'デ' => '([デで]|[テﾃて][゛ﾞ])'
		,'ド' => '([ドど]|[トﾄと][゛ﾞ])'
		,'バ' => '([バば]|[ハﾊは][゛ﾞ])'
		,'ビ' => '([ビび]|[ヒﾋひ][゛ﾞ])'
		,'ブ' => '([ブぶ]|[フﾌふ][゛ﾞ])'
		,'ベ' => '([ベべ]|[ヘﾍへ][゛ﾞ])'
		,'ボ' => '([ボぼ]|[ホﾎほ][゜ﾟ])'
		,'パ' => '([パぱ]|[ハﾊは][゜ﾟ])'
		,'ピ' => '([ピぴ]|[ヒﾋひ][゜ﾟ])'
		,'プ' => '([プぷ]|[フﾌふ][゜ﾟ])'
		,'ペ' => '([ペぺ]|[ヘﾍへ][゜ﾟ])'
		,'ポ' => '([ポぽ]|[ホﾎほ][゜ﾟ])'
		,'ヴ' => '(ヴ|[ウゥｳうぅ][゛ﾞ])'
		,'ァ' => '([アｱ㋐あァｧぁ])'
		,'ィ' => '([イｲ㋑㋼いィｨぃヰゐ])'
		,'ゥ' => '([ウｳ㋒うゥｩぅヱゑ])'
		,'ェ' => '([エｴ㋓㋽えェｪぇ])'
		,'ォ' => '([オｵ㋔おォｫぉ])'
		,'ヵ' => '([カｶ㋕かヵゕ])'
		,'ヶ' => '([ケｹ㋘けヶ])'
		,'ッ' => '([ツﾂ㋡つッｯっ])'
		,'ャ' => '([ヤﾔ㋳やャｬゃ])'
		,'ュ' => '([ユﾕ㋴ゆュｭゅ])'
		,'ョ' => '([ヨﾖ㋵よョｮょ])'
		,'ヮ' => '([ワﾜ㋻わヮゎ])'
		,'゛' => '([゛ﾞ])'
		,'゜' => '([゜ﾟ])'
		,'ー' => '([ー-])'
	);
	
	//-------------------------------------------------------------------------
	// リスト含有
	//-------------------------------------------------------------------------
	const VALID_CONTAINS = 'contains';
	protected function valid_contains($field, $label, $value, $list) {
		if($this->_empty($value)) { return null; }
		if(is_array($value)) {
			foreach ($value AS $v) {
				if(!in_array($v, $list)) { return "{$label}は指定の一覧から選択して下さい。"; }
			}
		} else {
			if(!in_array($value, $list)) { return "{$label}は指定の一覧から選択して下さい。"; }
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// リスト選択数下限
	//-------------------------------------------------------------------------
	const VALID_MIN_SELECT_COUNT = 'min_select_count';
	protected function valid_min_select_count($field, $label, $value, $min) {
		if($this->_empty($value)) { return null; }
		$size = is_array($value) ? count($value) : 1 ;
		if($size < $min) { return "{$label}は {$min} 個以上で選択して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// リスト選択数
	//-------------------------------------------------------------------------
	const VALID_SELECT_COUNT = 'select_count';
	protected function valid_select_count($field, $label, $value, $count) {
		if($this->_empty($value)) { return null; }
		$size = is_array($value) ? count($value) : 1 ;
		if($size != $count) { return "{$label}を {$count} 個選択して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// リスト選択数上限
	//-------------------------------------------------------------------------
	const VALID_MAX_SELECT_COUNT = 'max_select_count';
	protected function valid_max_select_count($field, $label, $value, $max) {
		if($this->_empty($value)) { return null; }
		$size = is_array($value) ? count($value) : 1 ;
		if($size > $max) { return "{$label}は {$max} 個以下で選択して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時フォーマット
	//-------------------------------------------------------------------------
	const VALID_DATETIME = 'datetime';
	protected function valid_datetime($field, $label, $value, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$date = DateTime::createFromFormat($format, $value);
		$le   = DateTime::getLastErrors();
		if($date === false || !empty($le['errors']) || !empty($le['warnings'])) { return "{$label}は".($formatLabel ? $formatLabel : "{$format} 形式")."で入力して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時：未来日(当日含まず)
	//-------------------------------------------------------------------------
	const VALID_FUTURE_THAN = 'future_than';
	protected function valid_future_than($field, $label, $value, $pointTime, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime($pointTime);
		if($target < $point) { return "{$label}は ".$point->format($format)." よりも未来日を指定して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時：未来日(当日含む)
	//-------------------------------------------------------------------------
	const VALID_FUTURE_EQUAL = 'future_equal';
	protected function valid_future_equal($field, $label, $value, $pointTime, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime($pointTime);
		if($target <= $point) { return "{$label}は ".$point->format($format)." よりも未来日(当日含む)を指定して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時：過去日(当日含まず)
	//-------------------------------------------------------------------------
	const VALID_PAST_THAN = 'past_than';
	protected function valid_past_than($field, $label, $value, $pointTime, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime($pointTime);
		if($target > $point) { return "{$label}は ".$point->format($format)." よりも過去日を指定して下さい。"; }
		return null;
	}
		
	//-------------------------------------------------------------------------
	// 日時：過去日(当日含む)
	//-------------------------------------------------------------------------
	const VALID_PAST_EQUAL = 'past_equal';
	protected function valid_past_equal($field, $label, $value, $pointTime, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime($pointTime);
		if($target >= $point) { return "{$label}は ".$point->format($format)." よりも過去日(当日含む)を指定して下さい。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時：年齢制限：以上
	//-------------------------------------------------------------------------
	const VALID_AGE_GREATER_EQUAL = 'age_greater_equal';
	protected function valid_age_greater_equal($field, $label, $value, $age, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime("-{$age} year");
		if($target > $point) { return "{$age}歳未満の方はご利用頂けません。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// 日時：年齢制限：以下
	//-------------------------------------------------------------------------
	const VALID_AGE_LESS_EQUAL = 'age_less_equal';
	protected function valid_age_less_equal($field, $label, $value, $age, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = new DateTime("-{$age} year");
		if($target < $point) { return ($age + 1)."歳以上の方はご利用頂けません。"; }
		return null;
	}
	
	//-------------------------------------------------------------------------
	// アップロードファイル：サイズ
	//-------------------------------------------------------------------------
	const VALID_FILE_SIZE = 'file_size';
	protected function valid_file_size($field, $label, $value, $size, $sizeLabel=null) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->size >= $size) {
			return "{$label}のファイルサイズ [ ".$value->size." byte ] が ".($sizeLabel ? $sizeLabel : "{$size} byte")." を超えています。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：拡張子
	//-------------------------------------------------------------------------
	const VALID_FILE_SUFFIX_MATCH = 'file_suffix_match';
	protected function valid_file_suffix_match($field, $label, $value, $pattern, $patternLabel=null) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if(!$value->matchFileSuffix($pattern)) {
			return "{$label}のファイル拡張子が ".($patternLabel ? $patternLabel : $pattern)." ではありません。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：MimeType
	//-------------------------------------------------------------------------
	const VALID_FILE_MIME_TYPE_MATCH = 'file_mime_type_match';
	protected function valid_file_mime_type_match($field, $label, $value, $pattern, $patternLabel=null) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if(!$value->matchMimeType($pattern)) {
			return "{$label}の形式が ".($patternLabel ? $patternLabel : $pattern)." ではありません。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：WEB画像拡張子
	//-------------------------------------------------------------------------
	const VALID_FILE_WEB_IMAGE_SUFFIX = 'file_web_image_suffix';
	protected function valid_file_web_image_suffix($field, $label, $value) {
		return $this->valid_file_suffix_match($field, $label, $value, '/^(jpe?g|gif|png|ico)$/iu', '画像形式[ jpg, jpeg, gif, png, ico]');
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：幅(最大値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_MAX_WIDTH = 'file_image_max_width';
	protected function valid_file_image_max_width($field, $label, $value, $width) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width > $width) {
			return "{$label}の幅 [ ".$value->width." px ] を {$width} px 以下にして下さい。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：幅(指定値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_WIDTH = 'file_image_width';
	protected function valid_file_image_width($field, $label, $value, $width) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width == $width) {
			return "{$label}の幅 [ ".$value->width." px ] を {$width} px にして下さい。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：幅(最小値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_MIN_WIDTH = 'file_image_min_width';
	protected function valid_file_image_min_width($field, $label, $value, $width) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width < $width) {
			return "{$label}の幅 [ ".$value->width." px ] を {$width} px 以上にして下さい。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：高さ(最大値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_MAX_HEIGHT = 'file_image_max_height';
	protected function valid_file_image_max_height($field, $label, $value, $height) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width > $height) {
			return "{$label}の高さ [ ".$value->width." px ] を {$height} px 以下にして下さい。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：高さ(指定値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_HEIGHT = 'file_image_height';
	protected function valid_file_image_height($field, $label, $value, $height) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width == $height) {
			return "{$label}の高さ [ ".$value->width." px ] を {$height} px にして下さい。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// アップロードファイル：画像：高さ(最小値)
	//-------------------------------------------------------------------------
	const VALID_FILE_IMAGE_MIN_HEIGHT = 'file_image_min_height';
	protected function valid_file_image_min_height($field, $label, $value, $height) {
		if($this->_empty($value)) { return null; }
		if(!($value instanceof UploadFile)) { throw new InvalidValidateRuleException("{$label} in not UploadFile."); }
		if($value->width < $height) {
			return "{$label}の高さ [ ".$value->width." px ] を {$height} px 以上にして下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：同じ値(再入力)
	//-------------------------------------------------------------------------
	const VALID_FIELD_SAME = 'field_same';
	protected function valid_field_same($field, $label, $value, $other) {
		if($this->_empty($value)) { return null; }
		if($value != $this->$other) {
			$labels = $this->labels();
			return "{$label}の値が{$labels[$other]}の値と異なります。";
		}
		return null;
	}

	//-------------------------------------------------------------------------
	// フィールド比較：重複不可
	//-------------------------------------------------------------------------
	const VALID_FIELD_UNIQUE = 'field_unique';
	protected function valid_field_unique($field, $label, $value, $depends) {
		$labels       = $this->labels();
		$dependsLabel = array();
		$values       = array();
		$emptyAll     = true;
		foreach (explode(',', $depends) AS $depend) {
			$emptyAll      &= $this->_empty($this->$depend);
			$values[]       = $this->$depend;
			$dependsLabel[] = isset($labels[$depend]) ? $labels[$depend] : $depend ;
		}
		if($emptyAll) { return null; }
		$unique = array_unique($values, SORT_STRING);
		if(count($values) != count($unique)) { return join(', ', $dependsLabel)." は異なる値を入力して下さい。"; }
		return null;
	}

	//-------------------------------------------------------------------------
	// フィールド比較：日時：未来日(当日含まず)
	//-------------------------------------------------------------------------
	const VALID_FIELD_FUTURE_THAN = 'field_future_than';
	protected function valid_field_future_than($field, $label, $value, $other, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = DateTime::createFromFormat($format, $this->$other);
		if($target < $point) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}よりも未来日を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：数値 (自身 >= 比較対象)
	//-------------------------------------------------------------------------
	const VALID_FIELD_GRATER_EQUAL = 'field_grater_equal';
	protected function valid_field_grater_equal($field, $label, $value, $other) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		$sv = doubleval($value);
		$ov = doubleval($this->$other);
		if($sv < $ov) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}以上の値を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：数値 (自身 > 比較対象)
	//-------------------------------------------------------------------------
	const VALID_FIELD_GRATER_THAN = 'field_grater_than';
	protected function valid_field_grater_than($field, $label, $value, $other) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		$sv = doubleval($value);
		$ov = doubleval($this->$other);
		if($sv <= $ov) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}超過の値を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：数値 (自身 <= 比較対象)
	//-------------------------------------------------------------------------
	const VALID_FIELD_LESS_EQUAL = 'field_less_equal';
	protected function valid_field_less_equal($field, $label, $value, $other) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		$sv = doubleval($value);
		$ov = doubleval($this->$other);
		if($sv > $ov) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}以下の値を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：数値 (自身 < 比較対象)
	//-------------------------------------------------------------------------
	const VALID_FIELD_LESS_THAN = 'field_less_than';
	protected function valid_field_less_than($field, $label, $value, $other) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_number($field, $label, $value);
		if(!empty($preCheck)) { return $preCheck; }
		$sv = doubleval($value);
		$ov = doubleval($this->$other);
		if($sv >= $ov) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}超過の値を指定して下さい。";
		}
		return null;
	}
	
	
	//-------------------------------------------------------------------------
	// フィールド比較：日時：未来日(当日含む)
	//-------------------------------------------------------------------------
	const VALID_FIELD_FUTURE_EQUAL = 'field_future_equal';
	protected function valid_field_future_equal($field, $label, $value, $other, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = DateTime::createFromFormat($format, $this->$other);
		if($target <= $point) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}よりも未来日(当日含む)を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：日時：過去日(当日含まず)
	//-------------------------------------------------------------------------
	const VALID_FIELD_PAST_THAN = 'field_past_than';
	protected function valid_field_past_than($field, $label, $value, $other, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = DateTime::createFromFormat($format, $this->$other);
		if($target > $point) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}よりも過去日を指定して下さい。";
		}
		return null;
	}
	
	//-------------------------------------------------------------------------
	// フィールド比較：日時：過去日(当日含む)
	//-------------------------------------------------------------------------
	const VALID_FIELD_PAST_EQUAL = 'field_past_equal';
	protected function valid_field_past_equal($field, $label, $value, $other, $format, $formatLabel=null) {
		if($this->_empty($value)) { return null; }
		$preCheck = $this->valid_datetime($field, $label, $value, $format, $formatLabel);
		if(!empty($preCheck)) { return $preCheck; }
		$target = DateTime::createFromFormat($format, $value);
		$point  = DateTime::createFromFormat($format, $this->$other);
		if($target >= $point) {
			$labels = $this->labels();
			return "{$label}は{$labels[$other]}よりも過去日(当日含む)を指定して下さい。";
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
class UploadFile {
	// サイズ指定計算用の定数
	const GB = 1073741824;
	const MB = 1048576;
	const KB = 1024;
	
	/**
	 * フォーム名称
	 * @var string
	 */
	public $formName;
	
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
	 * @var unknown
	 */
	public $error;
	
	/**
	 * アップロードファイルサフィックス
	 * @var string
	 */
	public $suffix;
	
	/**
	 * 幅（画像の場合のみ）
	 * @var int
	 */
	public $width;
	
	/**
	 * 高さ（画像の場合のみ）
	 * @var int
	 */
	public $height;
	
	/**
	 * アップロードファイルオブジェクトを構築します。
	 * 
	 * @param string $formName フォーム名
	 * @param string $field    フィールド名
	 * @param array  $file     ファイルリクエスト(=$_FILE) 又はそれに類する情報
	 */
	public function __construct ($formName, $field, $file) {
		
		foreach ($this AS $key => $value) {
			$this->$key = isset($file[$key]) ? $file[$key] : null ;
		}
		
		$this->formName = $formName;
		$this->field    = $field;
		$this->suffix   = null;
		if(empty(!$this->name)) {
			$pi = pathinfo($this->name);
			$this->suffix = strtolower($pi['extension']);
		}
		if(strrpos($this->type, "image/", -strlen($this->type)) !== FALSE) {
			$imagesize   = getimagesize($this->tmp_name);
			$this->width  = isset($imagesize[0]) ? $imagesize[0] : null ;
			$this->height = isset($imagesize[1]) ? $imagesize[1] : null ;
		}
	}
	
	/**
	 * アップロードファイルデータが空か否かチェックします。
	 * 
	 * @return boolean true : 空である／false : 空でない
	 */
	public function isEmpty() {
		return $this->size == 0 || empty($this->tmp_name);
	}
	
	/**
	 * エラーがあるかチェックします。
	 * 
	 * @return boolean true : エラー有り／false : エラー無し
	 */
	public function hasError() {
		return !empty($this->error);
	}
	
	/**
	 * 拡張子が指定の条件にマッチするかチェックします。
	 * 
	 * @param  string $pattern 拡張子を表す正規表現
	 * @return boolean true : マッチ／false : アンマッチ
	 */
	public function matchFileSuffix($pattern) {
		return preg_match($pattern, $this->suffix);
	}
	
	/**
	 * MimeType が指定の条件にマッチするかチェックします
	 * 
	 * @param string $pattern MimeType を表す正規表現
	 * @return boolean true : マッチ／false : アンマッチ
	 */
	public function matchMimeType($pattern) {
		return preg_match($pattern, $this->type);
	}
	
	/**
	 * アップロードデータを確認領域に保存します。
	 * ※セーブしたアップロードファイル情報はセッションに保存され、UploadFile::load で再読み込みできます。
	 * 
	 * @param  string $dir テンポラリディレクトリ
	 * @return string 保存されたファイル名
	 */
	public function saveTemporary($dir) {
		$fileId = self::_fileId($this->formName, $this->field);
		if($this->isEmpty()) {
			$_SESSION[$fileId] = serialize($this);
			return null;
		}
		
		if(!file_exists($dir)) {
			mkdir($dir, 0775, true);
		}
		
		$file   = "{$fileId}.".$this->suffix;
		$path   = "{$dir}/{$file}";
		
		if(is_uploaded_file($this->tmp_name)){
			move_uploaded_file($this->tmp_name, "{$dir}/{$file}");
		}
		
		$this->tmp_name = $path;
		$_SESSION[$fileId] = serialize($this);
		
		return $file;
	}
	
	/**
	 * アップロードデータを公開領域に保存します。
	 * 
	 * @param  string $dir      公開ディレクトリ
	 * @param  string $baseName 公開用ファイルベース名
	 * @return string 公開ファイル名
	 */
	public function publish($dir, $baseName) {
		if($this->isEmpty()) { return null; }
		
		if(!file_exists($dir)) {
			mkdir($dir, 0775, true);
		}
		
		$file = "{$baseName}.{$this->suffix}";
		rename($this->tmp_name, "{$dir}/{$file}");
		unset($_SESSION[self::_fileId($this->formName, $this->field)]);
		
		return $file;
	}
	
	/**
	 * アップロードデータを削除します。
	 * 
	 * @return void
	 */
	public function remove() {
		unset($_SESSION[self::_fileId($this->formName, $this->field)]);

		if(file_exists($this->tmp_name)) {
			unlink($this->tmp_name);
		}
	}
	
	/**
	 * 現在作業中のテンポラリデータが存在するかチェックします。
	 * 
	 * @param string $formName  フォーム名
	 * @param string $fieldName フィールド名
	 */
	public static function exists($formName, $fieldName) {
		return isset($_SESSION[self::_fileId($formName, $fieldName)]);
	}

	/**
	 * 現在作業中のテンポラリファイルデータをロードします。
	 * 
	 * @param string $formName  フォーム名
	 * @param string $fieldName フィールド名
	 * @return UploadFile
	 */
	public static function load($formName, $fieldName) {
		return self::exists($formName, $fieldName) ? unserialize($_SESSION[self::_fileId($formName, $fieldName)]) : new UploadFile($formName, $fieldName, array()) ;
	}
	
	/**
	 * 空のアップロードファイルデータを生成します。
	 * 
	 * @param string $formName  フォーム名
	 * @param string $fieldName フィールド名
	 * @return UploadFile 空のアップロードファイル
	 */
	public static function createEmpty($formName, $fieldName) {
		return new UploadFile($formName, $fieldName, array());
	}
	
	/**
	 * テンポラリデータ用のファイルIDを取得します。
	 * 
	 * @param  string $formName  フォーム名
	 * @param  string $fieldName フィールド名
	 * @return string テンポラリ用ファイルID
	 */
	private static function _fileId($formName, $fieldName) {
		return "SFLF_UPLOAD_FILE_{$formName}_{$fieldName}_".session_id();
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
class InvalidValidateRuleException extends RuntimeException {
	public function __construct ($message, $code=null, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}

