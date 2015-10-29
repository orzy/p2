<?php
/**
 *  P2_Service_Oauth
 *
 *  require
 *      * P2_Service_Base
 *
 *  @version 2.0.0
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  See also
 *  @see http://oauth.net/core/1.0a/
 */
class P2_Service_Oauth extends P2_Service_Base {
	private $_consumerKey;
	private $_consumerSecret;
	private $_accessToken = null;
	private $_accessSecret = null;
	
	/**
	 *	@param	string	$key	Consumer Key
	 *	@param	string	$secret	Consumer Secret
	 *	@param	string	$accessToken	(Optional) Access Token取得済みの場合のみ渡す
	 *	@param	string	$accessSecre	(Optional) Access Token取得済みの場合のみ渡すt
	 */
	public function __construct($key, $secret, $accessToken = "", $accessSecret = "") {
		$this->_consumerKey = $key;
		$this->_consumerSecret = $secret;
		
		if ($accessToken) {
			$this->_accessToken = $accessToken;
			$this->_accessSecret = $accessSecret;
		}
	}
	/**
	 *	@param	string	$reqTokenUrl	Request Token取得URL
	 *	@return	array	Request Token, Request Toekn Secret, 認証用パラメータ等
	 */
	public function prepare($reqTokenUrl) {
		$params = $this->params($reqTokenUrl);
		$res = $this->request($reqTokenUrl . '?' . http_build_query($params), false);
		parse_str($res, $arr);
		return $arr;
	}
	/**
	 *	@param	string	$accessTokenUrl	Access Token取得URL
	 *	@param	string	$reqToken	Request Token
	 *	@param	string	$reqSecret	Request Token Secret
	 *	@param	string	$verifier	OAuth Verifier
	 *	@return	array	Access TokenとAccess Token Secret
	 */
	public function exchange($accessTokenUrl, $reqToken, $reqSecret, $verifier) {
		$this->_params = $this->params(
			$accessTokenUrl,
			array(),
			'GET',
			array($reqToken, $reqSecret, $verifier)
		);
		
		parse_str($this->request($accessTokenUrl, false, 'post'), $arr);
		
		return array($arr['oauth_token'], $arr['oauth_token_secret']);
	}
	/**
	 *	@param	string	$url
	 *	@param	array	$params	パラメータ
	 *	@param	string	$method	HTTP Method (GET or POST)
	 *	@param	array	$_req	Request Token情報
	 *	@return	array
	 */
	public function params($url, array $params = array(), $method = 'GET', $_req = null) {
		$oauthParams = array(
			'oauth_consumer_key' => $this->_consumerKey,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => md5('more unique' . microtime()),
			'oauth_version' => '1.0',	//省略可
			//'oauth_callback' => '',
		);
		
		if ($this->_accessToken) {	//Access Tokenまで取得済みの場合
			$oauthParams['oauth_token'] = $this->_accessToken;
			$tokenSecret = $this->_accessSecret;
		} else if ($_req) {	//Request Token取得済みの場合
			$oauthParams['oauth_token'] = $_req[0];
			$oauthParams['oauth_verifier'] = $_req[2];
			$tokenSecret = $_req[1];
		} else {	//Request Token未取得の場合
			$tokenSecret = '';
		}
		
		$params = array_merge($params, $oauthParams);
		ksort($params);
		$qs = http_build_query($params);
		
		$params['oauth_signature'] = base64_encode(hash_hmac(
			'sha1',
			$method . '&' . rawurlencode($url) . '&' . rawurlencode($qs),
			$this->_consumerSecret . '&' . $tokenSecret,
			true
		));
		
		return $params;
	}
}
