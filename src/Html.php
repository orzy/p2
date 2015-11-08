<?php
/**
 *  P2_Html
 *
 *  ¦”ñ„§ -> ‘ã‚í‚è‚É P3_Html ‚ğg‚¤
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Html.php
 *
 *  require
 *      * P2_Xml
 *
 *  @version 2.2.0c
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Html {
	private $_prop;
	
	public function __construct(array $prop = array()) {
		$dflt = array('urlRoot' => '');
		$this->_prop = array_merge($dflt, $prop);
	}
	
	public function get($name, $data = null, $child = null) {
		return new P2_Xml($name, $data, $child);
	}
	
	public function a($href, $label, array $attr = array()) {
		$dflt = array('href' => $this->_prop['urlRoot'] . $href);
		return $this->get('a', array_merge($dflt, $attr), $label);
	}

	public function img($src, $alt = '', array $attr = array()) {
		$dflt = array('src' => $src, 'alt' => $alt);
		return $this->get('img', array_merge($dflt, $attr));
	}
	
	public function table(array $attr = array()) {
		return $this->get('table', array_merge(array('summary' => 'table'), $attr));
	}
}
