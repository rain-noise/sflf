<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

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
 * @version   v1.2.1
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Router
{
    /** @var string デフォルトコントローラー名 (default: top) */
    const DEFAULT_CONTEOLLER_NAME = 'top';

    /** @var string デフォルトアクション名 (default: index) */
    const DEFAULT_ACTION_NAME     = 'index';

    /** @var string URI の ワード区切り文字を定義します。 */
    const URI_SNAKE_CASE_SEPARATOR = '-';

    /**
     * コンテキストパス
     *
     * @var string
     */
    private $context_path;

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
     * @var string[]
     */
    private $part_of_uri;

    /**
     * アクセス制御
     *
     * @var bool
     */
    private $accessible;

    /**
     * コントローラークラスの名前空間
     *
     * @var string
     */
    private $controller_class_namespace;

    /**
     * コントローラークラス名のサフィックス
     *
     * @var string
     */
    private $controller_class_name_suffix;

    /**
     * ルーティングオブジェクトを構築します。
     *
     * @param string $uri                          リクエストURL(= $_SERVER['REQUEST_URI'])
     * @param string $context_path                 コンテキストパス (default: '')
     * @param string $controller_class_namespace   コントローラークラスの名前空間 (default: null for use 'SFLF_CONFIG['namespace']['controller']' constants if define)
     * @param string $controller_class_name_suffix コントローラークラス名のサフィックス (default: 'Controller')
     */
    public function __construct($uri, $context_path = '', $controller_class_namespace = null, $controller_class_name_suffix = 'Controller')
    {
        $this->uri          = (string)preg_replace('/^'.preg_quote($context_path, '/').'/', '', $uri);
        $this->context_path = $context_path;
        $part_of_uri        = explode('/', (string)preg_replace('/^\//', '', strstr($this->uri, '?', true) ?: $this->uri));
        assert(is_array($part_of_uri));
        $this->part_of_uri                  = $part_of_uri;
        $this->accessible                   = false;
        $this->controller_class_namespace   = $controller_class_namespace ?? (defined('SFLF_CONFIG') ? (SFLF_CONFIG['namespace']['controller'] ?? '') : '') ;
        $this->controller_class_name_suffix = $controller_class_name_suffix;
    }

    /**
     * コントローラーの非パブリックメソッドに対してのアクセス制御設定を行います
     *
     * @param  bool $accessible true : アクセス可／false : アクセス不可
     * @return void
     */
    public function setAccessible($accessible)
    {
        $this->accessible = $accessible;
    }

    /**
     * コントローラークラスのインスタンスを取得します。
     *
     * @return object コントローラーオブジェクト
     * @throws NoRouteException
     */
    public function getController()
    {
        $controller = $this->_getControllerName() ;
        try {
            return new $controller();
        } catch(\Throwable $e) {
            throw new NoRouteException("Route Not Found : Controller [ {$controller} ] can not instantiate.", 0, $e);
        }
    }

    /**
     * コントローラーのアクションを起動します。
     *
     * @param object $controller コントローラーオブジェクト
     * @return mixed アクションの戻り値
     */
    public function invoke($controller)
    {
        $clazz  = $this->_getControllerName();
        $method = $this->_getMethodName();
        $args   = $this->getArgs();

        try {
            $invoker = new \ReflectionMethod($clazz, $method);
        } catch(\Throwable $e) {
            throw new NoRouteException("Route Not Found : Controller [ {$clazz}->{$method}() ] can not invoke.", 0, $e);
        }

        if (count($args) < $invoker->getNumberOfRequiredParameters()) {
            throw new NoRouteException("Route Not Found : Controller [ {$clazz}->{$method}() ] can not invoke, because of not enough required args count.");
        }

        $invoker->setAccessible($this->accessible);
        return $invoker->invokeArgs($controller, $args);
    }

    /**
     * コントローラークラス名を取得します。
     *
     * @return string コントローラークラス名
     */
    private function _getControllerName()
    {
        return $this->controller_class_namespace . $this->_camelize($this->getPartOfController()) . $this->controller_class_name_suffix ;
    }

    /**
     * メソッド名を取得します。
     *
     * @return string メソッド名
     */
    private function _getMethodName()
    {
        return lcfirst($this->_camelize($this->getPartOfAction()));
    }

    /**
     * コンテキストパスを取得します。
     *
     * @return string コンテキストパス
     */
    public function getContextPath()
    {
        return $this->context_path;
    }

    /**
     * コントローラーパート文字列を取得します。
     *
     * @return string コントローラーパート文字列
     */
    public function getPartOfController()
    {
        return $this->_get($this->part_of_uri, 0, self::DEFAULT_CONTEOLLER_NAME);
    }

    /**
     * アクションパート文字列を取得します。
     *
     * @return string アクションパート文字列
     */
    public function getPartOfAction()
    {
        return $this->_get($this->part_of_uri, 1, self::DEFAULT_ACTION_NAME);
    }

    /**
     *パラメータを取得します。
     *
     * @return string[] パラメータ
     */
    public function getArgs()
    {
        return count($this->part_of_uri) > 2 ? array_slice($this->part_of_uri, 2) : [] ;
    }

    /**
     * 指定のコントローラー名などが現在のルーティング内容にマッチするかチェックします。
     * ※グローバルナビゲーションのアクティブスタイル適用などで利用できます
     *
     * @param string      $controller コントローラ名の正規表現
     * @param string|null $action     アクション名の正規表現 (default: null)
     * @param string      ...$args    引数の正規表現 (default: [])
     * @return bool true : マッチ／false : アンマッチ
     */
    public function match($controller, $action = null, ...$args)
    {
        if (!preg_match($controller, $this->getPartOfController())) {
            return false;
        }
        if (!empty($action)) {
            if (!preg_match($action, $this->getPartOfAction())) {
                return false;
            }
        }

        if (empty($args)) {
            return true;
        }

        $list = $this->getArgs();
        if (count($args) > count($list)) {
            return false;
        }

        foreach ($args as $i => $value) {
            if (!preg_match($value, $list[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * ルーティング情報を文字列表現で表します。
     *
     * @return string ルーティング情報
     */
    public function __toString()
    {
        return "Routing : '/".join("/", $this->part_of_uri)."' to ".$this->_getControllerName()."->".$this->_getMethodName()."(".join(',', $this->getArgs()).")";
    }

    /**
     * スネークケース(snake_case)文字列をキャメルケース(CamelCase)文字列に変換します。
     *
     * @param  string $str スネークケース文字列
     * @return string キャメライズ文字列
     */
    private function _camelize($str)
    {
        return str_replace(self::URI_SNAKE_CASE_SEPARATOR, '', ucwords($str, self::URI_SNAKE_CASE_SEPARATOR));
    }

    /**
     * 配列から値を取得します。
     *
     * @param array<mixed> $array   配列
     * @param string|int   $key     キー名
     * @param mixed|null   $default デフォルト値 (default: null)
     * @return mixed 値
     */
    public function _get($array, $key, $default = null)
    {
        if ($array == null) {
            return $default;
        }
        if (!isset($array[$key])) {
            return $default;
        }
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
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class NoRouteException extends \RuntimeException
{
    /**
     * ルーティング関連例外を構築します。
     *
     * @param string          $message  エラーメッセージ
     * @param int             $code     エラーコード (default: 0)
     * @param \Throwable|null $previous 原因例外 (default: null)
     * @return NoRouteException
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
