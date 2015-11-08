<?php
/**
 *  P2_Image
 *
 *    GIF, JPEG, PNGに対応
 *
 *  require
 *      * GD2
 *      * P2_PublicVars
 *
 *  @version 2.2.1a
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  See Also
 *  @see http://php.net/image
 */
class P2_Image extends P2_PublicVars {
	/** 縦横比を変えない */
	const COPY_SAME = 'same';
	/** 足りない分はPADを入れる */
	const COPY_PAD  = 'pad';
	/** 余った部分は切り捨てる */
	const COPY_TRIM = 'trim';
	
	//コピー時の設定
	public static $padColor = array(0x99, 0x99, 0x99);
	public static $quality = 100;
	public static $interlaceFlg = true;
	
	public $w;
	public $h;
	public $imageType;

	private $_path;
	
	/**
	 *	コンストラクタ
	 *	@param	string	$path
	 *	@param	raw	$binaryData	（省略可）バイナリデータをファイル化する場合に渡す
	 */
	public function __construct($path, $binaryData = null) {
		$this->_path = $path;
		if ($binaryData) {
			file_put_contents($path, $binaryData);
		}
		list($this->w, $this->h, $this->imageType) = @getImageSize($path);
		
		if (!$this->w || !$this->h) {
			throw new Exception("画像ファイルの取得に失敗 [path]$path");
		}
	}
	/**
	 *	画像をコピーする
	 *	@param	string	$dstPath	コピー元と同じパスの場合、それ自体を変更する
	 *	@param	integer	$w	（省略可）
	 *	@param	integer	$h	（省略可）
	 *	@param	string	$copyType	（省略可）このクラスの定数で指定する
	 *	@return	P2_Image
	 */
	public function copy($dstPath, $w = 0, $h = 0, $copyType = self::COPY_SAME) {
		$dstX = 0;
		$dstY = 0;
		$srcX = 0;
		$srcY = 0;
		list($dstW, $dstH) = $this->getFitSize($w, $h);
		$srcW = $this->w;
		$srcH = $this->h;
		
		switch ($copyType) {
			case self::COPY_SAME:
				$w = $dstW;
				$h = $dstH;
				break;
			case self::COPY_PAD:
				$dstX = floor(($w - $dstW) / 2);
				$dstY = floor(($h - $dstH) / 2);
				break;
			case self::COPY_TRIM:
				$scale = max(($w / $srcW), ($h / $srcH));
				$srcX = floor(($srcW - ($w / $scale)) / 2);
				$srcY = floor(($srcH - ($h / $scale)) / 2);
				$dstW = $w;
				$dstH = $h;
				$srcW -= $srcX * 2;
				$srcH -= $srcY * 2;
				break;
		}
		
		$srcImg = $this->_getSrcImg();
		$dstImg = imageCreateTrueColor($w, $h);
		if ($copyType == self::COPY_PAD) {
			$padColor = self::$padColor;
			$color = imageColorAllocate($dstImg, $padColor[0], $padColor[1], $padColor[2]);
			imageFill($dstImg, 0, 0, $color);	//余白の色
		}

		imageCopyResampled(
			$dstImg, $srcImg,
			$dstX, $dstY, $srcX, $srcY,
			$dstW, $dstH, $srcW, $srcH
		);
		$this->_createFile($dstImg, $dstPath);

		imageDestroy($srcImg);
		imageDestroy($dstImg);
		
		return new self($dstPath);
	}
	/**
	 *	縦横比を維持したまま拡大/縮小したサイズを取得する
	 *	@param	integer	$dstW	高さのみ指定する場合はゼロを渡す
	 *	@param	integer	$dstH	（省略可）
	 *	@return	array
	 */
	public function getFitSize($dstW, $dstH = 0) {
		return self::calcFitSize($this->w, $this->h, $dstW, $dstH);
	}
	/**
	 *	縦横比を維持したまま拡大/縮小したサイズを取得する
	 *	@param	integer	$srcW
	 *	@param	integer	$srcH
	 *	@param	integer	$fitW	高さのみ指定する場合はゼロを渡す
	 *	@param	integer	$fitH	（省略可）
	 *	@return	array
	 */
	public static function calcFitSize($srcW, $srcH, $dstW, $dstH = 0) {
		if ($dstW) {
			$scaleW = $dstW / $srcW;
		}
		if ($dstH) {
			$scaleH = $dstH / $srcH;
		}
		if ($scaleW && $scaleH) {
			$scale = min($scaleW, $scaleH);	//収まる方を採用
		} else if ($scaleW || $scaleH) {
			$scale = max($scaleW, $scaleH);	//存在する方を採用
		} else {
			$scale = 1;
		}
		return array(floor($srcW * $scale), floor($srcH * $scale));
	}
	/**
	 *	@access	private
	 */
	private function _getSrcImg() {
		switch ($this->imageType) {
			case IMAGETYPE_GIF:
				 return imageCreateFromGif($this->_path);
			case IMAGETYPE_JPEG:
				return imageCreateFromJpeg($this->_path);
			case IMAGETYPE_PNG:
				 return imageCreateFromPng($this->_path);
		}
		$ext = image_type_to_extension($this->imageType);
		throw new Exception("この画像形式（ $ext ）からは変換できません");
	}
	/**
	 *	@access	private
	 */
	private function _createFile($dstImg, $dstPath) {
		$ext = strToLower(pathInfo($dstPath, PATHINFO_EXTENSION));
		switch ($ext) {
			case 'gif':
				imageGif($dstImg, $dstPath);
				break;
			case 'jpeg':
			case 'jpg':
				imageJpeg($dstImg, $dstPath, self::$quality);
				break;
			case 'png':
				//PNGのqualityは 0（無圧縮）～ 9（高圧縮）
				$quality = min(floor((100 - self::$quality) / 10), 9);
				imagePng($dstImg, $dstPath, $quality);
				break;
			default:
				throw new Exception("この画像形式（ $ext ）へは変換できません");
		}
		
		if (self::$interlaceFlg) {
			//JPEGの場合はプログレッシブJPEGになる（携帯電話で表示できないので注意）
			imageInterlace($dstImg, 1);
		}
	}
	/**
	 *	指定した色を透過にする
	 *	@see http://php.net/imageColorTransparent
	 *	@see http://php.net/imageColorAllocate
	 */
	public function transparentize($r = 255, $g = 255, $b = 255) {
		$w = $this->w;
		$h = $this->h;
		
		$srcImg = $this->_getSrcImg();
		$dstImg = imageCreateTrueColor($this->w, $this->h);
		
		imageCopyResampled($dstImg, $srcImg, 0, 0, 0, 0, $w, $h, $w, $h);
		imageColorTransparent($dstImg, imageColorAllocate($dstImg, $r, $g, $b));
		
		$this->_createFile($dstImg, $this->_path);
		
		imageDestroy($srcImg);
		imageDestroy($dstImg);
	}
}
