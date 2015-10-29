<?php
/**
 *  P2_Pager2
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.0
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Pager2 {
	private $_params;
	private $_currentPageNo;
    private $_total;
    private $_links;

	/**
	 *	コンストラクタ
	 *	@param	integer	$total
	 *	@param	array	$options	（省略可）オプション設定
	 */
    public function __construct($total, array $options = array()) {
		$this->_total = $total;

		//デフォルト設定（KEYはなるべくPEAR::Pagerに合わせた）
        $defaultParams = array(
              'linkCount'	=> 9	//表示するページ番号リンクの数（奇数を推奨）
            , 'perPage'		=> 10
            , 'urlVar'		=> 'p'
            , 'prev'		=> '前へ &lt;&lt;'
            , 'next'		=> '&gt;&gt; 次へ'
            , 'more'		=> '. . .'
            , 'separator'	=> ''
        );
		
		//適用する設定
		$params = array_merge($defaultParams, $options);
		$this->_params = $params;
		
		//現在のページ番号
		if (isset($_GET[$params['urlVar']])) {
			$this->_currentPageNo = $_GET[$params['urlVar']];
		} else {
			$this->_currentPageNo = 1;
		}
	}
	/**
	 *	現在表示しているページの最初のデータの行番号を取得する
	 *	@return	integer
	 */
    public function getStartNo() {
        return ($this->_currentPageNo - 1) * $this->_params['perPage'];
    }
	/**
	 *	ページャーのリンクを取得する
	 *	@return	integer
	 */
    public function getLinks() {
    	if (!$this->_links) {
    		$this->_build();
    	}
        return $this->_links;
    }
	/**
	 *	全件数とその中の何ページ目かを取得する
	 *	@return	string
	 */
    public function getSummary() {
    	$start = $this->getStartNo();
        $summary = number_format($start + 1) . ' - ';
        $summary .= number_format($start + $this->_params['perPage']);
        $summary .= ' of ' . number_format($this->_total);
        return $summary;
    }
	/**
	 *	@access	private
	 */
	private function _build() {
		$prm = $this->_params;
		$current = $this->_currentPageNo;
		
		//最後のページ番号
		$last = ceil($this->_total / $prm['perPage']);
		if ($last == 1) {
			return;	//ページャー表示無し
		}
		
		//表示するページ番号の範囲
		$range = $prm['linkCount'] - 1;
		$min = max(1, min($current - (ceil($range / 2)), $last - $range));
		$max = min($last, $min + $range);
		
		list($firstUrl, $queryUrl) = $this->_getUrls($prm['urlVar']);
		
		//リンク作成
		$links = '';
		if ($current != 1) {	//前へ
			$a = $this->_a($firstUrl, $queryUrl, $current - 1, $prm['prev']);
			$links .= $this->_span('pager-prev', $a);
			$links .= $prm['separator'];
		}
		if ($min != 1 && $prm['more']) {	//まだある場合
			$links .= $this->_span('pager-more', $prm['more']);
		}
		for ($i = $min; $i < $max + 1; $i++) {
			if ($i == $current) {	//現在のページ
				$links .= $this->_span('pager-current', $i);
			} else {
				$links .= $this->_a($firstUrl, $queryUrl, $i, $i);
			}
			if ($i != $max) {
				$links .= $prm['separator'];
			}
		}
		if ($max != $last && $prm['more']) {	//まだある場合
			$links .= $this->_span('pager-more', $prm['more']);
		}
		if ($current != $last) {	//次へ
			$links .= $prm['separator'];
			$a = $this->_a($firstUrl, $queryUrl, $current + 1, $prm['next']);
			$links .= $this->_span('pager-next', $a);
		}
		
		$this->_links = $links;
    }
	/**
	 *	URLを取得
	 *	@access	private
	 */
	private function _getUrls($urlVar) {
		parse_str($_SERVER['QUERY_STRING'], $queryArr);
		unset($queryArr[$urlVar]);	//ページ番号はいったん削除
		$query = http_build_query($queryArr, '', '&amp;');
		
		$firstUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$queryUrl = $firstUrl . '?' . $query;
		if ($query) {
			$firstUrl = $queryUrl;
			$queryUrl .= '&amp;';
		}
		$queryUrl .= $urlVar . '=';
		
		return array($firstUrl, $queryUrl);
	}
	/**
	 *	span要素を生成
	 *	@access	private
	 */
	private function _span($class, $inner) {
		return '<span class="' . $class . '">' . $inner . '</span>';
	}
	/**
	 *	a要素を生成
	 *	@access	private
	 */
	private function _a($firstUrl, $queryUrl, $pageNo, $label) {
		if ($pageNo == 1) {	//1ページ目はページ番号無し
			$url = $firstUrl;
		} else {
			$url = $queryUrl . $pageNo;
		}
		return '<a href="' . $url . '">' . $label . '</a>';
	}
}
