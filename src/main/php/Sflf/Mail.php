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
 * @version   v1.0.1
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2017 github.com/rain-noise
 * @license   MIT License https://github.com/rain-noise/sflf/blob/master/LICENSE
 */
class Mail {
	
	/**
	 * 件名
	 * @var string
	 */
	private $_subject;
	
	/**
	 * 宛先(To)
	 * @var array
	 */
	private $_to;
	
	/**
	 * 宛先(Cc)
	 * @var array
	 */
	private $_cc;
	
	/**
	 * 宛先(Bcc)
	 * @var array
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
	 * @var array
	 */
	private $_body;
	
	/**
	 * コンストラクタ
	 */
	public function __construct() {
	}
	
	/**
	 * 件名を設定します。
	 * 
	 * @param  string $subject
	 * @return void
	 */
	public function setSubject($subject) {
		$this->_subject = $subject;
	}
	
	/**
	 * 宛先(To)を設定します。
	 * 
	 * @param  string ...$to
	 * @return void
	 */
	public function setTo(...$to) {
		$this->_to = $to;
	}
	
	/**
	 * 宛先(Cc)を設定します。
	 * 
	 * @param  string ...$cc
	 * @return void
	 */
	public function setCc(...$cc) {
		$this->_cc = $cc;
	}
	
	/**
	 * 宛先(Bcc)を設定します。
	 * 
	 * @param  string ...$bcc
	 * @return void
	 */
	public function setBcc(...$bcc) {
		$this->_bcc = $bcc;
	}
	
	/**
	 * 送信元(From)を設定します。
	 * 
	 * @param  string $from
	 * @return void
	 */
	public function setFrom($from) {
		$this->_from = $from;
	}
	
	/**
	 * 返信先(Reply-To)を設定します。
	 * 
	 * @param  string $replyTo
	 * @return void
	 */
	public function setReplyTo($replyTo) {
		$this->_replyTo = $replyTo;
	}
	
	/**
	 * 本文を設定します。
	 * 
	 * @param  string $body
	 * @return void
	 */
	public function setBody($body) {
		$this->_body = $body;
	}
	
	/**
	 * メールを送信します。
	 * 
	 * @return void
	 * @throws MailSendException
	 */
	public function send() {
		
		$parameter = array();
		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP";
		
		// 件名
		if(empty($this->_subject)) {
			throw new MailSendException("Mail 'subject' not set.");
		}
		$subject = mb_encode_mimeheader($this->_subject, 'UTF-8', 'B', "\n");
		
		// 送信元(From)
		if(empty($this->_from)) {
			throw new MailSendException("Mail 'from' not set.");
		}
		$from    = $this->_mailAddressEncode($this->_from);
		$replyTo = $this->_mailAddressEncode($this->_replyTo);
		$headers[]   = "From: ".$from;
		$headers[]   = "Reply-To: ".(empty($replyTo) ? $from : $replyTo);
		$parameter[] = "-f ".$this->_pickMailAddress($this->_from);
		
		// 宛先(To)
		if(empty($this->_to)) {
			throw new MailSendException("Mail 'to' not set.");
		}
		$tos = array();
		foreach ($this->_to AS $address) {
			$tos[] = $this->_mailAddressEncode($address);
		}
		$to = join(",", $tos);
		
		// 本文
		if(empty($this->_body)) {
			throw new MailSendException("Mail 'body' not set.");
		}
		$headers[] = "Content-Type: text/plain; charset=UTF-8";
		$headers[] = "Content-Transfer-Encoding: base64";
		$body = wordwrap(base64_encode($this->_body), 70, PHP_EOL, true);
		
		// 宛先(Cc)
		if(!empty($this->_cc)) {
			$ccs = array();
			foreach ($this->_cc AS $cc) {
				$ccs[] = $this->_mailAddressEncode($cc);
			}
			$headers[] = "Cc: ".join(",", $ccs);
		}
		
		// 宛先(Bcc)
		if(!empty($this->_bcc)) {
			$bccs = array();
			foreach ($this->_bcc AS $bcc) {
				$bccs[] = $this->_mailAddressEncode($bcc);
			}
			$headers[] = "Bcc: ".join(",", $bccs);
		}
		
		if(!mail($to, $subject, $body, join(PHP_EOL, $headers), join(' ',$parameter)) ) {
			throw new MailSendException("Mail send faild.");
		}
	}
	
	/**
	 * メールアドレスをエンコードします。
	 * 
	 * @param  string $address メールアドレス
	 * @return string エンコード済みメールアドレス
	 */
	private function _mailAddressEncode($address) {
		if(empty($address)) { return null; }
		$matches = array();
		if(preg_match('/^("([^"].*)" *)|(([^<].*) *)<(.*)>$/', trim($address), $matches)) {
			return mb_encode_mimeheader(trim(!empty($matches[2]) ? $matches[2] : $matches[4]), 'UTF-8', 'B', "\n")."<".$matches[5].">";
		}
		return "<".$address.">";
	}
	
	/**
	 * メールアドレス文字列からメールアドレスのみを抽出します。
	 * 
	 * @param  string $address メールアドレス文字列
	 * @return string メールアドレス
	 */
	private function _pickMailAddress($address) {
		if(empty($address)) { return null; }
		$matches = array();
		if(preg_match('/^("([^"].*)" *)|(([^<].*) *)<(.*)>$/', trim($address), $matches)) {
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
class MailSendException extends RuntimeException {
	public function __construct ($message, $code=null, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}
