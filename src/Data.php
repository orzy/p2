<?php
/**
 *  P2_Data
 *
 *  ※非推奨 -> 代わりに P3_Controller を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Controller.php
 *
 *  require
 *      * P2_Filter
 *
 *  @version 2.2.2b
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Data {
	private $_status;
	private $_errorStatus;
	private $_rules;
	private $_errors;

	/**
	 *	コンストラクタ
	 *	@param	string	$initialStatus
	 *	@param	string	$errorStatus
	 */
	public function __construct($initialStatus = 'success', $errorStatus = 'error') {
		$this->_status = $initialStatus;
		$this->_errorStatus = $errorStatus;
		$this->_errors = array();
	}
	/**
	 *	データを取得する
	 *	@param	string	$key
	 *	@return	mixed
	 */
	public function get($key) {
		$this->_validateKey($key);
		return eval('return $this->' . $key . ';');
	}
	/**
	 *	データをセットする
	 *	@param	string	$key
	 *	@param	mixed	$value
	 */
	public function set($key, $value) {
		$this->_validateKey($key);
		@eval('$this->' . $key . ' = $value;');
	}
	/**
	 *	@access	private
	 */
	private function _validateKey($key) {
		if (!preg_match('/^[_a-z][_a-z0-9]*$/i', $key)) {
			throw new Exception($key . 'は変数名として正しくありません');
		}
	}
	/**
	 *	POSTまたはGETで送られてきたデータを加工・エラー判定した上で取り込む
	 *	@param	array	$rules
	 *	@param	mixed	$required
	 */
	public function import(array $rules = array(), $required = null) {
		$this->_rules = $rules;
		foreach (array_merge($_GET, $_POST) as $key => $value) {
			if ($value == '') {
				continue;
			}
			$eachRules = $rules[$key];
			if ($eachRules) {
				foreach ($eachRules as $type => $rule) {
					if (substr($type, -3) != 'Msg') {
						$value = P2_Filter::convert($type, $rule, $value);
					}
				}
				foreach ($eachRules as $type => $rule) {
					if (substr($type, -3) != 'Msg') {
						$this->_addError(
							$key, $type, P2_Filter::validate($type, $rule, $value)
						);
					}
				}
			}
			$this->set($key, $value);
		}
		
		if (!$required) {
			return;
		}
		$required = (array)$required;
		$type = 'required';
		foreach ($required as $key) {
			$this->_addError(
				$key, $type, P2_Filter::validate($type, 1, $this->get($key))
			);
		}
	}
	/**
	 *	@access	private
	 */
	private function _addError($key, $type, $err) {
		if (!$err) {
			return;
		}
		$msg = $this->_rules[$key][$type . 'Msg'];
		if ($msg) {
			$err = $msg;
		}
		$this->addError($key, $err);
	}
	/**
	 *	エラーメッセージを追加する
	 *	@param	string	$key
	 *	@param	string	$errMsg
	 */
    public function addError($key, $errMsg) {
        $this->_errors[$key][] = $errMsg;
		$this->setStatus($this->_errorStatus);
    }
	/**
	 *	エラーメッセージを取得する
	 *	@param	string	$key
	 *	@return	string
	 */
    public function getErrors($key) {
		$e = '';
		$errors = $this->_errors[$key];
		if ($errors) {
			$e = '<span class="error">' . implode("<br />\n", $errors) . "</span><br />\n";
			unset($this->_errors[$key]);
		}
        return $e;
    }
	/**
	 *	ステータスを取得する
	 *	@return	string
	 */
	public function getStatus() {
		return $this->_status;
	}
	/**
	 *	ステータスをセットする
	 *	@param	string	$status
	 */
	public function setStatus($status) {
		$this->_status = $status;
	}
	/**
	 *	form入力項目の属性を取得する
	 *	@param	string	$tag
	 *	@param	string	$key
	 *	@return	array
	 */
	public function getAtr($tag, $key) {
		$atr = array();
		$rules = $this->_rules[$key];
		if ($rules) {
			foreach ($rules as $type => $rule) {
				$atr = P2_Filter::getAtr($tag, $type, $rule, $atr);
			}
		}
		return $atr;
	}
	/**
	 *	データを配列にする
	 *	@param	array	$keys
	 *	@return	array
	 */
	public function toArray(array $keys) {
		$arr = array();
		foreach ($keys as $key) {
			$arr[$key] = $this->get($key);
		}
		return $arr;
	}
	/**
	 *	配列のデータを取り込む
	 *	@param	array	$arr
	 */
	public function fromArray(array $arr) {
		foreach ($arr as $key => $value) {
			$this->set($key, $value);
		}
	}
}
