<?php
/**
 *  P2_Client
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.3
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Client {
	const QUERY_REGEX = '/^(MT|Text|as_q|keywords?|kw|qt|query|search|w(or)?d)$/i';
	
	//キャッシュ用
    private static $_client;
    
	/**
	 *	携帯電話かどうか（主な携帯のみ）
	 *	@return	boolean
	 */
    public static function isMobile() {
    	//最後の2つはGooglebot用
    	$carrier = 'DoCoMo|KDDI|SoftBank|Vodafone|J-PHONE|SAMSUNG|portalmmm';
        return P2_Client::_is('mobile', 'HTTP_USER_AGENT', "/^($carrier)/");
    }
	/**
	 *	LAN内かどうか（クラスA・Bは省略）
	 *	@return	boolean
	 */
    public static function isLocal() {
        return P2_Client::_is('local', 'REMOTE_ADDR', '/^(192\.168|127\.0\.0\.[0-8]$)/');
    }
	/**
	 *	Botかどうか
	 *	@return	boolean
	 */
	public static function isBot() {
        return P2_Client::_is('bot', 'HTTP_USER_AGENT', '/http:/');
	}
    /**
	 *	@access	private
	 */
    private static function _is($type, $key, $regex) {
        if (P2_Client::$_client[$type] == null) {
            if (preg_match($regex, $_SERVER[$key])) {
                P2_Client::$_client[$type] = true;
            } else {
                P2_Client::$_client[$type] = false;
            }
        }
        return P2_Client::$_client[$type];
    }
	/**
	 *	クライアントキャッシュ期限を指定する
	 *	@param	string	$strDate	期限を表す文字列
	 */
    public static function setCacheLimit($strDate) {
        $time = strtotime($strDate);
        header('Cache-Control: max-age=' . ($time - time()));
        if (session_id()) {
            header('Expires: ' . gmdate('D, d M Y H:i:s T', $time));
            header('Pragma: cache');
        }
    }
    /**
     *	@return	boolean	変更されたかどうか
     */
    public static function setLastModified($timeOrPath) {
	    if (is_numeric($timeOrPath)) {	//Unix Timestamp
	    	$modified = $timeOrPath;
	    } else if (file_exists($timeOrPath)) {	//ファイルパス
	        $modified = filemtime($timeOrPath);
	    } else {
	        $modified = strtotime($timeOrPath);	//日付を表す文字列
	    }
	    if (!$modified) {
	        throw new Exception("timestampに変換できません $timeOrPath");
	    }
	    
	    $since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
	    if ($since) {
	        if (!preg_match('/GMT/', $since)) {
	            $since .= ' GMT';
	        }
	        if ($modified == strtotime($since)) {	//変更されていない場合
	            header('HTTP/1.1 304 Not Modified');
	            return false;
	        }
	    }
	    
	    header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $modified));
	    return true;
    }
	/**
	 *	リファラから検索キーワードを取り出す
	 */
	public static function getRefererKeyword($referer = '') {
		if (!$referer) {
			$referer = $_SERVER['HTTP_REFERER'];
		}
		
		$queryString = parse_url($referer, PHP_URL_QUERY);
		if (!$queryString) {
			return '';
		}
		parse_str($queryString, $query);
		
		if (isset($query['q'])) {	//Google等、主な検索エンジンは"q"
			return self::_formatKeyword($query['q']);
		} else if (isset($query['p'])) {	//Yahoo等は"p"
			return self::_formatKeyword($query['p']);
		}
		
		//その他の検索エンジンからのキーワード取り出し
		foreach ($query as $key => $value) {
			if (preg_match(self::QUERY_REGEX, $key)) {
				return self::_formatKeyword($value);
			}
		}

		return '';	//ヒットしなかった場合
	}
    /**
	 *	@access	private
	 */
	private static function _formatKeyword($v) {
		$v = urldecode($v);
		$v = mb_convert_encoding($v, mb_internal_encoding(), 'UTF-8,EUC-JP,SJIS,ASCII');
		$v = mb_convert_kana($v, 'asKV');	//全角・半角の統一
		$v = strtoupper($v);	//英字は大文字に統一
		$v = trim($v);
		return $v;
	}
	/**
	 *
	 */
	public static function redirect($url, $statusCode = 302, $scheme = '') {
		if (!preg_match('@^https?://@', $url)) {
			//スキーム
			if (!$scheme) {
				$scheme = 'http';
				if ($_SERVER['HTTPS']) {
					$scheme .= 's';
				}
			}
			
			//ドメイン
			if ($_SERVER['HTTP_X_FORWARDED_HOST']) {
				$domain = $_SERVER['HTTP_X_FORWARDED_HOST'];	//リバースプロキシの場合
			} else {
				$domain = $_SERVER['HTTP_HOST'];
			}
			
			//パス
			if (substr($url, 0, 1) != '/') {
				$arr = explode('/', $_SERVER['REQUEST_URI']);
				array_pop($arr);
				
				foreach (explode('/', $url) as $dir) {
					if ($dir == '..') {
						array_pop($arr);
					} else if ($dir != '.') {
						$arr[] = $dir;
					}
				}
				
				$url = implode('/', $arr);
			}
			
			$url = "$scheme://$domain{$url}";
		}
		
		header("Location: $url", true, $statusCode);
		exit;
	}
}
