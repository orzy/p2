<?php
/**
 *  P2_List
 *
 *  ※非推奨
 *
 *  require
 *      * P2_Db
 *      * P2_Pager
 *
 *  @version 2.1.6
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class P2_List {
	private $_db;
	private $_select;
	private $_where;
	private $_orderBy;
	
	/**
	 *	SQLをセットする
	 *	@param	string	$select
	 *	@param	string	$from
	 *	@param	array	$where
	 *	@param	string	$orderBy
	 */
	public function setSql($select, $from, array $where, $orderBy) {
		$this->_db = new P2_Db($from);	//DSNセット済みが前提
		$this->_select = $select;
		$this->_where = $where;
		$this->_orderBy = $orderBy;
	}
	/**
	 *	ページング付きで表示する
	 *	@param	integer	$perPage	（省略可）
	 *	@return	boolean	1件以上あったかどうか
	 */
	public function showAsPage($perPage = 10) {
		$totalCount = $this->_db->getCount($this->_where);
		if (!$totalCount) {
			$this->_notFound();
			return false;
		}
		
		$pager = new P2_Pager($totalCount, $perPage, array(
			'prevImg' => '前へ&lt;&lt;',
			'nextImg' => '&gt;&gt;次へ',
			'spacesAfterSeparator' => 0,
		));
		$links = $pager->getLinks();
		
		$others = 'ORDER BY ' . $this->_orderBy . ' LIMIT ' .  $pager->getStartNo() . ", $perPage";
		$rows = $this->_db->select($this->_select, $this->_where, $others);
		
		$this->_header($links);
		foreach ($rows as $row) {
			$this->_body($row);
		}
		$this->_footer($links);
		
		return true;
	}
	/**
	 *	表示する
	 *	@param	integer	$limit	（省略可）
	 *	@param	integer	$start	（省略可）
	 *	@return	boolean	1件以上あったかどうか
	 */
	public function show($limit = 10, $start = 0) {
		$others = 'ORDER BY ' . $this->_orderBy . " LIMIT $start, $limit";
		$rows = $this->_db->select($this->_select, $this->_where, $others);
		
		$this->_header('');
		$i = -1;
		foreach ($rows as $i => $row) {
			$this->_body($row);
		}
		$this->_footer('');
		
		if ($i == -1) {
			$this->_notFound();
			return false;
		} else {
			return true;
		}
	}
	/**
	 *	1件も無かった時に呼ばれる
	 */
	protected function _notFound() {
		echo "該当データ無し\n";
	}
	/**
	 *	リストを出力する前に呼ばれる
	 *	@param	string	$links	ページングありの場合はページングのhtmlが渡される
	 */
	protected function _header($links) {
	}
	/**
	 *	リストの出力
	 *	@param	array	$row	
	 */
	abstract protected function _body(array $row);
	/**
	 *	リストを出力した後に呼ばれる
	 *	@param	string	$links	ページングありの場合はページングのhtmlが渡される
	 */
	protected function _footer($links) {
	}
}
