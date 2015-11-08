<?php
/**
 *  P2_Service_Google
 *
 *  require
 *      * P2_Service_Base
 *      * php-json
 *
 *  @version 2.2.0
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Google extends P2_Service_Base {
	
	private $_apiKey = '';
	
	/**
	 *	@param	string	$apiKey	Google API Key
	 */
	public function __construct($apiKey) {
		$this->_apiKey = $apiKey;
	}
	
	/**
	 *	検索
	 *	@param	string	$cx	Googleカスタム検索のID
	 *	@param	string	$word	検索キーワード
	 *	@return	array
	 *	@see http://code.google.com/intl/ja/apis/customsearch/v1/using_rest.html
	 */
	public function search($cx, $word) {
		$url = 'https://www.googleapis.com/customsearch/v1';
		$this->addParam('key', $this->_apiKey);
		$this->addParam('cx', $cx);
		$this->addParam('q', $word);
		$json = json_decode($this->request($url, false), true);
		return $json['items'];
	}
}
