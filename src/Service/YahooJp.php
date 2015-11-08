<?php
/**
 *  P2_Service_YahooJp
 *
 *  require
 *      * P2_Service_Base
 *
 *  @version 2.2.1
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_YahooJp extends P2_Service_Base {
	
	const URL_MA_PARSE = 'http://jlp.yahooapis.jp/MAService/V1/parse';
	
	private $_appId;

	public function __construct($appId) {
		$this->_appId = $appId;
	}
	
	/**
	 *	morphologic analysis
	 *	@see http://developer.yahoo.co.jp/jlp/MAService/V1/parse.html
	 */
	public function parseMa($sentence) {
		$this->addParam('appid', $this->_appId);
		$this->addParam('sentence', $sentence);
		
		$xml = $this->request(P2_Service_YahooJp::URL_MA_PARSE);
		
		if ($xml->ma_result) {
			$ma = $xml->ma_result->word_list->word;
		}
		if ($xml->uniq_result) {
			$uniq = $xml->uniq_result->word_list->word;
		}
		
		if ($ma && $uniq) {
			return compact('ma', 'uniq');
		} else if ($ma) {
			return $ma;
		} else {
			return $uniq;
		}
	}
	
	public function embedLink($sentence, $baseUrl, array $target = array('名詞')) {
		$this->clearParams();
		$this->addParam('response', 'surface,pos,baseform');
		$this->addParam('filter', implode(range(1, 13), '|'));	//全て
		$ma = $this->parseMa($sentence);
		
		$s = '';
		foreach ($ma as $word) {
			$text = (string)$word->surface;
			if (in_array($word->pos, $target) && !is_numeric($text)) {
				$s .= '<a href="' . $baseUrl . urlencode($word->baseform) . '">';
				$s .= htmlspecialchars($text, ENT_QUOTES) . '</a>';
			} else {
				$s .= htmlspecialchars($text, ENT_QUOTES);
			}
		}
		
		return $s;
	}
}
