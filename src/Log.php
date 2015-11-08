<?php
/**
 *  P2_Log
 *
 *  ※非推奨 -> 代わりに P3 functionsのdump() を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/functions.php
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.1b
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Log {
    private $_path;
	private $_defaultLogLevel;

	/**
	 *	コンストラクタ
	 *	@param	string	$fileNmae	（省略可）ログファイル名。省略した場合はデフォルトを使う
	 */
    public function __construct($fileName = null) {
        $default = ini_get('error_log');
        if ($fileName) {
            $arr = explode('/', $default);
            $arr[count($arr) - 1] = $fileName;
            $this->_path = implode('/', $arr);
        } else {
            $this->_path = $default;
        }
    }
	/**
	 *	ログ出力
	 *	@param	mixed	$log	ログ内容
	 */
    public function log($log) {
    	if (is_array($log) || is_object($log)) {
    		$str = var_export($log, true);
    	} else {
    		$str = $log;
    	}
        error_log('[' . date('Y/m/d H:i:s') . "] $str\n", 3, $this->_path);
    }
	/**
	 *	strictエラー表示有無の切り替え
	 *	@param	boolean	$flg	strictをエラーとするかどうか
	 */
    public function strict($flg) {
        if ($flg) {	//元に戻す
            $logLevel = $this->defaultLogLevel;
        } else {	//strictエラーを無視する
			$this->defaultLogLevel = error_reporting();
            $logLevel = error_reporting() & ~E_STRICT;
        }
        error_reporting($logLevel);
    }
}
