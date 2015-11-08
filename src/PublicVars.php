<?php
/**
 *  P2_PublicVars
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.0
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class P2_PublicVars {
	/**
	 *	存在しない変数へのアクセス
	 *	@param	string	$key
	 */
	public function __get($key) {
		throw new Exception(get_class($this) . 'に無い変数"' . $key  . '"をget');
	}
	/**
	 *	存在しない変数へのアクセス
	 *	@param	string	$key
	 *	@param	mixed	$value
	 */
	public function __set($key, $value) {
		throw new Exception(get_class($this) . 'に無い変数"' . $key  . '"をset');
	}
}
