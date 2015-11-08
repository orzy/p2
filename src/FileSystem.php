<?php
/**
 *  P2_FileSystem
 *
 *  require
 *      * (none)
 *
 *  @version 2.2.2
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_FileSystem {
	/**
	 *	mkdirの強化版
	 *	@param	string	$dir	作成するディレクトリのパス
	 *	@param	boolean	$renewFlg	既存ディレクトリがある場合に削除するかどうか
	 */
	public static function mkdir($dir, $renewFlg = false) {
		if (is_dir($dir)) {
			if ($renewFlg) {
				P2_FileSystem::rmdir($dir);
			} else {
				return;
			}
		}
		if (!mkdir($dir)) {
			throw new Exception("ディレクトリ作成失敗 $dir");
		}
	}
	/**
	 *	rmdirの強化版
	 *	@param	string	$dir	削除するディレクトリのパス
	 *	@return	boolean	該当のディレクトリがあったかどうか
	 */
	public static function rmdir($dir) {
		if (substr($dir, -1) == '/') {
			$path = substr($dir, 0, strlen($dir) - 1);
		}
		if (!$h = @opendir($dir)) {
			return false;
		}
		while (false !== ($name = readdir($h))) {
			if ($name == '.' || $name == '..') {
				continue;
			}
			$path = "$dir/$name";
			if (is_dir($path)) {
				P2_FileSystem::rmdir($path);
			} else if (!unlink($path)) {
				throw new Exception("ファイル削除失敗 $path");
			}
		}
		closedir($h);
		if (!rmdir($dir)) {
			throw new Exception("ディレクトリ削除失敗 $dir");
		}
		return true;
	}
	/**
	 *	ディレクトリ内にあるファイル名を全て取得する
	 *	@param	string	$dir
	 *	@return	array	
	 */
	public static function getFileNames($dir) {
		if (!$h = @opendir($dir)) {
			return false;
		}
		$files = array();
		while (false !== ($name = readdir($h))) {
			if ($name == '.' || $name == '..') {
				continue;
			}
			if (is_file("$dir/$name")) {
				$files[] = $name;
			}
		}
		closedir($h);
		return $files;
	}
	/**
	 *	ディレクトリ内にあるディレクトリとファイルのパスを全て取得する
	 *	@param	string	$dir
	 *	@param	string	$ignorePattern	(Optional)無視するファイル名の正規表現（preg系）
	 *	@return	array	
	 */
	public static function getAllPaths($dir, $ignorePattern = '') {
		if (!$h = @opendir($dir)) {
			return false;
		}
		$paths = array();
		while (false !== ($name = readdir($h))) {
			if ($name == '.' || $name == '..') {
				continue;
			} else if ($ignorePattern && preg_match($ignorePattern, $name)) {
				continue;
			}
			$path = "$dir/$name";
			$paths[$path] = is_dir($path);
		}
		closedir($h);
		return $paths;
	}
	/**
	 *	アップロードされたファイルを元の拡張子のまま保存する
	 *	@param	string	$postKey
	 *	@param	string	$dir
	 *	@param	string	$fileName
	 */
	public static function saveUploadedFile($postKey, $dir, $fileName) {
		$extension = P2_FileSystem::getExtension($_FILES[$postKey]['name']);
		move_uploaded_file($_FILES[$postKey]['tmp_name'], "$dir$fileName.$extension");
	}
	/**
	 *	拡張子を取得する
	 *	@param	string	$path
	 *	@return	string	小文字にした拡張子
	 */
	public static function getExtension($path) {
		$pathinfo = pathinfo($path);
		return strToLower($pathinfo['extension']);
	}
}
