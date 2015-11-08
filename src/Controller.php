<?php
/**
 *  P2_Controller
 *
 *  ※非推奨 -> 代わりに P3_Controller を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Controller.php
 *
 *  require
 *      * P2_Data
 *      * P2_PublicVars
 *      * P2 globalFunctions
 *
 *  @version 2.2.6a
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
require_once('P2/globalFunctions.php');

class P2_Controller extends P2_PublicVars {
	const PROC_NONE = 'none';

	const ST_DEFAULT = 'default';
	const ST_SUCCESS = 'success';
	const ST_ERROR = 'error';
	const ST_NOT_FOUND = 404;
	
	public $page;
	public $check = false;
	public $template = '';
	public $templateNoCache = false;
	public $header = '';
	public $footer = '';
	public $rules;
	public $required = null;
	public $charset = '';
	public $mobile = false;
	public $title;		//非推奨 -> 代わりに$Dを使う
	public $uriValue;	//非推奨 -> 代わりに$Dを使う

	private $_proc;
	private $_view;

	/**
	 *	コントローラを起動する
	 *	@param	string	$appPath	アプリケーションのルートディレクトリのパス
	 */
	public static function run($appPath) {
		global $C;
		
		set_exception_handler(array('P2_Controller', 'afterException'));
		set_error_handler(array('P2_Controller', 'beforeWarning'), E_WARNING);
		register_shutdown_function(array('P2_Controller', 'beforeShutdown'));
		
		chdir($appPath);	//このパスを作業ディレクトリにする
		$C = new P2_Controller();
		$C->_start();
	}
	/**
	 *	catchされなかった例外の処理
	 */
	public static function afterException($e){
		header('HTTP/1.1 500 Internal Server Error');
		?>
		<div class="exception">
		<strong>システムエラーが発生しました</strong>
		<?php
		if (ini_get('display_errors')) {
			echo "<pre>$e</pre>";
		}
		echo "</div>\n";
		
		self::_errorLog('Exception', (string)$e);
	}
	/**
	 *	警告が発生した状況をログに記録する
	 */
	public static function beforeWarning($errNo, $msg, $file, $line, $context) {
		if (preg_match('/^include\\(/', $msg)) {	//include()のWarningは無視
			return false;
		}
		
		$e = new Exception();	//スタックトレース取得用
		self::_errorLog('Warning', $e->getTraceAsString());
		return false;	//通常のエラーハンドラーに処理を継続させる
	}
	/**
	 *	エラーが発生したURIをログに記録する
	 */
	public static function beforeShutdown(){
		$error = error_get_last();
		
		if ($error['type'] == E_ERROR) {	//Fatal errorのみ
			if (substr($error['message'], 0, 22) == 'Maximum execution time') {
				if (!headers_sent()) {
					header('HTTP/1.1 503 Service Unavailable');
				}
				echo '<strong>タイムアウトしました</strong>';
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				echo '<strong>システムエラーが発生しました</strong>';
			}
			
			self::_errorLog('Error', $error['message']);
		}
	}
	/**
	 *	@access	private
	 */
	private static function _errorLog($level, $msg) {
		error_log("$level [URI] " . $_SERVER['REQUEST_URI'] . "\n" . $msg);
	}
	/**
	 *	コンストラクタ
	 */
	private function __construct() {
		$this->rules = array();	//デフォルトはルール無し
		$this->_proc = array();	//デフォルトはページごとのproc()を呼ぶ
		$this->_view = array();	//デフォルトはページごとのview()を呼ぶ
	}
	/**
	 *	@access	private
	 */
	private function _start() {
		global $C;
		
		//デフォルトのファイルパスはURL＋".php"
		$this->page = substr($this->getUri(), 1);
		if (substr($this->page, -1) == '/' || !$this->page) {
			$this->page .= 'index';	//スラッシュで終わる場合はindex.phpへ
		}
		
		//データコンテナ生成
		$D = new P2_Data(P2_Controller::ST_SUCCESS, P2_Controller::ST_ERROR);
		
		//デフォルト設定を読み込む
		require('_conf/default.php');
		if (substr($this->page, 0, 1) == '_') {
			throw new Exception('P2のインクルードファイルへのアクセスです');
		}
		
		//ページごとの設定を読み込む
		$pagePath = './' . $this->page . '.php';
		if (@include($pagePath)) {
			$pageDir = substr($pagePath, 0, strrpos($pagePath, '/') + 1);
		} else {	//該当ファイルが無い場合
			$pageDir = '';
			require('404.php');
			$D->setStatus(P2_Controller::ST_NOT_FOUND);
		}
		
		//データの受け取り
		$D->import($this->rules, $this->required);
		if ($D->getStatus() == P2_Controller::ST_SUCCESS && $this->check) {
			check($D);
		}
		
		//ビジネスロジック
		$proc = $this->_getPathByStatus($this->_proc, $D);
		if ($proc == P2_Controller::PROC_NONE) {	//無し
		} else if ($proc) {	//ビジネスロジック用のファイルに書かれている場合
			require($pageDir . $proc);
		} else {	//設定ファイルに書かれている場合
			$status = proc($D);
			if ($status) {
				$D->setStatus($status);
			}
		}
		
		//該当ページが無い場合
		if ($D->getStatus() == P2_Controller::ST_NOT_FOUND) {
			header('HTTP/1.1 404 Not Found');
		}
		
		//出力文字コード設定
		if ($this->charset) {
			P2_Controller::setOutputCharset($this->charset);
		}
		
		//携帯向けページへの変換
		if ($this->mobile) {
			ob_start(array('P2_Controller', 'toHankaku'));
		}
		
		if ($this->template) {
			ob_start();	//コンテンツ部分をバッファにためて後でテンプレートに埋め込む
		}
		
		//ページ表示
		if ($this->header) {
			require("_header/{$this->header}.php");
		}
		$view = $this->_getPathByStatus($this->_view, $D);
		if ($view) {	//ビュー用ファイルに書かれている場合
			require($pageDir . $view);
		} else {	//設定ファイルに書かれている場合
			view($D);
		}
		if ($this->footer) {
			require("_footer/{$this->footer}.php");
		}
		
		if ($this->template) {
			$content = ob_get_clean();
			
			//テンプレート内の'{content}'を実際のコンテンツに置換
			ob_start();
			require("_template/{$this->template}.php");
			$resp = str_replace('{content}', $content, ob_get_clean());
			
			if ($this->templateNoCache) {
				//CSS、JavaScript、画像ファイルのブラウザキャッシュを使わないようにする
				$pattern = '/(<(link|script|img).+(href|src)="[^?]+)"/iU';
				$resp = preg_replace($pattern, '$1?' . time() . '"', $resp);
			}
			
			echo $resp;
		}
	}
	/**
	 *	ビジネスロジックのファイルを指定する
	 *	@param	string	$path	ファイルパス
	 *	@param	string	$when	（省略可）このファイルを呼び出す条件
	 */
	public function proc($path, $when = P2_Controller::ST_DEFAULT) {
		$this->_proc[$when] = $path;
	}
	/**
	 *	ページ表示のファイルを指定する
	 *	@param	string	$path	ファイルパス
	 *	@param	string	$when	（省略可）このファイルを呼び出す条件
	 */
	public function view($path, $when = P2_Controller::ST_DEFAULT) {
		$this->_view[$when] = $path;
	}
	/**
	 *	アクセスしてきたURLのドメインより後ろの部分を取得する
	 *	@return	string
	 */
	public function getUri() {
		return $_SERVER['SCRIPT_URL'];
	}
	/**
	 *	アクセスしてきたURLのドメインより後ろの部分を配列にして取得する
	 *	@return	array
	 */
	public function getUriArr() {
		return explode('/', $this->getUri());
	}
	/**
	 *	@access	private
	 */
	private function _getPathByStatus(array $paths, P2_Data $D) {
		$path = $paths[$D->getStatus()];
		if (!$path) {
			$path = $paths[P2_Controller::ST_DEFAULT];
		}
		return $path;
	}
	/**
	 *	出力文字コード設定（最初の出力の前にセットすること）
	 *	@param	string	$charset
	 */
	public static function setOutputCharset($charset) {
		mb_http_output($charset);
		ob_start('mb_output_handler');
	}
	/**
	 *	出力を半角に変換する
	 *	@param	string	$buf
	 */
	public static function toHankaku($buf) {
		return mb_convert_kana($buf, 'rnsk');
	}
}
