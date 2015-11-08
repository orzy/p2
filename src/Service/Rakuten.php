<?php
/**
 *  P2_Service_Rakuten
 *
 *  require
 *      * P2_Service_Base
 *
 *  @version 2.2.2a
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Rakuten extends P2_Service_Base {
	
	const API_URL = 'http://api.rakuten.co.jp/rws/';
	
	public $version = '2.0';
	public $versionDate = '2009-04-15';
	
	public $count;
	
	private $_devId;
	private $_affId;

	public function __construct($developerId, $affiliateId = '') {
		parent::__construct();
		$this->_devId = $developerId;
		$this->_affId = $affiliateId;
	}
	
	/**
	 *	@see http://webservice.rakuten.co.jp/api/itemsearch/
	 */
	public function searchItems($keyword = '') {
		if ($keyword) {
			$this->addParam('keyword', $keyword);
		}
		return $this->_sendRequest('ItemSearch');
	}
	
	/**
	 *	@see http://webservice.rakuten.co.jp/api/booksbooksearch/
	 */
	public function searchBooks() {
		return $this->_sendRequest('BooksBookSearch');
	}
	
	/**
	 *	最新(2009-10-20)にしても2009-09-09で返ってくるので2009-09-09にした
	 *	@see http://webservice.rakuten.co.jp/api/simplehotelsearch/
	 *	@see http://webservice.rakuten.co.jp/api/simplehotelsearch/2009-09-09.html
	 */
	public function searchHotels($version = '3.0', $versionDate = '2009-09-09') {
		$this->version = $version;
		$this->versionDate = $versionDate;
		
		return $this->_sendRequest('SimpleHotelSearch');
	}
	
	private function _sendRequest($operation) {
		$this->count = 0;

		$this->addParam('developerId', $this->_devId);
		if ($this->_affId) {
			$this->addParam('affiliateId', $this->_affId);
		}
		$this->addParam('operation', $operation);
		$this->addParam('version', $this->versionDate);
		
		$xml = parent::request(self::API_URL . $this->version . '/rest');
		
		$status = $this->_getStatus($xml->children(self::API_URL . 'rest/Header'));
		switch ($status) {
			case 'Success':
				break;
			case 'NotFound':
				return null;
			default:
				throw new Exception($status);
		}
		
		$holder = $xml->Body->children(
			self::API_URL . "rest/$operation/" . $this->versionDate	//Name Space
		);
		
		if ($this->_params['operation'] == 'SimpleHotelSearch') {
			return $holder;
		}
		
		foreach ($holder->children() as $child) {
			switch ($child->getName()) {
				case 'count':
					$this->count = (integer)$child;
					break;
				case 'Items':
					return $child;
			}
		}
		
		throw new Exception('no items');	//Itemsが無いのは想定外
	}
	
	private function _getStatus($header) {
		foreach ($header->children() as $child) {
			if ($child->getName() == 'Status') {
				return (string)$child;
			} 
		}
		throw new Exception('no status');
	}
}
