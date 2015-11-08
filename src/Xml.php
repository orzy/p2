<?php
/**
 *  P2_Xml
 *
 *  require
 *      * P2_PublicVars
 *
 *  @version 2.2.1
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Xml extends P2_PublicVars {
	public $name;
	public $children = array();
	public $attr = array();
	
	/**
	 *	@param	string	$name
	 *	@param	mixed	$data	（省略可）属性の配列または子要素
	 *	@param	mixed	$child	（省略可）子要素
	 */
	public function __construct($name, $data = null, $child = null) {
		$this->name = $name;
		if (is_array($data)) {
			$this->attr = $data;
		} else {
			$child = $data;
		}
		if (!is_null($child)) {
			$this->children[] = $child;
		}
	}
	/**
	 *	子要素を作成して追加する
	 *	@param	string	$name
	 *	@param	mixed	$data	（省略可）子要素の属性の配列または子要素の子要素
	 *	@param	mixed	$child	（省略可）子要素の子要素
	 *	@return	P2_Xml	追加した子要素
	 */
	public function add($name, $data = null, $child = null) {
		$xml = new P2_Xml($name, $data, $child);
		$this->children[] = $xml;
		return $xml;
	}
	/**
	 *	@param	mixed	$children	子要素。引数として一度に複数渡せる
	 */
	public function inject() {
		$args = func_get_args();
		if (is_array($args[0])) {
			$args = $args[0];
		}
		$this->children = array_merge($this->children, $args);
	}
	/**
	 *	要素名で探して最初に見つかった子要素を返す
	 *	@return	P2_Xml	見つからなかった場合はnull
	 */
	public function getElementByName($name) {
		foreach ($this->children as $child) {
			if (is_object($child)) {
				if ($child->name == $name) {
					return $child;
				}
				$result = $child->getElementByName($name);
				if ($result) {
					return $result;
				}
			}
		}
		return null;
	}
	/**
	 *	@return	string	文字列可したXML
	 */
	public function __toString() {
		$s = "\n<" . $this->name;
		foreach ($this->attr as $key => $value) {
			$s .= " $key" . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
		}
		if (count($this->children)) {
			$s .= '>';
			foreach ($this->children as $child) {
				if (is_object($child)) {
					$s .= $child->__toString();
				} else {
					$s .= htmlspecialchars($child, ENT_QUOTES);
				}
			}
			$s .= '</' . $this->name . '>';
		} else {
			$s .= ' />';
		}
		return $s;
	}
	/**
	 *	@return string XML宣言のついたXML文字列
	 */
	public function format($encoding = null, $doc = '') {
		if (!$encoding) {
			$encoding = mb_internal_encoding();
		}
		$s = '<?xml version="1.0" encoding="' . $encoding . '" ?>';
		if ($doc) {
			$s .= "\n" . $doc;
		}
		return $s . $this;
	}
}
