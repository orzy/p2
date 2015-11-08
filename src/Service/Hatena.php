<?php
/**
 *  P2_Service_Hatena
 *
 *  require
 *      * P2_Service_Feed
 *
 *  @version 2.2.0a
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Hatena extends P2_Service_Feed {
	
	/**
	 *	タグによるブックマーク検索
	 *	@param	string	$tag
	 *	@param	string	$sort	（省略可）並び順（新着=eid, 注目=hot, 人気=count）
	 *	@param	string	$count	（省略可）取得対象にするブックマーク数の閾値。新着では無効
	 *	@return	array
	 */
	public function getBookmarksByTag($tag, $sort = 'hot', $count = 3) {
		$url = 'http://b.hatena.ne.jp/t/' . urlencode($tag);
		$this->addParam('mode', 'rss');
		$this->addParam('sort', $sort);
		$this->addParam('threshold', $count);
		$xmls = $this->get($url);
		
		$arr = array();
		foreach ($xmls as $xml) {
			$data = array();
			foreach ($xml as $key => $value) {
				$data[$key] = (string)$value;
			}

			//ダブリン・コア要素の取り出し
			foreach ($xml->children('http://purl.org/dc/elements/1.1/') as $name => $dc) {
				$data[$name] = (string)$dc;
			}

			//2008年秋のリニューアルでタグは配信されなくなった
			$data['tags'] = array();	//下位互換のため

			$arr[] = $data;
		}
		
		return $arr;
	}
}
