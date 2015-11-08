<?php
/**
 *  P2_Filter
 *
 *  ※非推奨 -> 代わりに P3_Filter を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Filter.php
 *
 *  require
 *      * P2_Client
 *
 *  @version 2.2.2a
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Filter {
	/**
	 *	値を変換する
	 *	@param	string	$type	分類
	 *	@param	string	$rule	ルール
	 *	@param	string	$value	値
	 *	@return	mixed
	 */
    public static function convert($type, $rule, $value) {
        switch ($type) {
            case 'type':
                switch ($rule) {
                    case 'regular':
                        $value = trim(mb_convert_kana($value, 'asKV'));
                        break;
                    case 'alphnum':
                    case 'alphnumsign':
                    case 'email':
                        $value = mb_convert_kana($value, 'a');
                        break;
                    case 'number':
                    case 'plusnumber':
                        $value = mb_convert_kana($value, 'n');
                        if (is_numeric($value)) {
                            $value *= 1;
                        }
                        break;
                    case 'zeropadnumber':
                        $value = mb_convert_kana($value, 'n');
                        break;
                }
                break;
            case 'charheight':
                switch ($rule) {
                    case 'lower':
                        $value = mb_strtolower($value);
                        break;
                    case 'upper':
                        $value = mb_strtoupper($value);
                        break;
                }
                break;
			case 'trim':
				$value = trim($value);
				break;
        }
        return $value;
    }
	/**
	 *	エラーチェックする
	 *	@param	string	$type	分類
	 *	@param	string	$rule	ルール
	 *	@param	mixed	$value	値
	 *	@return	string	エラーメッセージ。エラー無しの場合は空文字列
	 */
    public static function validate($type, $rule, $value) {
        $e = '';
        switch ($type) {
            case 'required':
                if (is_null($value) || trim($value) == '') {
                    $e = '入力してください';
                }
                break;
            case 'type':
                switch ($rule) {
                    case 'alphnum':
						$msg = '英数字で入力してください';
						$e = P2_Filter::_match('/^[a-z0-9]+$/i', $value, $msg);
                        break;
                    case 'number':
                    case 'zeropadnumber':
						$e = P2_Filter::_validateNumber('/^[0-9]+$/', $value);
                        break;
                    case 'plusnumber':
						$e = P2_Filter::_validateNumber('/^[1-9][0-9]*$/', $value);
                        break;
                    case 'email':
						$msg = '正しいメールアドレスを入力してください';
						$regex = '/^[-+.\\w]+@[-a-z0-9]+(\\.[-a-z0-9]+)*\\.[a-z]{2,6}$/i';
						$e = P2_Filter::_match($regex, $value, $msg);
                        break;
                }
                break;
            case 'minlength':
                $len = mb_strlen($value);
                if ($len < $rule) {
                    $e = $rule . "文字以上で入力してください （現在 $len 字）";
                }
                break;
            case 'maxlength':
                $len = mb_strlen($value);
                if ($len > $rule) {
                    $e = $rule . "文字以内で入力してください （現在 $len 字）";
                }
                break;
            case 'regex':
				$e = P2_Filter::_match($rule, $value, '正しい形式で入力してください');
                break;
        }
        return $e;
    }
	/**
	 *	@access	private
	 */
	protected static function _validateNumber($regex, $value) {
        if (is_numeric($value)) {
			$e = P2_Filter::_match($regex, $value, '正しい数字を入力してください');
        } else {
            $e = '数字で入力してください';
        }
		return $e;
	}
	/**
	 *	@access	private
	 */
	protected static function _match($regex, $value, $msg) {
        if (preg_match($regex, $value)) {
			return '';
		}
        return $msg;
	}
	/**
	 *	form入力項目の属性を取得する
	 *	@param	string	$tag
	 *	@param	string	$type
	 *	@param	string	$rule
	 *	@param	array	$atr
	 *	@return	array
	 */
    public static function getAtr($tag, $type, $rule, array $atr) {
        switch ($type) {
            case 'type':
                switch ($rule) {
                    case 'alphnum':
                    case 'alphnumsign':
                    case 'email':
                        $atr['class'] .= ' han';
						if (P2_Client::isMobile()) {
							$atr['istyle'] = 3;
						}
                        break;
                    case 'number':
                    case 'plusnumber':
                    case 'zeropadnumber':
                        $atr['class'] .= ' number';
						if (P2_Client::isMobile()) {
							$atr['istyle'] = 4;
						}
                        break;
                }
                break;
            case 'maxlength':
				if ($tag == 'input') {
					if (P2_Client::isMobile()) {
						$rule *= 2;	//docomoではSJISのバイト数での制限になる
					}
	                $atr['maxlength'] = $rule;
				}
                break;
            case 'class':
                $atr['class'] .= " $rule";
                break;
        }
        return $atr;
    }
}
