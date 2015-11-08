<?php
/**
 *  P2_FormExt
 *
 *  require
 *      * P2_Form
 *
 *  @version 2.2.0
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_FormExt extends P2_Form {
	/**
	 *	連番数字の選択リストのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	integer	$start	最初の数値
	 *	@param	integer	$end	最後の数値
	 *	@param	string	$firstOption	（省略可）先頭に入れる選択肢
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function selectNumber($name, $start, $end, $firstOption = null, array $atr = array()) {
		list($selected, $h, $endTag) = $this->_getSelectBase($name, $atr, $firstOption);
		$add = 1;
		if ($start > $end) {
			$add = -1;
		}
        for ($i = $start; $i != $end + $add; $i += $add) {
            $h .= '<option' . $this->getSelected($selected, $i) . ">$i</option>\n";
        }
        return $h . $endTag;
    }
	/**
	 *	日付の選択リストのhtmlを取得する
	 *	@param	string	$prefix
	 *	@param	integer	$firstY	リストの最初の年と今年との差
	 *	@param	integer	$lastY	リストの最後の年と今年との差
	 *	@param	mixed	$sep	（省略可）セパレータ
	 *	@param	string	$firstOption	（省略可）先頭に入れる選択肢
	 *	@return	string	html
	 */
	public function selectDate($prefix, $firstY, $lastY, $sep = '/', $firstOption = null) {
		if (is_array($sep)) {
			$sepArr = $sep;
		} else {
			$sepArr = array($sep, $sep, '');
		}
		$thisY = date('Y');
		$first = $thisY + $firstY;
		$last = $thisY + $lastY;
		$name = $prefix . '_y';
		$h = $this->selectNumber($name, $first, $last, $firstOption, array('id' => $name));
		$h .= $sepArr[0];
		$name = $prefix . '_m';
		$h .= $this->selectNumber($name, 1, 12, $firstOption, array('id' => $name));
		$h .= $sepArr[1];
		$name = $prefix . '_d';
		$h .= $this->selectNumber($name, 1, 31, $firstOption, array('id' => $name));
		$h .= $sepArr[2];
		return $h;
	}
	/**
	 *	時刻の選択リストのhtmlを取得する
	 *	@param	string	$prefix
	 *	@param	mixed	$sep	（省略可）セパレータ
	 *	@return	string	html
	 */
	public function selectTime($prefix, $sep = '：') {
		if (is_array($sep)) {
			$sepArr = $sep;
		} else {
			$sepArr = array($sep, '');
		}
		$minutes = array();
		$nums = range(0, 59);
		foreach ($nums as $num) {
			$minutes[] = sprintf('%02d', $num);
		}
		
		$h = $this->selectNumber($prefix . '_h', 0, 23);
		$h .= $sepArr[0];
		$h .= $this->select($prefix . '_m', $minutes, false);
		$h .= $sepArr[1];
		return $h;
	}
    /**
	 *	都道府県の選択リストのhtmlを取得する
	 *	@param	string	$name	name属性の値
	 *	@param	string	$firstOption	（省略可）先頭に入れる選択肢
	 *	@param	array	$atr	（省略可）その他の属性の指定
	 *	@return	string	html
	 */
    public function selectTodofuken($name, $firstOption = null, array $atr = array()) {
		list($selected, $h, $endTag) = $this->_getSelectBase($name, $atr, $firstOption);
        $list = P2_FormExt::_getAllTodofuken();
        foreach ($list as $block => $kens) {
            $h .= "<optgroup label=\"$block\">\n";
            foreach ($kens as $ken) {
	            $h .= '<option' . $this->getSelected($selected, $ken) . ">$ken</option>\n";
            }
            $h .= "</optgroup>\n";
        }
        return $h . $endTag;
    }
	/**
	 *	都道府県かどうかチェックする
	 *	@param	string	$ken
	 *	@return	boolean
	 */
    public static function isTodofuken($ken) {
        $list = P2_FormExt::_getAllTodofuken();
        foreach ($list as $block => $kens) {
            if (array_search($ken, $kens) !== false) {
                return true;
            }
        }
        return false;
    }
	/**
	 *	@access	private
	 */
    private static function _getAllTodofuken() {
        return array(
            '北海道・東北' => array(
                  '北海道'
                , '青森県'
                , '岩手県'
                , '宮城県'
                , '秋田県'
                , '山形県'
                , '福島県'
            ), '関東' => array(
                  '茨城県'
                , '栃木県'
                , '群馬県'
                , '埼玉県'
                , '千葉県'
                , '東京都'
                , '神奈川県'
            ), '北陸・甲信越' => array(
                  '新潟県'
                , '富山県'
                , '石川県'
                , '福井県'
                , '山梨県'
                , '長野県'
            ), '東海' => array(
                  '岐阜県'
                , '静岡県'
                , '愛知県'
                , '三重県'
            ), '関西' => array(
                  '滋賀県'
                , '京都府'
                , '大阪府'
                , '兵庫県'
                , '奈良県'
                , '和歌山県'
            ), '中国' => array(
                  '鳥取県'
                , '島根県'
                , '岡山県'
                , '広島県'
                , '山口県'
            ), '四国' => array(
                  '徳島県'
                , '香川県'
                , '愛媛県'
                , '高知県'
            ), '九州' => array(
                  '福岡県'
                , '佐賀県'
                , '長崎県'
                , '熊本県'
                , '大分県'
                , '宮崎県'
                , '鹿児島県'
                , '沖縄県'
            )
        );
    }
}
