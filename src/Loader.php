<?php
/**
 *  P2_Loader
 *
 *  ※非推奨 -> 代わりに autoload を使う
 *
 *  ファイルパスはPEARのファイルパス規則に従っていることが前提。
 *  そうでない場合は、P2_Loader::setClassPath()にて事前にセットする必要がある。
 *
 *  require
 *      * P2 globalFunctions
 *
 *  @version 2.1.5
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
require_once('P2/globalFunctions.php');	//P2内でautoloadを使えるようにするため

class P2_Loader {
	private static $_classPaths = array();
	
	/**
	 *	classごとのPHPファイルのパスをセットする
	 *	@param	string	$className	クラス名
	 *	@param	string	$classPath	パス
	 */
	public static function setClassPath($className, $classPath) {
		P2_Loader::$_classPaths[$className] = $classPath;
	}
	/**
	 *	PHPファイルを読み込む
	 *	@param	string	$className	クラス名
	 */
	public static function load($className) {
		$path = @P2_Loader::$_classPaths[$className];
		if (!$path) {
			$path = strtr($className, '_', '/') . '.php';
		}
		require_once($path);
	}
	/**
	 *	PHPファイルを読み込み、classを生成する
	 *	@param	string	$className	クラス名
	 *	@param	mixed	（classのコンストラクタに与えるパラメータをいくつでも）
	 *	@return	object	生成したクラス
	 */
	public static function create() {
		$args = func_get_args();
		$className = array_shift($args);
		P2_Loader::load($className);
		$param = array();
		for ($i = 0; $i < count($args); $i++) {
			$param[] = '$args[' . $i . ']';
		}
		return eval("return new $className(" . implode(', ', $param) . ');');
	}
}
