<?php
/**
 *  P2_Auth
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.1
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Auth {
	const ONE_TIME_TOKEN_KEY = 'P2_Auth 1 time token';

	/**
	 *	ログイン
	 */
	public static function login() {
		session_regenerate_id(true);	//セッションIDを新規発行
	}
	/**
	 *	ログアウト（セッションは全て破棄）
	 */
	public static function logout() {
		$_SESSION = array();
		return session_destroy();
	}
	/**
	 *	CSRF対策の1タイムトークンを取得する
	 *	@return	string
	 */
	public static function get1timeToken() {
		$token = md5($_SERVER['PATH'] . time());
		$_SESSION[P2_Auth::ONE_TIME_TOKEN_KEY] = $token;
		return $token;
	}
	/**
	 *	CSRF対策の1タイムトークンをチェックする
	 *	@param	string	$postKey	トークンのフィールド名
	 *	@param	boolean
	 */
	public static function validate1timeToken($postKey) {
		$token = $_SESSION[P2_Auth::ONE_TIME_TOKEN_KEY];
		unset($_SESSION[P2_Auth::ONE_TIME_TOKEN_KEY]);
		return ($_POST[$postKey] == $token);
	}
	/**
	 *	ランダムな英数字の文字列を生成する
	 *	@param	integer	$len	文字列の長さ
	 *	@return	string
	 */
	public static function getRandString($len) {
		$arr = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
		$last = count($arr) - 1;
		$str = '';
		for ($i = 0; $i < $len; $i++) {
			$str .= $arr[rand(0, $last)];
		}
		return $str;
	}
}
