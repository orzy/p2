<?php
/**
 *  P2_Service_Base
 *
 *  require
 *      * libxml 2.6.21+ (PHP 5.1.2+)
 *      * P2_PublicVars
 *
 *  @version 2.2.7a
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Base extends P2_PublicVars {
	const SATUS_INIT = 0;
	const SATUS_IDVALID_XML = 1000;	//XML不整合
	const SATUS_LOCK_ERROR = 1100;	//ロック取得失敗
	const SATUS_SERVICE_ERROR = 2000;	//サービス固有のエラー
	
	public $status = self::SATUS_INIT;
	public $responseCode;	//エラー解析用
	public $response;	//エラー解析用
	public $url;	//エラー解析用
	
	protected $_params = array();
	
	private $_workDir;
	private $_interval;
	
	/**
	 *	コンストラクタ
	 */
	public function __construct() {
	}
	
	/**
	 *	@param	string	$workDir	/path/to/workdir
	 *	@param	integer	$intervalSecond	(optional)
	 */
	public function setRequestInterval($workDir, $intervalSecond = 1) {
		$this->_workDir = $workDir;
		$this->_interval = $intervalSecond;
	}
	
	public function addParam($key, $value) {
		$this->_params[$key] = $value;
	}
	
	public function clearParams() {
		$this->_params = array();
	}
	
	public function request($url, $xmlFlg = true, $method = 'get', $toEncoding = 'UTF-8') {
		$this->url = '';
		$method = strToLower($method);
		
		//パラメータをQueryStringに変換
		$qs = $this->_arr2qs($toEncoding);
		
		//ロック取得
		$fp = $this->_getLockFp();
		if (!$fp) {
			$this->status = self::SATUS_LOCK_ERROR;
			throw new Exception('ロック取得失敗');
		}
		
		//ファイル取得
		$resp = $this->_getFile($method, $url, $qs);
		
		//最終更新時刻更新してロックを開放
		if ($this->_workDir) {
			touch($this->_workDir . '/timestamp');
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		
		//ファイル取得失敗の場合
		if ($resp === false || $this->status >= 400) {
			throw new Exception('リクエスト失敗 (' . $this->responseCode . ')');
		}
		
		$this->response = $resp;
		
		if ($xmlFlg) {
			//XMLをパース
			//@see http://php.net/manual/ja/libxml.constants.php
			$xml = simplexml_load_string(
				$resp,
				'SimpleXMLElement',
				LIBXML_COMPACT + LIBXML_NOERROR
			);
			
			if ($xml === false) {
				$this->status = self::SATUS_IDVALID_XML;
				throw new Exception('XML取得失敗');
			}
			return $xml;
		} else {
			return $resp;
		}
	}
	
	protected function _arr2qs($to) {
		$from = mb_internal_encoding();
		$arr = $this->_params;
		if ($from !== $to) {
			mb_convert_variables($to, $from, $arr);
		}
		return http_build_query($arr);
	}
	
	private function _getLockFp() {
		if (!$this->_workDir) {	//インターバル指定無し
			return true;
		}
		
		$fp = fopen($this->_workDir . '/lock', 'w');
		if (!$fp || !flock($fp, LOCK_EX)) {
			return false;
		}
		
		//アクセス間隔を遵守
		$path = $this->_workDir . '/timestamp';
		if (file_exists($path)) {
			$sleep = filemtime($path) + $this->_interval - time();
			if ($sleep > 0) {
				sleep($sleep);
			}
		}
		
		return $fp;
	}
	
	private function _getFile($method, $url, $qs) {
		//PHP 5.2.10+で有効。それ以前はHTTPエラー時にはPHPのWarningが発生
		$http = array('ignore_errors' => true);
		
		switch ($method) {
			case 'get':
				if ($qs) {
					$url .= '?' . $qs;
				}
				break;
			case 'post':
				$http['method'] = 'POST';
				$http['header'] = 'Content-type: application/x-www-form-urlencoded';
				$http['content'] = $qs;
				break;
			default:
				throw new Exception($method . 'には未対応です');
		}
		
		$this->url = $url;
		
		$resp = file_get_contents(
			$url,
			false,
			stream_context_create(array('http' => $http))
		);
		
		$this->_setResponseCode($http_response_header);
		
		return $resp;
	}
	
	private function _setResponseCode($responseHeader) {
		if (!$responseHeader) {
			return;
		}
		
		preg_match('@^HTTP/1\\.. ([0-9]{3}) @i', $responseHeader[0], $matches);
		$this->status = $matches[1];
		$this->responseCode = $responseHeader[0];
	}
	
	public function getDetail($paramsFlg = false) {
		$msg = '[status] ' . $this->status . "\n";
		$msg .= '[url] ' . $this->url . "\n";
		if ($paramsFlg) {
			$msg .= '[params] ' . http_build_query($this->_params) . "\n";
			$msg .= '[params (decoded)] ' . urldecode(http_build_query($this->_params)) . "\n";
		}
		if ($this->responseCode) {
			$msg .= '[response code] ' . $this->responseCode . "\n";
		}
		$msg .= '[response body] ' . $this->response;
		return $msg;
	}
}
