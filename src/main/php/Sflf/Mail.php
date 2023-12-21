<?php
//namespace Sflf; // 名前空間が必要な場合はコメントを解除して下さい。（任意の名前空間による設定も可）

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 メール クラス
 * 以下の形式でメールを作成／送信します。
 * 件名　　 ： UTF-8/base64 エンコード
 * アドレス ： UTF-8/base64 エンコード
 * 本文　　 ： Content-Type: text/plain; charset=UTF-8
 * 　　　　 ： Content-Transfer-Encoding: base64
 *
 * 【使い方】
 * require_once "/path/to/Mail.php"; // or use AutoLoader
 *
 * // If you don't want to send mail when you are developing
 * Mail::$SENDER = function(Mail $mail) {
 *     Log::debug("***** MAIL *****\n{$mail}\n**********");
 *     return true; // return false if you want to send a mail with default behavior
 * };
 *
 * $mail = new Mail();
 * $mail->setSubject('○○のお知らせ');
 * $mail->setTo('会員氏名<user@sample.com>');
 * $mail->setFrom('○○事務局<info@your.domain.com>');
 * $mail->setBcc('info@your.domain.com');
 * $body = new Smarty();
 * $body->assign('user', $user);
 * $mail->setBody($body->fetch('mail/register-thanks.tpl'));
 * $mail->send();
 *
 * @package   SFLF
 * @version   v1.2.3
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Mail
{
    /**
     * メール送信ロジック
     * ※検証環境などでメールを送信せずにログ出力する場合などは本送信ロジックを上書きして下さい。
     *
     * @var callable(Mail) function(Mail $mail){ ... }
     */
    public static $SENDER = null;

    /**
     * 件名
     * @var string
     */
    private $_subject;

    /**
     * 宛先(To)
     * @var string[]
     */
    private $_to;

    /**
     * 宛先(Cc)
     * @var string[]
     */
    private $_cc;

    /**
     * 宛先(Bcc)
     * @var string[]
     */
    private $_bcc;

    /**
     * 送信元
     * @var string
     */
    private $_from;

    /**
     * 返信先
     * @var string
     */
    private $_replyTo;

    /**
     * 本文
     * @var string
     */
    private $_body;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
    }

    /**
     * 件名を設定します。
     *
     * @param string $subject 件名
     * @return void
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * 件名を取得します。
     *
     * @return string 件名
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * 宛先(To)を設定します。
     *
     * @param string ...$to 宛先(To)
     * @return void
     */
    public function setTo(...$to)
    {
        $this->_to = $to;
    }

    /**
     * 宛先(To)を取得します。
     *
     * @return string[] 宛先(To)
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * 宛先(Cc)を設定します。
     *
     * @param string ...$cc 宛先(Cc)
     * @return void
     */
    public function setCc(...$cc)
    {
        $this->_cc = $cc;
    }

    /**
     * 宛先(Cc)を取得します。
     *
     * @return string[] 宛先(Cc)
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * 宛先(Bcc)を設定します。
     *
     * @param string ...$bcc 宛先(Bcc)
     * @return void
     */
    public function setBcc(...$bcc)
    {
        $this->_bcc = $bcc;
    }

    /**
     * 宛先(Bcc)を取得します。
     *
     * @return string[] 宛先(Bcc)
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * 送信元(From)を設定します。
     *
     * @param string $from 送信元(From)
     * @return void
     */
    public function setFrom($from)
    {
        $this->_from = $from;
    }

    /**
     * 送信元(From)を取得します。
     *
     * @return string 送信元(From)
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * 返信先(Reply-To)を設定します。
     *
     * @param string $reply_to 返信先(Reply-To)
     * @return void
     */
    public function setReplyTo($reply_to)
    {
        $this->_replyTo = $reply_to;
    }

    /**
     * 返信先(Reply-To)を取得します。
     *
     * @return string 返信先(Reply-To)
     */
    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    /**
     * 本文を設定します。
     *
     * @param string $body 本文
     * @return void
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * 本文を取得します。
     *
     * @return string 本文
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * メールオブジェクト文字列化します
     */
    public function __toString()
    {
        $text  = "";
        $text .= "Subject: {$this->_subject}\n";
        $text .= "From: {$this->_from}\n";
        $text .= "To: ".join(', ', $this->_to)."\n";
        if (!empty($this->_cc)) {
            $text .= "Cc: ".join(', ', $this->_cc)."\n";
        }
        if (!empty($this->_bcc)) {
            $text .= "Bcc: ".join(', ', $this->_bcc)."\n";
        }
        if (!empty($this->_replyTo)) {
            $text .= "Reply-To: {$this->_replyTo}\n";
        }
        $text .= "\n";
        $text .= "{$this->_body}";

        return $text;
    }

    /**
     * メールを送信します。
     *
     * @return void
     * @throws MailSendException
     */
    public function send()
    {
        $sender = self::$SENDER;
        if (is_callable($sender)) {
            if ($sender($this)) {
                return;
            }
        }

        $headers   = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "X-Mailer: PHP";

        // 件名
        if (empty($this->_subject)) {
            throw new MailSendException("Mail 'subject' not set.");
        }
        $subject = mb_encode_mimeheader($this->_subject, 'UTF-8', 'B', "\n");

        // 送信元(From)
        if (empty($this->_from)) {
            throw new MailSendException("Mail 'from' not set.");
        }
        $from       = self::encodeMailAddress($this->_from);
        $reply_to   = self::encodeMailAddress($this->_replyTo);
        $headers[]  = "From: ".$from;
        $headers[]  = "Reply-To: ".(empty($reply_to) ? $from : $reply_to);

        // 宛先(To)
        if (empty($this->_to)) {
            throw new MailSendException("Mail 'to' not set.");
        }
        $tos = [];
        foreach ($this->_to as $address) {
            $tos[] = self::encodeMailAddress($address);
        }

        // 本文
        if (empty($this->_body)) {
            throw new MailSendException("Mail 'body' not set.");
        }
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";
        $body      = wordwrap(base64_encode($this->_body), 70, PHP_EOL, true);

        // 宛先(Cc)
        if (!empty($this->_cc)) {
            $ccs = [];
            foreach ($this->_cc as $cc) {
                $ccs[] = self::encodeMailAddress($cc);
            }
            $headers[] = "Cc: ".join(",", $ccs);
        }

        // 宛先(Bcc)
        if (!empty($this->_bcc)) {
            $bccs = [];
            foreach ($this->_bcc as $bcc) {
                $bccs[] = self::encodeMailAddress($bcc);
            }
            $headers[] = "Bcc: ".join(",", $bccs);
        }

        if (!mail(join(",", $tos), $subject, $body, join(PHP_EOL, $headers), "-f ". self::pickMailAddress($this->_from))) {
            throw new MailSendException("Mail send faild.");
        }
    }

    /**
     * メールアドレスをエンコードします。
     *
     * @param string $address           メールアドレス
     * @param string $charset           文字コード (default: UTF-8)
     * @param string $transfer_encoding 転送エンコード (default: B)
     * @param string $linefeed          改行コード (default: \n)
     * @return string|null エンコード済みメールアドレス
     */
    public static function encodeMailAddress($address, $charset = 'UTF-8', $transfer_encoding = 'B', $linefeed = "\n")
    {
        if (empty($address)) {
            return null;
        }
        $matches = [];
        if (preg_match('/^("([^"].*)" *)|(([^<].*) *)<(.*)>$/', trim($address), $matches)) {
            return mb_encode_mimeheader(trim(!empty($matches[2]) ? $matches[2] : $matches[4]), $charset, $transfer_encoding, $linefeed)."<".$matches[5].">";
        }
        return "<".$address.">";
    }

    /**
     * メールアドレス文字列からメールアドレスのみを抽出します。
     *
     * @param string $address メールアドレス文字列
     * @return string|null メールアドレス
     */
    public static function pickMailAddress($address)
    {
        if (empty($address)) {
            return null;
        }
        $matches = [];
        if (preg_match('/^("([^"].*)" *)|(([^<].*) *)<(.*)>$/', trim($address), $matches)) {
            return $matches[5];
        }
        return $address;
    }
}

/**
 * Single File Low Functionality Class Tools
 *
 * ■単一ファイル低機能 メール送信関連エラー クラス（Mail付帯クラス）
 *
 * @package   SFLF
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class MailSendException extends \RuntimeException
{
    /**
     * メール送信関連例外を構築します
     *
     * @param string          $message  エラーメッセージ
     * @param int             $code     エラーコード (default: 0)
     * @param \Throwable|null $previous 原因例外 (default: null)
     * @return MailSendException
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
