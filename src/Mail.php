<?php
/**
 *  P2_Mail
 *
 *  require
 *      * PEAR::Mail_mimeDecode
 *      * P2_Log
 *      * P2_PublicVars
 *
 *  @version 2.2.1
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Mail extends P2_PublicVars {
    //メールヘッダーに追加するオプションのKEY
    const CC = 'Cc';
    const BCC = 'Bcc';
    const REPLY_TO = 'Reply-To'; //返信先メールアドレス
    const SENDER = 'Sender';     //送信者
    const X_MAILER = 'X-Mailer'; //送信元クライアントアプリケーション名

    public $from;
    public $to;
    public $subject;
    public $body;

    public $fromName; //表示名付きのFrom
    public $toName;   //表示名付きのTo
    public $files;    //受信した添付ファイル（送信はできない）

    /**
     *	コンストラクタ
     *	@param	string	$from	送信元メールアドレス
     *	@param	string	$to	宛先メールアドレス
     *	@param	string	$subject	件名
     *	@param	string	$body	本文
     */
    public function __construct($from, $to, $subject, $body) {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;

        $this->files = array();
    }
    /**
     *	送信元メールアドレスに表示名をセットする
     *	@param	string	$from	表示名
     */
    public function setFromName($name) {
        $this->fromName = P2_Mail::_toDisplayName($this->from, $name);
    }
    /**
     *	宛先メールアドレスに表示名をセットする
     *	@param	string	$from	表示名
     */
    public function setToName($name) {
        $this->toName = P2_Mail::_toDisplayName($this->to, $name);
    }
    /**
     *	メールを送信する
     *	@param	array	$headerVal	（省略可）メールヘッダーの追加オプション
     *	@return	string	送信成功の場合空文字、失敗の場合メール情報
     */
    public function send($headerVal = array()) {
        ini_set("sendmail_from", $this->from);    //WindowsではSMTPのMAIL FROM（エンベロープFrom）に使われる

        //メールヘッダー作成
        if ($this->fromName) {
            $headerVal['From'] = $this->fromName;
        }
        if ($this->toName) {
            $headerVal['To'] = $this->toName;    //表示名付きのToで上書き
        }
		$header = array();
        foreach ($headerVal as $k => $v) {
            $header[] = ucfirst($k) . ": $v";
        }

        //送信（第1引数はSMTPのRCPT TO（エンベロープTO）にも使われる）
        if (@mb_send_mail($this->to, $this->subject, $this->body, implode("\r\n", $header))) {
            return '';
        } else {
            $str = $this->_toString($header);
			error_log($str);
			return $str;
        }
    }
    /**
     *  メール内容を見やすい形にする
	 *	@access	proteced
     *  @param	array	$header	メールヘッダー情報
     */
    protected function _toString($header) {
        $s .= "[MAIL FROM]:{$this->from}\n";
        $s .= "[RCPT TO]:{$this->to}\n";
        $s .= "[header]:\n";
        foreach ($header as $line) {
            $s .= "$line\n";
        }
        $s .= "[subject]:{$this->subject}\n";
        $s .= "[body]\n";
        $s .= "{$this->body}\n";
        return $s;
    }
    /**
     *	返信する
     *	@param	string	$msg	返信メッセージ
     *	@param	array	$header	（省略可）追加するメールヘッダー
     *	@return	string	送信成功の場合空文字、失敗の場合メール情報
     */
    public function reply($msg, $header = array()) {
        //元のメッセージを引用として付ける
        $msg .= "\n\n";
        $lines = split("\n", $this->body);
        foreach ($lines as $line) {
            $msg .= '> ' . $line . "\n";
        }

        $reply = new P2_Mail($this->to, $this->from, 'RE: ' . $this->subject, $msg);

        //メールアドレスの表示名をセット
        if ($this->fromName) {
            $reply->toName = mb_encode_mimeheader($this->fromName);
        }
        if ($this->toName) {
            $reply->fromName = mb_encode_mimeheader($this->toName);
        }

        return $reply->send($header);
    }
    /**
     *	メールのファイルからデータを取り出す
     *	@param	string	$path	メールのファイルパス
     *	@param	string	$data	（省略可）メールの生データ。こちらを渡す場合、パスは不要
     *	@return	P2_Mail	取り出したメール
     */
    public static function file2mail($path, $data = null) {
        $decoded = P2_Mail::decode($path, $data);

        //基本情報を取り出す
        $fromName = P2_Mail::_jis2internal($decoded->headers['from']);
        $toName = P2_Mail::_jis2internal($decoded->headers['to']);

        $from = P2_Mail::_toOnlyEmail($fromName);
        $to = P2_Mail::_toOnlyEmail($toName);
        $subject = P2_Mail::_jis2internal($decoded->headers['subject']);

        $mail = new P2_Mail($from, $to, $subject, '');

        $mail->fromName = $fromName;
        $mail->toName = $toName;

        //本文と添付ファイルを掘り出す
        P2_Mail::_digBody($mail, $decoded);

        return $mail;
    }

    /**
     *	メールを分解する
     *	@param	string	$path	メールのファイルパス
     *	@param	string	$data	（省略可）メールの生データ。こちらを渡す場合、パスは不要
     *	@return	object	分解したメールデータ
     */
    public static function decode($path, $data = null) {
        $log = new P2_Log();
        $log->strict(false);

        if (is_null($data)) {
            $data = file_get_contents($path);
        }

        $decoder = new Mail_mimeDecode($data);	//要PEAR

        $params['decode_headers'] = true;
        $params['include_bodies'] = true;
        $params['decode_bodies']  = true;
        $decoded = $decoder->decode($params);

        $log->strict(true);

        return $decoded;
    }
    /**
     *	本文と添付ファイルを取り出す再帰処理
     *	@access	private
     */
    private static function _digBody($mail, $part) {
        if (is_array($part) || is_object($part)) {    //子要素がある場合は再帰処理
            foreach($part as $child) {
                P2_Mail::_digBody($mail, $child);
            }
        }
        if (!$part->ctype_primary) {
            return;
        }
        switch (strToLower($part->ctype_primary)) {
            case 'multipart':
                return;
            case 'text':    //本文
                if (strToLower($part->ctype_secondary) == "plain") {    //プレーンテキストのみが対象
                    $mail->body = P2_Mail::_jis2internal($part->body);
                }
                break;
            default:    //添付ファイル
                $file['name'] = P2_Mail::_jis2internal($part->d_parameters['filename']);
                $file['type'] = $part->ctype_primary . '/' . $part->ctype_secondary;
                $file['data'] = $part->body;
                $mail->files[] = $file;
        }
    }
    /**
     *	JISから内部文字コードに変換
     *	@access	private
     */
    private static function _jis2internal($str) {
        return mb_convert_encoding($str, mb_internal_encoding(), 'JIS');
    }
    /**
     *	メールアドレスに表示名を加える
     *	@access	private
     */
    private static function _toDisplayName($email, $name) {
        return mb_encode_mimeheader($name) . " <$email>";
    }
    /**
     *	メールアドレスだけを取り出す
     *	@access	private
     */
    private static function _toOnlyEmail($displayName) {
        return preg_replace('/(^.*<|>$)/', '', $displayName);
    }
}
