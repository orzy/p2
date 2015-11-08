<?php
/**
 *  P2_Service_Twitter
 *
 *  require
 *      * P2_Service_Oauth
 *      * php-json
 *
 *  @version 2.3.1
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  See also
 *  @see http://dev.twitter.com/doc
 *  @see http://dev.twitter.com/pages/auth
 */
class P2_Service_Twitter extends P2_Service_Oauth {
	const API_URL = 'https://api.twitter.com/1/';
	const OAUTH_URL = 'https://api.twitter.com/oauth/';
	
	private $_oauth;
	
	/**
	 *	ストリームで特定のキーワードを受信し続ける
	 *	・受信すると、$callback->answer()にarray()が渡される
	 *	・受信時に受信を止めたい場合は、$callback->answer()の戻り値をfalseにする
	 *	・TweetのidはPHPでは桁あふれするため、id_strを使うこと
	 *	@param	string	$screenName	TwitterアカウントのユーザーID
	 *	@param	string	$password	Twitterアカウントのパスワード
	 *	@param	mixed	$words	キーワードの文字列 or 配列
	 *	@param	object	$callback
	 *	@return	boolean	true:$callbackによる終了時、false:接続失敗
	 *	@throw	Exception
	 *	@see http://dev.twitter.com/pages/streaming_api
	 */
	public static function trackStream($screenName, $password, $words, $callback) {
		$url = 'https://' . $screenName . ':' . $password;
		$url .= '@stream.twitter.com/1/statuses/filter.json?track=';
		$url .= rawurlencode(implode(',', (array)$words));
		
		$param = array('http' => array('method' => 'POST'));
		$stream = fopen($url, 'r', false, stream_context_create($param));
		
		if (!$stream) {
			return false;
		}
		
		while ($json = fgets($stream)) {
			if (!$json) {
				continue;
			}
			
			$decoded = json_decode($json, true);
			
			try {
				if (is_array($decoded) && $callback->answer($decoded) === false) {
					fclose($stream);
					return true;
				}
			} catch (Exception $e) {
				fclose($stream);
				throw $e;
			}
		}
	}
	/**
	 *	検索のみならパラメータは不要
	 *	Access Toekn取得前ならAccess TokenとAccess Token Secretは不要
	 */
	public function __construct($key = "", $secret = "", $acsToken = "", $acsSecret = "") {
		parent::__construct($key, $secret, $acsToken, $acsSecret);
	}
	/**
	 *	検索
	 *	@param	string	$word
	 *	@return	array
	 *	@see http://dev.twitter.com/doc/get/search
	 */
	public function search($word) {
		$url = 'http://search.twitter.com/search.json';
		$this->addParam('q', $word);
		$json = json_decode($this->request($url, false), true);
		return $json['results'];
	}
	
	public function tweet($text) {
		return $this->_api_post('statuses/update.json', array('status' => $text));
	}
	
	public function follow($name) {
		$this->_api_post('friendships/create.json', array('screen_name' => $name));
	}
	
	private function _api_post($url, array $params = array()) {
		$url = self::API_URL . $url;
		$this->_params = $this->params($url, $params, 'POST');
		return $this->request($url, false, 'POST');
	}
	
	public function oauthAuthorize() {
		$arr = $this->prepare(self::OAUTH_URL . 'request_token');
		$arr['url'] = self::OAUTH_URL . 'authorize?oauth_token=' . $arr['oauth_token'];
		return $arr;
	}
	
	public function oauthExchange($requestToken, $requestSecret, $verifier) {
		return $this->exchange(
			self::OAUTH_URL . 'access_token',
			$requestToken,
			$requestSecret,
			$verifier
		);
	}
}
