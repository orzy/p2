<?php
/**
 *  P2 Global Functions
 *
 *  ※非推奨 -> 代わりに P3 functions を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/functions.php
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.5c
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 *	PEARのclass命名規則に則ったautoload
 *	@param	string	$className
 *	@see	http://php.net/autoload
 */
if (!function_exists('__autoload')) {
	function __autoload($className) {
		if (strpos($className, '.') !== false) {
			throw new Exception('不正なclass名をautoloadしようとしました');
		}
		require(strtr($className, '_', '/') . '.php');
	}
}
/**
 *	htmlエスケープ
 *	@param	string	$value
 *	@return	string
 */
if (!function_exists('h')) {
	function h($value) {
		return htmlSpecialChars($value, ENT_QUOTES, 'UTF-8');
	}
}
/**
 *	htmlエスケープをして、改行箇所にはbr要素を入れる
 *	@param	string	$value
 *	@return	string
 */
if (!function_exists('hbr')) {
	function hbr($value) {
		return nl2br(htmlSpecialChars($value, ENT_QUOTES, 'UTF-8'));
	}
}
/**
 *	RFC 3986形式でURLに付けるパラメータを作成する
 *	@param	mixed	$data	文字列 or 配列
 *	@param	string	$url	(Optional)パラメータの前に付けるURL
 *	@return	string
 */
if (!function_exists('ue')) {
	function ue($data, $url = '') {
		if (is_array($data)) {
			$s = $url;
			if ($url) {
				if (strpos($url, '?') === false) {
					$s .= '?';
				} else {
					$s .= '&';
				}
			}
			$s .= strtr(http_build_query($data), array('%7E' => '~', '+' => '%20'));
		} else {
			$s = strtr(rawurlencode($data), array('%7E' => '~'));
		}
		return $s;
	}
}
/**
 *	デバグ用のhtml出力
 *	@param	mixed	$value
 *	@param	mixed	$log	true or false or ログファイルのpath
 */
if (!function_exists('dump')) {
	function dump($value, $log = false) {
		if (is_array($value) || is_object($value)) {
			$value = var_export($value, true);
		}
		
		if ($log) {
			if (is_bool($log)) {
				error_log($value);
			} else {
				error_log('[' . date('Y-m-d H:i:s') . "] $value\n", 3, $log);
			}
		} else {
			echo '<pre>' . h($value) . "</pre>\n";
		}
	}
}
/**
 *	デフォルト値のセット
 *	※非推奨
 *	@param	mixed	$value
 *	@param	mixed	$default
 *	@param	mixed	$chante	(省略可)
 *	@param	mixed
 */
if (!function_exists('dflt')) {
	function dflt($value, $default, $change = null) {
		if ($value) {
			if ($change) {
				return $change;
			} else {
				return $value;
			}
		} else {
			return $default;
		}
	}
}
/**
 *	htmlのa要素（リンク）の生成
 *	※非推奨
 *	@param	string	$href
 *	@param	string	$text
 *	@param	string	$atr	（省略可）
 *	@return	string
 */
if (!function_exists('a')) {
	function a($href, $text, $atr = '') {
		return "<a href=\"$href\"$atr>$text</a>\n";
	}
}
