<?php
// namespace App\Core; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 端末判定 クラス
 * ユーザーエージェントを利用した端末判定を行います。
 *
 * 【使い方】
 * require_once "/path/to/Terminal.php"; // or use AutoLoader
 *
 * if(Terminal::isMobile()) {
 *     // Something to do
 * }
 *
 * @package   SFLF
 * @version   v1.0.5
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Terminal
{
    /** @var int 端末タイプ：スマートフォン */
    const TYPE_SMARTPHONE = 1;
    /** @var int 端末タイプ：タブレット */
    const TYPE_TABLET = 2;
    /** @var int 端末タイプ：その他(PC) */
    const TYPE_OTHERS = 3;

    /**
     * ユーザーエージェント
     * @var string
     */
    private static $_UA = null;

    /**
     * 端末タイプ
     * @var int self::TYPE_*
     */
    private static $_TYPE = self::TYPE_OTHERS;

    /**
     * クローラーか否か
     * @var bool
     */
    private static $_IS_CRAWLER = false;

    /**
     * 接続元IPアドレス（REMOTE_ADDR）
     * @var string|null
     */
    private static $_IP = null;

    /**
     * 接続元IPアドレス（HTTP_X_FORWARDED_FOR）
     * @var string|null
     */
    private static $_XFF = null;

    /**
     * 接続元IPアドレス（HTTP_X_REAL_IP）
     * @var string|null
     */
    private static $_XRIP = null;

    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 端末が モバイル(スマートフォン 又は タブレット) か判定します。
     *
     * @return bool true : モバイル／false : モバイル以外
     */
    public static function isMobile()
    {
        return self::isSmartphone() || self::isTablet() ;
    }

    /**
     * 端末が スマートフォン か判定します。
     *
     * @return bool true : スマートフォン／false : スマートフォン以外
     */
    public static function isSmartphone()
    {
        return self::$_TYPE == self::TYPE_SMARTPHONE;
    }

    /**
     * 端末が タブレット か判定します。
     *
     * @return bool true : タブレット／false : タブレット以外
     */
    public static function isTablet()
    {
        return self::$_TYPE == self::TYPE_TABLET;
    }

    /**
     * 端末が その他(PC) か判定します。
     *
     * @return bool true : その他(PC)／false : その他(PC)以外
     */
    public static function isOthers()
    {
        return self::$_TYPE == self::TYPE_OTHERS;
    }

    /**
     * 端末が クローラー か判定します。
     *
     * @return bool true : クローラー／false : クローラー以外
     */
    public static function isCrawler()
    {
        return self::$_IS_CRAWLER;
    }

    /**
     * 端末種別を取得します。
     *
     * @return int Terminal::TYPE_*
     */
    public static function getType()
    {
        return self::$_TYPE;
    }

    /**
     * ユーザーエージェントを取得します。
     *
     * @return string ユーザーエージェント文字列
     */
    public static function getUA()
    {
        return self::$_UA;
    }

    /**
     * 接続元IPを取得します。
     * ※$via_proxy 指定時は X_REAL_IP ⇒ X_FORWARDED_FOR の順で最初に見つけた値をIPアドレスとして返します。
     *
     * @param bool $via_proxy プロキシ/ロードバランサ経由か否か (default: false)
     * @return string|null 接続元IPアドレス
     */
    public static function getIP($via_proxy = false)
    {
        return $via_proxy ? (!empty(self::$_XRIP) ? self::$_XRIP : self::$_XFF) : self::$_IP ;
    }

    /**
     * 端末情報を初期化します。
     *
     * @param string|null $ua   ユーザーエージェント
     * @param string $ip   IPアドレス：REMOTE_ADDR 値
     * @param string $xrip IPアドレス：X_REAL_IP 値
     * @param string $xff  IPアドレス：X_FORWARDED_FOR 値
     * @return void
     */
    public static function init($ua, $ip, $xrip, $xff)
    {
        self::$_XFF = null;
        if (!empty($xff)) {
            $xff_list   = explode(",", $xff);
            self::$_XFF = trim(reset($xff_list));
        }
        self::$_XRIP = $xrip;
        self::$_IP   = $ip;
        self::$_UA   = $ua ?? '';
        $ua          = mb_strtolower($ua ?? '');

        // 端末タイプ判定
        if (strpos($ua, 'iphone') !== false) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif (strpos($ua, 'ipod') !== false) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif ((strpos($ua, 'android') !== false) && (strpos($ua, 'mobile') !== false)) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif ((strpos($ua, 'windows') !== false) && (strpos($ua, 'phone') !== false)) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif ((strpos($ua, 'firefox') !== false) && (strpos($ua, 'mobile') !== false)) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif (strpos($ua, 'blackberry') !== false) {
            self::$_TYPE = self::TYPE_SMARTPHONE;
        } elseif (strpos($ua, 'ipad') !== false) {
            self::$_TYPE = self::TYPE_TABLET;
        } elseif ((strpos($ua, 'windows') !== false) && (strpos($ua, 'touch') !== false && (strpos($ua, 'tablet pc') == false))) {
            self::$_TYPE = self::TYPE_TABLET;
        } elseif ((strpos($ua, 'android') !== false) && (strpos($ua, 'mobile') === false)) {
            self::$_TYPE = self::TYPE_TABLET;
        } elseif ((strpos($ua, 'firefox') !== false) && (strpos($ua, 'tablet') !== false)) {
            self::$_TYPE = self::TYPE_TABLET;
        } elseif ((strpos($ua, 'kindle') !== false) || (strpos($ua, 'silk') !== false)) {
            self::$_TYPE = self::TYPE_TABLET;
        } elseif ((strpos($ua, 'playbook') !== false)) {
            self::$_TYPE = self::TYPE_TABLET;
        } else {
            self::$_TYPE = self::TYPE_OTHERS;
        }

        // クローラー判定
        foreach (['googlebot', 'baiduspider', 'bingbot', 'yeti', 'naverbot', 'yahoo', 'tumblr', 'livedoor'] as $bot) {
            if ((strpos($ua, $bot) !== false)) {
                self::$_IS_CRAWLER = true;
                break;
            }
        }
    }
}

// 端末初期化
Terminal::init(
    isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
    isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
    isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null,
    isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null
);
