<?php
/**
 *  P2_Service_Flickr
 *
 *  require
 *      * P2_Service_Base
 *
 *  @version 2.2.2
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Flickr extends P2_Service_Base {

	const URL_REST = 'http://api.flickr.com/services/rest/';
	
	public $error;

	private $_apiKey;

	public function __construct($apiKey) {
		$error = '';
		$this->_apiKey = $apiKey;
	}
	/**
	 *	写真検索
	 *	・検索条件をaddParam()してからこの関数を呼ぶ
	 *	@param	string	$searchKeyword	(省略可)
	 *	@see http://www.flickr.com/services/api/flickr.photos.search.html
	 */
	public function search($searchKeyword = '') {
		$this->addParam('method', 'flickr.photos.search');
		if ($searchKeyword) {
			$this->addParam('text', $searchKeyword);
		}
		$arr = $this->_sendRequest();
		return $arr['photos'];
	}
	/**
	 *	ユーザー情報取得
	 *	@param	string	$userId	（例）12345678@N00
	 *	@see http://www.flickr.com/services/api/flickr.people.getInfo.html
	 */
	public function getPersonInfo($userId) {
		$this->addParam('method', 'flickr.people.getInfo');
		$this->addParam('user_id', $userId);
		$arr = $this->_sendRequest();
		return $arr['person'];
	}
	
	private function _sendRequest() {
		$this->addParam('api_key', $this->_apiKey);
		$this->addParam('format', 'php_serial');

		$serial = parent::request(P2_Service_Flickr::URL_REST, false);
		$arr = unserialize($serial);

		if ($arr['stat'] != 'ok'){
			$this->status = P2_Service_Base::SATUS_SERVICE_ERROR;
			$this->error = $arr['message'];
			throw new Exception('リクエストに失敗しました');
		}

		return $arr;
	}
	/**
	 *	@see http://www.flickr.com/services/api/misc.urls.html
	 */
	public function getUrl(array $photo, $size = '') {
		$url = 'http://farm' . $photo['farm'] . '.static.flickr.com/';
		$url .= $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'];
		if ($size) {
			$url .= '_' . $size;
		}
		$url .= '.jpg';
		return $url;
	}
	
	public function getImg(array $photo, $size = '') {
		$img = '<img src="' . $this->getUrl($photo, $size) . '"';
		$img .= ' alt="' . htmlspecialchars($photo['title'], ENT_QUOTES) . '" />';
		return $img . "\n";
	}
	/**
	 *	@see http://www.flickr.com/services/api/misc.urls.html
	 */
	public function getPhotoPageUrl($ownerId, $photoId, $mobileFlg = false) {
		if ($mobileFlg) {
			return 'http://m.flickr.com/photo.gne?id=' . $photoId;
		} else {
			return 'http://www.flickr.com/photos/' . $ownerId . '/' . $photoId;
		}
	}
}
