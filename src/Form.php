<?php
/**
 *  P2_Form
 *
 *  ※非推奨 -> 代わりに P3_Form を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Form.php
 *
 *  require
 *      * P2_Data
 *
 *  @version 2.2.2b
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Form {
    const LABEL_ID = 'label-id';

    protected $_D;
    protected $_seq;
    
	/**
	 *	コンストラクタ
	 *	@param	P2_Data	$D	（省略可）省略した場合はルール無しのP2_Dataが使われる
	 */
    public function __construct(P2_Data $D = null) {
        if ($D) {
            $this->_D = $D;
        } else {
            $this->_D = new P2_Data('', '');
            $this->_D->import(array(), '');
        }
        $this->_seq = 0;
    }
    /**
     *	form要素の開始タグを取得する
     *	@param	string	$action
     *	@param	array	$atr	(Optional)
     *	@return	string
     */
    public function start($action, array $atr = array()) {
    	$def = array('action' => $action, 'method' => 'post');
    	return '<form' . $this->_arr2atr('form', $def, $atr) . ">\n";
    }
    /**
	 *	テキスト入力欄のhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function text($name, array $atr = array()) {
        $def = array('type' => 'text', 'name' => $name, 'value' => $this->_getValue($name));
        return $this->_input($def, $atr);
    }
    /**
	 *	パスワード入力欄のhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function password($name, array $atr = array()) {
        return $this->_input(array('type' => 'password', 'name' => $name), $atr);
    }
    /**
	 *	チェックボックスのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	string	$label	表示する文字列
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function checkbox($name, $label, array $atr = array()) {
        $def = array('type' => 'checkbox', 'name' => $name);
        return $this->_checkable($this->_getValue($name, false), $label, $def, $atr);
    }
    /**
	 *	ラジオボタンのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	string	$label	表示する文字列
	 *	@param	string	$value	value属性の値
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function radio($name, $label, $value, array $atr = array()) {
        $def = array('type' => 'radio', 'name' => $name, 'value' => $value);
        return $this->_checkable(($value == $this->_getValue($name, false)), $label, $def, $atr);
    }
    /**
	 *	チェック系要素（エラーでもclass属性にerrorを追加しない）を生成する
	 *	@access	private
	 */
    private function _checkable($checkedFlg, $label, $def, $atr) {
        if ($checkedFlg) {
            $def['checked'] = 'checked';
        }
        if ($atr['id']) {
            $id = $atr['id'];
        } else {
            $id = P2_Form::LABEL_ID . $this->_seq++;
            $def['id'] = $id;
        }
        $err = $this->_D->getErrors($def['name']);
		$input = $this->_input($def, $atr, false);
        return "$err<label for=\"$id\">$input$label</label>\n";
    }
    /**
	 *	隠し項目のhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function hidden($name, array $atr = array()) {
        $def = array('type' => 'hidden', 'name' => $name, 'value' => $this->_getValue($name));
        return $this->_input($def, $atr);
    }
    /**
	 *	submitボタンのhtmlを取得する
	 *	@param	string	$label	ボタン上に表示する文字列
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function submit($label = '送信', array $atr = array()) {
        return $this->_input(array('type' => 'submit', 'value' => $label), $atr);
    }
    /**
	 *	リセットボタンのhtmlを取得する
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function reset(array $atr = array()) {
        return $this->_input(array('type' => 'reset'), $atr);
    }
    /**
	 *	submitボタンのhtmlを取得する
	 *	@param	string	$label	ボタン上に表示する文字列
	 *	@param	string	$onclick	クリック時に実行するJavaScript
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function button($label, $onclick, array $atr = array()) {
        $def = array('type' => 'button', 'value' => $label, 'onclick' => $onclick);
        return $this->_input($def, $atr);
    }
    /**
	 *	アップロード用のファイル選択欄のhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	integer	$maxFileSize	最大ファイルサイズ（単位はMB）
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function file($name, $maxFileSize, array $atr = array()) {
        $h = $this->hidden('MAX_FILE_SIZE', array('value' => $maxFileSize * 1024 * 1024));
        return $h . $this->_input(array('type' => 'file', 'name' => $name), $atr);
    }
    /**
	 *	@access	protected
	 */
    protected function _input(array $def, array $atr, $addErrFlg = true) {
		$atr['class'] .= ' input-' . $def['type'];
		$err = '';
        if ($addErrFlg) {
            $err = $this->_D->getErrors($def['name']);
			if ($err) {
				$atr['class'] .= ' error';
			}
        }
        return "$err<input" . $this->_arr2atr('input', $def, $atr) . " />\n";
    }
    /**
	 *	テキストエリアのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function textarea($name, array $atr = array()) {
        $def = array('name' => $name, 'rows' => 5, 'cols' => 30);
        $err = $this->_D->getErrors($name);
		if ($err) {
			$atr['class'] .= ' error';
		}
        $h = "$err<textarea" . $this->_arr2atr('textarea', $def, $atr) . '>';
        return $h . $this->_getValue($name) . "</textarea>\n";
    }
    /**
	 *	選択リストのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	mixed	$options	選択肢（配列 or 1列のみのデータを持つPDOStatement）
	 *	@param	boolean	$valueFlg	（省略可）option要素にvalue属性を付けるかどうか
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function select($name, $options, $valueFlg = true, array $atr = array()) {
		list($selected, $h, $endTag) = $this->_getSelectBase($name, $atr);
		if (is_object($options)) {
			$options = $options->fetchAll(PDO::FETCH_COLUMN, 0);
		}
        foreach ($options as $value => $label) {
            if ($valueFlg) {
                $v = ' value="' . $this->_h($value) . '"';
            } else {
                $v = '';
                $value = $label;
            }
            $s = $this->getSelected($selected, $value);
            $h .= "<option$v$s>" . $this->_h($label) . "</option>\n";
        }
        return $h . $endTag;
    }
    /**
	 *	@access	protected
	 */
	protected function _getSelectBase($name, array $atr, $firstOption = null) {
        $err = $this->_D->getErrors($name);
		if ($err) {
			$atr['class'] .= ' error';
		}
        $h = "$err<select" . $this->_arr2atr('select', array('name' => $name), $atr) . ">\n";
		if ($firstOption === '') {
			$h .= "<option><!-- empty --></option>\n";
		} else if (!is_null($firstOption)) {
			$h .= '<option>' . $this->_h($firstOption) . "</option>\n";
		}
		return array($this->_getValue($name, false), $h, "</select>\n");
	}
    /**
	 *	選択リストのselected属性を取得する
	 *	@param	mixed	$selected	選択状態にする値
	 *	@param	mixed	$value	値
	 *	@return	string
	 */
	public function getSelected($selected, $value) {
        if ($selected == $value) {
            return ' selected="selected"';
        } else {
			return '';
		}
	}
    /**
	 *	@access	protected
	 */
    protected function _getValue($name, $hEscFlg = true) {
        $value = $this->_D->get($name);
        if ($hEscFlg) {
            $value = $this->_h($value);
        }
        return $value;
    }
    /**
	 *	@access	protected
	 */
    protected function _arr2atr($tag, array $def, array $atr) {
        $rule = $this->_D->getAtr($tag, $def['name']);
		$atr['class'] = trim($atr['class'] . ' '. $rule['class']);
        $arr = array_merge($def, $rule);
        $arr = array_merge($arr, $atr);
        $h = '';
        foreach ($arr as $key => $value) {
            if (!is_null($value) && $value !== '') {
                $h .= " $key=\"$value\"";
            }
        }
        return $h;
    }
    /**
	 *	@access	protected
	 */
    protected function _h($value) {
        return htmlSpecialChars($value, ENT_QUOTES);
    }
	/**
	 *	入力可能な文字列長の説明文を取得する
	 *	@param	string	$name	フィールド名
	 */
    public function getLengthRange($name) {
        global $C;
        $rules = $C->rules[$name];
        $min = $rules['minlength'];
        $max = $rules['maxlength'];
        if ($min) {
            return "{$min}～{$max}字";
        } else {
            return "{$max}字以内";
        }
    }
}
