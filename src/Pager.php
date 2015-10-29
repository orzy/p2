<?php
/**
 *  P2_Pager
 *
 *  ※非推奨 -> 代わりに P2_Pager2 を使う
 *  @see http://code.google.com/p/p2-php-framework/source/browse/trunk/Pager2.php
 *
 *  require
 *      * PEAR::Pager 2.4.5+
 *      * P2_Loader
 *      * P2_Log
 *
 *  @version 2.0.2a
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Pager {
    private $_pager;
    private $_perPage;

	/**
	 *	コンストラクタ
	 *	@param	integer	$totalCount
	 *	@param	integer	$perPage	（省略可）
	 *	@param	array	$optionParams	（省略可）PEAR::Pagerに渡したい独自設定
	 */
    public function __construct($totalCount, $perPage = 10, array $optionParams = array()) {
        P2_Loader::load('Pager');	//PEAR

		//デフォルト設定
        $defaultParams = array(
              'mode'       => 'Sliding'
            , 'totalItems' => $totalCount
            , 'perPage'    => $perPage
            , 'delta'      => 4
            , 'path'       => implode('/', explode('/', $_SERVER['REQUEST_URI'], -1)) //マルチバイト対応
            , 'urlVar'     => 'p'
            , 'altPrev'    => '前へ'
            , 'altNext'    => '次へ'
            , 'prevImg'    => '前へ &lt;&lt;'
            , 'nextImg'    => '&gt;&gt; 次へ'
            , 'separator'  => ''
            , 'spacesAfterSeparator' => 1
        );
		
		$params = array_merge($defaultParams, $optionParams);	//指定があればそれで上書き

        $log = P2_Loader::create('P2_Log');
        $log->strict(false);
        $this->_pager = Pager::factory($params);
        $log->strict(true);

        $this->_perPage = $perPage;
    }
	/**
	 *	ページングのhtmlを取得する
	 *	@return	string
	 */
    public function getLinks() {
        $links = $this->_pager->getLinks();
        return $links['back'] . "&nbsp;&nbsp;&nbsp;" . $links['pages'] . $links['next'];
    }
	/**
	 *	現在表示しているページ番号を取得する
	 *	@return	integer
	 */
    public function getPageNo() {
        $pageNo = $this->_pager->getCurrentPageID();
        if ($pageNo) {
            return $pageNo;
        } else {
            return 1;
        }
    }
	/**
	 *	現在表示しているページの最初のデータの行番号を取得する
	 *	@return	integer
	 */
    public function getStartNo() {
        return ($this->getPageNo() - 1) * $this->_perPage;
    }
}

/* //sample //
require('P2_Loader.php');
$p = new P2_Pager(10, 3);
?>
<<?php ?>?xml version="1.0" encoding="Shift_JIS" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<title>P2_Pager</title>
</head>
<body>
<div>
[getLinks()] <?php echo $p->getLinks(); ?>
<br />
[getPageNo()] <?php echo $p->getPageNo(); ?>
<br />
[getStartNo()] <?php echo $p->getStartNo(); ?>
</div>
</body>
</html>
<!-- */ // -->
