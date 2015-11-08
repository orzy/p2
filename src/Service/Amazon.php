<?php
/**
 *  P2_Service_Amazon
 *
 *  require
 *      * Hash (PHP 5.1.2+)
 *      * P2_Service_Base
 *
 *  @version 2.2.7
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Amazon extends P2_Service_Base {
	const URL_US = 'http://ecs.amazonaws.com/onca/xml';
	
	public $url = 'http://ecs.amazonaws.jp/onca/xml';	//日本のAmazon
	public $apiVersion = '2009-03-31';
	public $errors;
	
	private $_id;
	private $_secretKey;
	private $_associateTag;

	public static function getUrl($asin, $associateTag) {
		return "http://amazon.jp/o/asin/$asin/$associateTag";
	}

	public function __construct($accessKeyId, $secretAccessKey, $associateTag) {
		parent::__construct();
		$this->errors = null;
		$this->_id = $accessKeyId;
		$this->_secretKey = $secretAccessKey;
		$this->_associateTag = $associateTag;	//2011-10-26から必須になった
	}
	/**
	 *	@see http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/ItemSearch.html
	 */
	public function searchItems($searchIndex) {
		$this->addParam('Operation', 'ItemSearch');
		$this->addParam('SearchIndex', $searchIndex);
		
		return $this->_sendRequest();
	}
	
	public function lookUpItem($itemId) {
		$this->addParam('Operation', 'ItemLookup');
		$this->addParam('ItemId', $itemId);
		
		$xml = $this->_sendRequest();
		return $xml->Item;
	}
	/**
	 *	@see http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/rest-signature.html
	 */
	private function _sendRequest() {
		$this->addParam('Service', 'AWSECommerceService');
		$this->addParam('AWSAccessKeyId', $this->_id);
		$this->addParam('AssociateTag', $this->_associateTag);
		$this->addParam('Version', $this->apiVersion);
		$this->addParam('Timestamp', gmdate('Y-m-d\TH:i:s\Z'));
		
		ksort($this->_params);
		
		//暗号化する文字列を作る
		$parsed = parse_url($this->url);
		$src = "GET\n";
		$src .= $parsed['host'] . "\n";
		$src .= $parsed['path'] . "\n";
		//RFC 3986のURLエンコードに合わせる
		$src .= strtr($this->_arr2qs('UTF-8'), array('%7E' => '~', '+' => '%20'));
		
		//署名を作る
		$hash = hash_hmac('sha256', $src, $this->_secretKey, true);
		$this->addParam('Signature', base64_encode($hash));
		
		$xml = parent::request($this->url);
		if ($xml->Items->Request->IsValid == 'False') {
			$this->errors = $xml->Items->Request->Errors;
			$this->status = P2_Service_Base::SATUS_SERVICE_ERROR;
			throw new Exception('リクエストに誤りがあります');
		}
		return $xml->Items;
	}
}
