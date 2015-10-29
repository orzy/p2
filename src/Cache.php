<?php
/**
 *  P2_Cache
 *
 *  ※非推奨 -> 代わりに P3_Cache を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Cache.php
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.1b
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Cache {
	private static $_path;
	private static $_workDir;
	
	/**
	 *	ページ全体をキャッシュする
	 *	@param	string	$dir	(Optional)キャッシュファイルを置くディレクトリのパス
	 *	@param	string	$key	(Optional)キャッシュファイル名
	 *	@param	integer	$duration	(Optional)キャッシュ期間の秒数
	 */
	public static function cache($dir = '.', $key = '', $duration = 86400) {
		$key = $key ? $key : md5($_SERVER['REQUEST_URI']) . '.html';
		$path = "$dir/$key";
		$now = time();
		clearStatCache();
		
		if (is_file($path) && (filemtime($path) + $duration) > $now) {
			//有効なキャッシュあり
			$createdAt = filemtime($path);
			$since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
			
			if ($since) {	//ブラウザにキャッシュあり
				if (!preg_match('/GMT/', $since)) {
					$since .= ' GMT';
				}
				
				if ($createdAt == strToTime($since)) {	//変更されていない場合
					header('HTTP/1.1 304 Not Modified');
					
					if (session_id()) {	//余分なHTTPヘッダーを消す
						header('Expires:');
						header('Cache-Control:');
					}
					
					exit;	//終了
				}
			}
		} else {
			//有効なキャッシュ無し
			$createdAt = $now;
		}
		
		//HTTPヘッダーでクライアントキャッシュを制御
		self::setHttpHeaders($duration, $createdAt);
		
		//sessionを使うと出力されるHTTPヘッダーの設定・上書き
		if (session_id()) {
			header('Pragma:');
		} else {
			session_cache_limiter('public');
			//session.cache_expireの単位は分
			ini_set('session.cache_expire', ceil(($createdAt + $duration - $now) / 60));
		}
		
		if ($createdAt < $now) {
			//有効なキャッシュあり
			readfile($path);
			exit;	//終了
		} else {
			//有効なキャッシュ無し
			self::$_path = $path;
			self::$_workDir = getcwd();
			ob_start(array('P2_Cache', 'callback'));
		}
	}
	/**
	 *	ページをbufferへ出力した後に呼ばれるCallback
	 *	@param	string	$buf	buffer
	 */
	public static function callback($buf) {
		chdir(self::$_workDir);	//セットしないとルート(/)になる
		file_put_contents(self::$_path, $buf, LOCK_EX);
		return $buf;
	}
	/**
	 *	部分キャッシュ開始
	 *	@param	string	$path	キャッシュファイルのパス
	 *	@param	integer	$duration	(Optional)キャッシュ期間の秒数
	 *	@return	bookean	有効なキャッシュの有無
	 */
	public static function start($path, $duration = 86400) {
		clearStatCache();
		
		if (is_file($path) && (filemtime($path) + $duration) > time()) {
			//有効なキャッシュあり
			readfile($path);
			return true;
		} else {
			//有効なキャッシュ無し
			self::$_path = $path;
			ob_start();
			return false;
		}
	}
	/**
	 *	部分キャッシュ終了
	 */
	public static function end() {
		file_put_contents(self::$_path, ob_get_flush(), LOCK_EX);
	}
	/**
	 *	HTTPヘッダーでクライアントキャッシュを制御する
	 *	@param	integer	$duration	(Optional)キャッシュ期間の秒数
	 *	@param	integer	$createdAt	(Optional)コンテンツ生成時刻のUnixタイムスタンプ
	 */
	public static function setHttpHeaders($duration = 86400, $createdAt = 0) {
		$createdAt = $createdAt ? $createdAt : time();
		
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $createdAt));
		header('Expires: ' . gmdate('D, d M Y H:i:s T', $createdAt + $duration));
		header("Cache-Control: max-age=$duration");
	}
	/**
	 *	古いキャッシュを削除する
	 *	@param	string	$pattern	glob()のルールに従った削除対象のパスのパターン
	 *	@param	integer	$duration	(Optional)キャッシュ期間の秒数
	 *	@param	integer	$probability	(Optional)削除が実行される確率（1～100）
	 */
	public static function cleanUp($pattern, $duration = 86400, $probability = 1) {
		if (rand(1, 100) > $probability) {
			return;
		}
		
		$time = time() - $duration;
		clearStatCache();
		
		foreach (glob($pattern) as $path) {
			if (is_file($path) && filemtime($path) < $time) {
				unlink($path);
			}
		}
	}
}
