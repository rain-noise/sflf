<?php
/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 URLルーティング クラス
 * 以下のパターンでURL解析を行い
 * 
 * 　http://domain.of.yours/{controller}/{action}/{arg1}/{arg2}...
 * 　例1) /user/detail/123456
 * 　例1) /user/register-input
 * 　例3) /term
 * 
 * 以下の処理を実行します。
 * 
 * 　{Controller}@{action}({arg1}, {arg2}, ...)
 * 　例1) UserController@detail(123456)
 * 　例2) UserController@registerInput()
 * 　例3) TermController@index()
 * 
 * controller : コントローラー名（デフォルト：Top）
 * action     : アクション名（デフォルト：index）
 * 
 * 【使い方】
 * require_once "/path/to/Router.php"; // or use AutoLoader
 * 
 * try {
 *     $router = new Router($_SERVER['REQUEST_URI']);
 *     $controller = $router->getController();
 * 
 *     //Something to do to $controller
 * 
 *     $router->invoke($controller);
 * } catch(NoRouteException $e) {
 *     //Something to do for route not found.
 * }
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://opensource.org/licenses/MIT
 */
 class Router
{
	// 各種デフォルト名の定義
	const DEFAULT_CONTEOLLER_NAME = 'top';
	const DEFAULT_ACTION_NAME     = 'index';
	
	// コントローラークラス名のサフィックス
	const CONTROLLER_CLASS_NAME_SUFFIX = 'Controller';
	
	// URI の ワード区切り文字を定義します。
	const URI_SNAKE_CASE_SEPARATOR = '-';
	
	/**
	 * コンテキストパス
	 * 
	 * @var string
	 */
	private $contextPath;
	
	/**
	 * リクエストURI
	 * ※コンテキストパスを除いた文字列
	 * 
	 * @var string
	 */
	private $uri;
	
	/**
	 * リクエストURI
	 * 
	 * @var string
	 */
	private $part_of_uri;
	
	/**
	 * アクセス制御
	 * 
	 * @var boolean
	 */
	private $accessible;
	
	/**
	 * ルーティングオブジェクトを構築します。
	 * 
	 * @param string $uri         リクエストURL(= $_SERVER['REQUEST_URI'])
	 * @param string $contextPath コンテキストパス                         - デフォルト ''
	 */
	public function __construct($uri, $contextPath='') {
		$this->uri         = preg_replace('/^'.preg_quote($contextPath,'/').'/', '', $uri);
		$this->contextPath = $contextPath;
		$uri = strpos($this->uri, '?') ===false ? $this->uri : strstr($this->uri, '?', true) ;
		$this->part_of_uri = explode('/', preg_replace('/^\//', '', $uri));
		$this->accessible  = false;
	}
	
	/**
	 * コントローラーの非パブリックメソッドに対してのアクセス制御設定を行います
	 * 
	 * @param  boolean $accessible true : アクセス可／false : アクセス不可
	 * @return void
	 */
	public function setAccessible($accessible) {
		$this->accessible = $accessible;
	}
	
	/**
	 * コントローラークラスのインスタンスを取得します。
	 * 
	 * @return obj コントローラーオブジェクト
	 * @throws NoRouteException
	 */
	public function getController() {
		$controller = $this->_getControllerName() ;
		try {
			return new $controller();
		} catch(Throwable $e) {
			throw new NoRouteException("Route Not Found : Controller [ {$controller} ] can not instantiate.", null, $e);
		}
	}
	
	/**
	 * コントローラーのアクションを起動します。
	 * 
	 * @param obj $controller コントローラーオブジェクト
	 * @return mixed アクションの戻り値
	 */
	public function invoke($controller) {
		$clazz   = $this->_getControllerName();
		$method  = $this->_getMethodName();
		$args    = $this->getArgs();
		$invoker = new ReflectionMethod($clazz, $method);
		$invoker->setAccessible($this->accessible);
		return $invoker->invokeArgs($controller, $args);
	}
	
	/**
	 * コントローラークラス名を取得します。
	 * 
	 * @return string コントローラークラス名
	 */
	private function _getControllerName() {
		return $this->_camelize($this->getPartOfController()) . self::CONTROLLER_CLASS_NAME_SUFFIX ;
	}
	
	/**
	 * メソッド名を取得します。
	 * 
	 * @return string メソッド名
	 */
	private function _getMethodName() {
		return lcfirst($this->_camelize($this->getPartOfAction()));
	}
	
	/**
	 * コンテキストパスを取得します。
	 * 
	 * @return string コンテキストパス
	 */
	public function getContextPath() {
		return $this->contextPath;
	}
		
	/**
	 * コントローラーパート文字列を取得します。
	 * 
	 * @return string コントローラーパート文字列
	 */
	public function getPartOfController() {
		return $this->_get($this->part_of_uri, 0, self::DEFAULT_CONTEOLLER_NAME);
	}
		
	/**
	 * アクションパート文字列を取得します。
	 * 
	 * @return string アクションパート文字列
	 */
	public function getPartOfAction() {
		return $this->_get($this->part_of_uri, 1, self::DEFAULT_ACTION_NAME);
	}
	
	/**
	 *パラメータを取得します。
	 * 
	 * @return array パラメータ
	 */
	public function getArgs() {
		return count($this->part_of_uri) > 2 ? array_slice($this->part_of_uri, 2) : array() ;
	}
	
	/**
	 * 指定のコントローラー名などが現在のルーティング内容にマッチするかチェックします。
	 * ※グローバルナビゲーションのアクティブスタイル適用などで利用できます
	 * 
	 * @param  string $controller コントローラオブジェクト
	 * @param  string $action     アクション名
	 * @param  array  ...$args    引数
	 * @return boolean true : マッチ／false : アンマッチ
	 */
	public function match($controller, $action = null, ...$args) {
		if($controller != $this->getPartOfController()) { return false; }
		if(!empty($action)) {
			if($action != $this->getPartOfAction()) { return false; }
		}
		
		if(empty($args)) { return true; }
		
		$list = $this->getArgs();
		if(count($args) > count($list)) { return false; }
		
		foreach ($args AS $i => $value) {
			if($list[$i] != $value) { return false; }
		}
		
		return true;
	}
	
	/**
	 * ルーティング情報を文字列表現で表します。
	 * 
	 * @return string ルーティング情報
	 */
	public function __toString() {
		return "Routing : '/".join("/", $this->part_of_uri)."' to ".$this->_getControllerName()."->".$this->_getMethodName()."(".join(',', $this->getArgs()).")";
	}
	
	/**
	 * スネークケース(snake_case)文字列をキャメルケース(CamelCase)文字列に変換します。
	 * 
	 * @param  string $str スネークケース文字列
	 * @return string キャメライズ文字列
	 */
	private function _camelize($str) {
		return str_replace(self::URI_SNAKE_CASE_SEPARATOR, '', ucwords($str, self::URI_SNAKE_CASE_SEPARATOR));
	}
	
	/**
	 * 配列から値を取得します。
	 * 
	 * @param array  $array   配列
	 * @param string $key     キー名
	 * @param mixed  $default デフォルト値
	 */
	public function _get($array, $key, $default = null) {
		if($array == null) { return $default; }
		if(!isset($array[$key])) { return $default; }
		return empty($array[$key]) ? $default : $array[$key] ;
	}
}


/**
 * Single File Low Functionality Class Tools
 * 
 * ■単一ファイル低機能 ルーティング関連エラー クラス（Router付帯クラス）
 * 
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://opensource.org/licenses/MIT
 */
class NoRouteException extends RuntimeException {
	public function __construct ($message, $code=null, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


