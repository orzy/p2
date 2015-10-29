<?php
/**
 *  P2_Feeder
 *
 *  ※非推奨 -> 代わりに P3_Feed を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Feed.php
 *
 *  require
 *      * FeedCreator ( http://www.bitfolge.de/rsscreator-en.html )
 *      * P2_Loader
 *
 *  @version 2.2.0a
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
define('TIME_ZONE', '');    //FeedCreator内での宣言を無効にする
P2_Loader::load('FeedCreator');	//pathが違う場合は事前にclassPath登録が必要

class P2_Feeder extends UniversalFeedCreator {
    protected $_encoding;
    protected $_format;
    protected $_savePath;
    
	/**
	 *	コンストラクタ
	 *	@param	string	$format	フィードの種類
	 *	@param	string	$savePath	（省略可）キャッシュファイルの保存パス
	 *	@param	string	$encoding	（省略可）文字コード
	 *	@param	integer	$timeout	（省略可）キャッシュ期間（秒）。デフォルトは1日
	 */
    public function __construct($format, $savePath = '', $encoding = '', $timeout = 86400) {
    	//FeedCreatorのより少し丁寧な期限設定
        if (file_exists($savePath)) {
        	$fileTime = filemtime($savePath);
			if (time() - $fileTime < $timeout) {
	        	$since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	        	if ($since && $since == $fileTime) {
					header('HTTP/1.1 304 Not Modified');
					exit;
				}
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $fileTime));
			}
            $expireTime = $fileTime + $timeout;
        } else {
            $expireTime = time() + $timeout;
        }
        header('Expires: ' . date('r', $expireTime));
       
        $this->_encoding = $encoding;
       
        $this->useCached($format, $savePath, $timeout);
       
        $this->_format = $format;
        $this->_savePath = $savePath;
    }
    /**
	 *	アイテムを追加する
	 *	@param	FeedItem	$item
	 */
    public function addItem($item) {
        $item->descriptionHtmlSyndicated = true;
        $item->source = $this->link;
       
        parent::addItem($item);
    }
    /**
	 *	フィードを出力する
	 */
    public function feed() {
        $this->syndicationURL = $this->link;
        $this->_setFormat($this->_format);
        $this->_feed->saveFeed($this->_savePath);
    }
	/**
	 *	フォーマットを指定する（元クラスのオーバーライドのためのfunction）
	 *	@param	string	$format
	 */
    public function _setFormat($format) {
        parent::_setFormat($format);
       
        if ($this->_encoding) {
            $this->_feed->encoding = $this->_encoding;
        }
    }
}
