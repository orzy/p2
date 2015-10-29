<?php
/**
 *  P2_Db
 *
 *  ※非推奨 -> 代わりに P3_Db を使う
 *  @see http://code.google.com/p/p3-framework/source/browse/trunk/Db.php
 *
 *  require
 *      * MySQL
 *      * PDO (PHP 5.1+)
 *
 *  @version 2.2.3a
 *  @see     http://code.google.com/p/p2-php-framework/
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Db {
	private static $_dsn;
	private static $_pdo;
	private static $_insertTimestampName;
	//private static $_updateTimestampName;
	
	private $_table;
	
	/**
	 *	DSNをセットする
	 *	@param	mixed	$dsn	DSN、ユーザー、パスワードの配列 or DSN
	 *	@param	string	$user	(Optional)
	 *	@param	string	$password	(Optional)
	 *	@see http://php.net/manual/ja/ref.pdo-mysql.connection.php
	 */
	public static function setDsn($dsn, $user = null, $password = null) {
		if (!is_array($dsn)) {
			$dsn = array($dsn, $user, $password);
		}
		P2_Db::$_dsn = $dsn;
		P2_Db::$_pdo = null;
	}
	/**
	 *	INSERT日時を保存する列名の指定（指定が無ければ保存しない）
	 *	@param	string	$name	(Optional)
	 */
	public static function setInsertTimestampName($name = 'created_at') {
		P2_Db::$_insertTimestampName = $name;
	}
	/**
	 *	@access	private
	 */
	private static function _getPdo() {
		$pdo = new PDO(
			P2_Db::$_dsn[0],
			P2_Db::$_dsn[1],
			P2_Db::$_dsn[2],
			array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,	//例外を投げる
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,	//MySQLのみ
				PDO::ATTR_EMULATE_PREPARES => false,	//サーバサイドのを使う
			)
		);
		
		return $pdo;
	}
	/**
	 *	SQLを実行する
	 *	@param	string	$sql
	 *	@param	array	$params
	 *	@return	PDOStatment
	 */
	public static function query($sql, array $params) {
		$pdo = P2_Db::$_pdo;
		if (!$pdo) {
			$pdo = P2_Db::_getPdo();
			P2_Db::$_pdo = $pdo;
		}
		
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
		} catch (PDOException $e) {
			//エラーログにSQLとパラメータを出力
			$msg = "\n[SQL]\n$sql\n[PARAMS]\n" . var_export($params, true);
			throw new Exception($e->getMessage() . $msg);
		}
		$stmt->setFetchMode(PDO::FETCH_ASSOC);	//添字を列名のみにする
		return $stmt;
	}
	/**
	 *	データを1行SELECTする
	 *	@param	string	$sql
	 *	@param	array	$params
	 *	@return	array
	 */
	public static function queryRow($sql, array $params) {
		$stmt = P2_Db::query($sql, $params);
		return $stmt->fetch();
	}
	/**
	 *	データを1つSELECTする
	 *	@param	string	$sql
	 *	@param	array	$params
	 *	@return	string
	 */
	public static function queryColumn($sql, array $params) {
		$stmt = P2_Db::query($sql, $params);
		return $stmt->fetchColumn();
	}
	/**
	 *	コンストラクタ
	 *	@param	string	$table	テーブル名（またはFROM句で指定するもの）
	 */
	public function __construct($table) {
		$this->_table = $table;
	}
	/**
	 *	SELECTする
	 *	@param	string	$select
	 *	@param	array	$where	（省略可）
	 *	@param	string	$others	（省略可）WHERE句の後に付けるSQL
	 *	@return	mixed
	 */
	public function select($select, array $where = array(), $others = null) {
		$sql = "SELECT $select \n";
		$sql .= " FROM {$this->_table}\n";
		if (count($where)) {
			list($sqlWhere, $params) = $this->_where($where);
			$sql .= $sqlWhere;
		} else {
			$params = array();
		}
		$sql .= $others;
		
		if (stripos($others, 'ORDER BY') !== false) {	//複数行の場合
			return P2_Db::query($sql, $params);
		} else if (strpos($select, ',') !== false || $select == '*') {	//複数列の場合
			return P2_Db::queryRow($sql, $params);
		} else {
			return P2_Db::queryColumn($sql, $params);
		}
	}
	/**
	 *	件数を取得する
	 *	@param	string	$select
	 *	@param	array	$where	（省略可）
	 *	@return	integer
	 */
	public function getCount(array $where = array()) {
		return $this->select('COUNT(*)', $where);
	}
	/**
	 *	INSERTする
	 *	@param	array	$insert
	 *	@param	mixed	$option		(Optional) 'IGNORE' or 'REPLACE' or 配列（更新の場合）
	 *	@param	boolean	$idReturnFlg	(Optional) 自動採番したIDを返すかどうか
	 *	@return	integer	($idReturnFlgがtrueの場合のみ)自動採番したID
	 */
	public function insert(array $insert, $option = null, $idReturnFlg = false) {
		if (P2_Db::$_insertTimestampName) {
			$insert[P2_Db::$_insertTimestampName] = date('Y-m-d H:i:s');
		}
		
		$command = 'INSERT';
		if ($option && !is_array($option)) {
			switch (strtoupper($option)) {
				case 'IGNORE':	//PK重複データが既にあれば何もしない
					$command .= ' IGNORE';
					break;
				case 'REPLACE':	//PK重複データが既にあれば削除してから登録
					$command = 'REPLACE';
					break;
			}
		}
		$params = array_values($insert);
		
		$sql = "$command INTO {$this->_table}(";
		$sql .= implode(', ', array_keys($insert)) . ")\n";
		$sql .= 'values(' . str_repeat('?, ', count($insert) - 1) . '?)';
		
		if (is_array($option)) {	//PK重複データが既にあれば更新
			list($sqlSet, $updateParams) = $this->_set($option);
			$sql .= "\n ON DUPLICATE KEY UPDATE $sqlSet";
			$params = array_merge($params, $updateParams);
		}
		
		P2_Db::query($sql, $params);
		
		if ($idReturnFlg) {
			return P2_Db::$_pdo->lastInsertId();
		}
	}
	/**
	 *	UPDATEする
	 *	@param	array	$set
	 *	@param	array	$where
	 */
	public function update(array $set, array $where) {
		list($sqlSet, $params) = $this->_set($set);
		list($sqlWhere, $params) = $this->_where($where, $params);
		$sql = "UPDATE {$this->_table}\n SET $sqlSet \n $sqlWhere";
		P2_Db::query($sql, $params);
	}
	/**
	 *	DELETEする
	 *	@param	array	$where
	 */
	public function delete(array $where) {
		$sql = "DELETE FROM {$this->_table}\n";
		list($sqlWhere, $params) = $this->_where($where);
		P2_Db::query($sql . $sqlWhere, $params);
	}
	/**
	 *	@access	private
	 */
	private function _where(array $where, array $params = array()) {
		foreach ($where as $key => $value) {
			if (is_numeric($key)) {
				$arr[] = $value;
			} else if (preg_match('/ ((<|>|!)=?|LIKE)$/i', $key)) {
				$arr[] = "$key ?";
				$params[] = $value;
			} else {
				$arr[] = "$key = ?";
				$params[] = $value;
			}
		}
		return array('WHERE ' . implode("\n AND ", $arr) . "\n", $params);
	}
	/**
	 *	@access	private
	 */
	private function _set(array $set) {
		$arr = array();
		$params = array();
		foreach ($set as $key => $value) {
			if (is_numeric($key)) {
				$arr[] = $value;
			} else {
				$arr[] = "$key = ?";
				$params[] = $value;
			}
		}
		return array(implode("\n ,", $arr), $params);
	}
	/**
	 *	該当データがあればUPDATEし、無ければINSERTする
	 *	@param	array	$where
	 *	@param	array	$set
	 *	@param	array	$insert	（省略可）$setと同じなら省略可
	 */
	public function insertOrUpdate(array $where, array $set, $insert = null) {
		if ($this->getCount($where)) {
			$this->update($set, $where);	//あれば更新
		} else {
			if (!$insert) {
				$insert = $set;
			}
			$this->insert($insert);	//無ければ登録
		}
	}
	/**
	 *	該当データが無い場合のみINSERTする
	 *	@param	array	$where
	 *	@param	array	$insert
	 */
	public function insertOnce(array $where, array $insert) {
		if (!$this->getCount($where)) {
			$this->insert($insert);
		}
	}
}
