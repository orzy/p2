<?php
/**
 *  P2_Feeder2
 *
 *  ※非推奨 -> 代わりに P3_Feed を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Feed.php
 *
 *  require
 *      * P2_Client
 *      * P2_Xml
 *
 *  @version 2.2.1b
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Feeder2 {
    private $_path;
    private $_expTime;
    private $_cacheFlg;
    private $_from;
    private $_to;
    private $_items;
    
    /**
     *  コンストラクタ
     *  @param  string  $path       キャッシュファイルの保存パス
     *  @param  integer $timeout    （省略可）キャッシュ期間（秒）。デフォルトは1日
     */
    public function __construct($path, $timeout = 86400) {
        $this->_path = $path;
        $this->_timeout = $timeout;
        
        //キャッシュと有効期限
        $this->_cacheFlg = false;
        if (file_exists($path)) {
            $this->_expTime = filemtime($path) + $timeout - time();
            $this->_cacheFlg = ($this->_expTime > 0);
        }
        if (!$this->_cacheFlg) {
            $this->_expTime = $timeout;
        }
        
        $this->_from = mb_internal_encoding();
        $this->_to = 'UTF-8';
        
        $this->_items = array();
    }
    /**
     *  有効期限内のキャッシュがあるか
     *  @return bool
     */
    public function hasCache() {
        return $this->_cacheFlg;
    }
    /**
     *  アイテムを追加する
     *  @param  string  $url
     *  @param  string  $title  （省略可）RSS/Atomフィードでは必須
     *  @param  string  $desc   （省略可）本文またはSummary（HTML可）
     *  @param  string  $date   （省略可）PHPで扱える日付形式の文字列
     *  @param  string  $author （省略可）RSS2ではメールアドレス
     */
    public function addItem($url, $title = '', $desc = '', $date = '', $author = '') {
        $this->_items[] = compact('url', 'title', 'desc', 'date', 'author');
    }
    /**
     *  RSSフィードを出力する
     *  @param  string  $url        WebサイトのURL（トップページなど）
     *  @param  string  $title
     *  @param  string  $desc       サイトの説明
     *  @param  integer $version    （省略可）1 or 2
     */
    public function feedRss($url, $title, $desc, $version = 1) {
        if ($version != 2) {
            $version = 1;
        }
        $this->_feed('rss', $version, $title, $url, $desc, '');
    }
    /**
     *  Atomフィード(1.0)を出力する
     *  @param  string  $title
     *  @param  string  $subtitle   （省略可）
     *  @param  string  $url        （省略可）WebサイトのURL（トップページなど）
     *  @param  string  $author     （省略可）全アイテムにauthorがあれば省略してもよい
     */
    public function feedAtom($title, $subtitle = '', $url = '', $author = '') {
        $this->_feed('atom', 1, $title, $url, $subtitle, $author);
    }
    /**
     *  Sitemapを出力する
     *  @param  bool    $mobileFlg  （省略可）
     */
    public function feedSitemap($mobileFlg = false) {
        if ($mobileFlg) {
            $version = 'mobile';
        } else {
            $version = 'pc';
        }
        $this->_feed('sitemap', $version, '', '', '', '');
    }
    
    private function _feed($format, $version, $title, $url, $desc, $author) {
        header('Content-Type: application/xml; charset=' . $this->_to);
        header('Cache-Control: max-age=' . date('U', $this->_expTime));
        header('Expires: ' . gmdate('D, d M Y H:i:s T', ($this->_expTime + time())));

        if (!$this->_cacheFlg) {
            $this->_create($format, $version, $url, $title, $desc, $author);
        }
        
        if (P2_Client::setLastModified($this->_path)) {
        	readfile($this->_path);
        }
    }
    
    private function _create($format, $version, $url, $title, $desc, $author) {
        list($xml, $list) =
        	$this->_getBase($format, $version, $url, $title, $desc, $author);
        $seq = $list->getElementByName('rdf:Seq');	//RSS1のみで使う
        
        foreach ($this->_items as $item) {
            $list->inject($this->_getEntry($format, $version, $item));
            if ($format == 'rss' && $version == 1) {
                //RSS1はSeqにもitemリストがある
                $seq->add('rdf:li', array('rdf:resource' => $item['url']));
            }
        }
        
        $decl = '<?xml version="1.0" encoding="' . $this->_to . '" ?>';
        file_put_contents($this->_path, $decl. $xml, LOCK_EX);
    }

    private function _getBase($format, $version, $url, $title, $desc, $author) {
        $feedUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $title = $this->_e($title);
        $desc = $this->_e($desc);
        
        switch ($format) {
            case 'rss':
                $channel = new P2_Xml('channel');
                $channel->inject($this->_getRssGeneral($url, $title, $desc));
                if ($version == 1) {
                	$xml = new P2_Xml('rdf:RDF', array(
                		'xmlns' => 'http://purl.org/rss/1.0/',
                    	'xmlns:rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                    	'xmlns:dc' => 'http://purl.org/dc/elements/1.1/',
                    	'xml:lang' => 'ja',
                    ), $channel);
                    $channel->attr['rdf:about'] = $feedUrl;
                    $channel->add('items', new P2_Xml('rdf:Seq'));
                } else {
                	$xml = new P2_Xml('rss', array('version' => '2.0'), $channel);
                	$channel->add('language', 'ja');
                	return array($xml, $channel);	//RSS2はchannelの中にitemを入れる
                }
                break;
            case 'atom':
                $author = $this->_e($author);
                $xml = new P2_Xml('feed', array(
                	'xmlns' => 'http://www.w3.org/2005/Atom',
                	'xml:lang' => 'ja',
                ));
                $xml->inject(
                	$this->_getAtomGeneral($feedUrl, $url, $title, $author, time())
                );
                $xml->add('link', array('rel' => 'self', 'href' => $feedUrl));
                if ($desc) {
                	$xml->add('subtitle', $desc);
                }
                break;
            case 'sitemap':
            	$xml = new P2_Xml('urlset', array(
            		'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
            	));
                if ($version == 'mobile') {
                    $xml->attr['xmlns:mobile'] =
                    	'http://www.google.com/schemas/sitemap-mobile/1.0';
                }
                break;
        }
    	return array($xml, $xml);
    }
    
    private function _getEntry($format, $version, array $item) {
        $url = $item['url'];
        $title = $this->_e($item['title']);
        $desc = $this->_e($item['desc']);
        $author = $this->_e($item['author']);
        if ($item['date']) {
            $time = strtotime($item['date']);
        }

        switch ($format) {
            case 'rss':
                $entry = new P2_Xml('item');
                $entry->inject($this->_getRssGeneral($url, $title, $desc));
                if ($version == 1) {
                	$entry->attr['rdf:about'] = $url;
                    if ($author) {
                    	$entry->add('dc:creator', $author);
                    }
                    if ($time) {
                    	$entry->add('dc:date', date(DATE_W3C, $time));
                    }
                } else {
                    if ($author) {
                    	$entry->add('author', $author);
                    }
                    if ($time) {
                    	$entry->add('pubDate', date(DATE_RSS, $time));
                    }
                }
                break;
            case 'atom':
            	$entry = new P2_Xml('entry');
            	$entry->inject(
            		$this->_getAtomGeneral($url, $url, $title, $author, $time)
            	);
            	$entry->add('summary', array('type' => 'html'), $desc);
                break;
            case 'sitemap':
            	$entry = new P2_Xml('url');
            	$entry->add('loc', $url);
                if ($version == 'mobile') {
                	$entry->add('mobile:mobile');
                }
                if ($time) {
                	$entry->add('lastmod', date(DATE_W3C, $time));
                }
                break;
        }
        return $entry;
    }
    
    private function _getRssGeneral($url, $title, $desc) {
    	return array(
    		new P2_Xml('title', $title),
    		new P2_Xml('link', $url),
    		new P2_Xml('description', $desc),
    	);
    }
    
    private function _getAtomGeneral($id, $url, $title, $author, $time) {
    	$arr = array(
    		new P2_Xml('id', $id),
    		new P2_Xml('title', $title),
    		new P2_Xml('link', array('href' => $url)),
    		new P2_Xml('updated', date(DATE_W3C, $time)),
    	);
        if ($author) {
	    	$arr[] = new P2_Xml('author', new P2_Xml('name', $author));
        }
        return $arr;
    }
    
    private function _e($str) {
        if ($this->_from != $this->_to) {
            $str = mb_convert_encoding($str, $this->_to, $this->_from);
        }
        return $str;
    }
}
