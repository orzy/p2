<?php
/**
 *  P2_Video
 *
 *  require
 *      * FFmpeg
 *      * MEncoder
 *      * MediaInfo 0.7.7.3+
 *      * FLVMDI
 *      * MP4Box
 *      * P2_Image
 *      * P2_Log
 *      * P2_PublicVars
 *
 *  @version 2.1.5
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Video extends P2_PublicVars {
	//ツール等のファイルパス
	public $ffmpeg;
	public $mencoder;
	public $mediaInfo;
	public $flvmdi;
	public $mp4box;
	public $workDir;

	//映像の変換設定
	public $vBitRate;
	public $vFrameRate;
	public $vPadFlg;

	//音声の変換設定
	public $aBitRate;
	public $aSampRate;
	public $aChannel;

	//ログファイル名
	public $logFileName;
	
	
	//変換元動画情報
	protected $_path;
	private $_w;
	private $_h;
	private $_aFormat;
	
	private $_ffmpegInPath;
	private $_readyFlg;
	
	/**
	 *	コンストラクタ
	 *	@param	string	$path	動画ファイルのパス
	 *	@param	string	$toolsPath	（省略可）ツールの親ディレクトリのパス
	 */
	public function __construct($path, $toolsPath = '') {
		if (!file_exists($path)) {
			throw new Exception("動画ファイルの取得失敗 path: $path");
		}
		
		$this->_path = $path;
		$this->_readyFlg = false;
		$this->_w = 0;
		$this->_h = 0;
		
		$this->setDefault();

		if ($toolsPath) {
			$this->ffmpeg = "$toolsPath/ffmpeg/ffmpeg";
			$this->mencoder = "$toolsPath/MPlayer/mencoder";
			$this->mediaInfo = "$toolsPath/mediaInfo/mediaInfo";
			$this->flvmdi = "$toolsPath/flvmdi/flvmdi";
			$this->mp4box = "$toolsPath/Yamb/mp4box";
		}
	}
	/**
	 *	変換設定をデフォルトにする
	 */
	public function setDefault() {
		$this->vBitRate = 2000;
		$this->vFrameRate = 30;
		$this->vPadFlg = false;
		$this->aBitRate = 256;
		$this->aSampRate = 44100;
		$this->aChannel = 2;
	}
	/**
	 *	動画を変換する
	 *	@param	string	$outPath
	 *	@param	integer	$w	（省略可）最大の幅
	 *	@param	integer	$h	（省略可）最大の高さ
	 *	@param	string	$imgPath	（省略可）画像を切り出す場合のパス
	 *	@param	integer	$time	（省略可）画像を切り出す場合の開始からの秒数
	 *	@param	array	$prm	（省略可）FFmpegの追加パラメータ
	 */
	public function convert($outPath, $w = 0, $h = 0, $imgPath = '', $time = 0, array $prm = array()) {
		$this->_setUp();
		$outExtension = $this->_getExtension($outPath);
		
		$prm = array_merge($this->_getParams($w, $h, $outExtension), $prm);
		
		//FFmpegコマンド作成
		$command =  '-i ' . $this->_ffmpegInPath;
		foreach ($prm as $key => $value) {
			$command .= " -$key $value";
		}
		$command .= " -hq -y $outPath";
		
		//変換
		$this->_exec($this->ffmpeg, $command);
		
		if (!file_exists($outPath) || !fileSize($outPath)) {	//結果判定
			throw new Exception("動画の変換に失敗 \n FFmpegコマンド : $command");
		}
		
		if ($imgPath) {
			$this->getImage($imgPath, $time, $w, $h);	//画像ファイルの作成
		}
		
		//追加処理
		switch ($outExtension) {
		case 'flv':
			$this->_exec($this->flvmdi, $outPath);	//FLVMDIによりMetaData付加
			break;
		case '3gp':
			//docomoでプログレッシブダウンロード可能にする
			$this->_exec($this->mp4box, "-add $outPath -brand mmp4:1 -new $outPath");
			break;
		}
	}
	/**
	 *	画像を切り出す
	 *	@param	string	$outPath
	 *	@param	integer	$time	（省略可）開始からの秒数
	 *	@param	integer	$w	（省略可）最大の幅
	 *	@param	integer	$h	（省略可）最大の高さ
	 *	@return	P2_Image
	 */
	public function getImage($outPath, $time = 0, $w = 0, $h = 0) {
		$this->_setUp();
		
		if ($this->_w && $this->_h) {	//変換元動画のサイズが分かっている場合
			if ($w || $h) {	//サイズ指定あり
				list($dstW, $dstH) = P2_Image::calcFitSize($this->_w, $this->_h, $w, $h);
			} else {
				$dstW = $this->_w;
				$dstH = $this->_h;
			}
			$size = '-s ' . $this->_toSizeParam($dstW, $dstH);
		}

		//FFmpegコマンド作成
		$command = "-ss $time -i " . $this->_ffmpegInPath;
		$command .= " $size -f image2 -vframes 1 -hq -an -y $outPath";
		
		//FFmpegで切り出す
		$this->_exec($this->ffmpeg, $command);
		
		try {
			$image = new P2_Image($outPath);
		} catch (Exception $e) {
			throw new Exception($e->getMessage() . "\n FFmpegコマンド : $command");
		}
		
		if (!$size) {	//変換元動画のサイズが不明だった場合
			//再利用のために変換元の動画サイズを保存
			$this->_w = $image->w;
			$this->_h = $image->h;

			if ($w || $h) {	//サイズ指定あり
				$image = $image->copy($outPath, $w, $h);	//リサイズ
			}
		}
		
		return $image;
	}
	/**
	 *	@access	private
	 */
	private function _setUp() {
		if ($this->_readyFlg) {
			return;
		}
		$this->_analize();
		$this->_setFFmpegInPath();
		$this->_readyFlg = true;
	}
	/**
	 *	MediaInfoを使って動画情報を取得する
	 *	@access	private
	 */
	private function _analize() {
		$videoInfo = $this->_exec($this->mediaInfo, $this->_path);
		$arr = array();
		foreach ($videoInfo as $info) {
			if (preg_match('/^([a-zA-Z]+)$/', $info, $matches)) {
				$infoType = $matches[1];	//"General" or "Video" or "Audio"
				$arr[$infoType] = array();
			} else {
				$infoRow = explode(':', $info);
				$infoData = explode(' ', trim($infoRow[1]));
				$arr[$infoType][rtrim($infoRow[0])] = ltrim($infoData[0]);
			}
		}

		if ($arr['Video']) {
			$this->_h = $arr['Video']['Height'];
			if ($arr['Video']['Display aspect ratio']) {
				$aspectArr = explode('/', $arr['Video']['Display aspect ratio']);
				if (count($aspectArr) == 1) {	//数値として取得した場合
					$aspect = $aspectArr[0];
				} else {	//"w/h"形式で取得した場合
					$aspect = $aspectArr[0] / $aspectArr[1];
				}
				$this->_w = $this->_h * $aspect;
			} else {
				$this->_w = $arr['Video']['Width'];
			}
		}
		if ($arr['Audio']) {
			$this->_aFormat = $arr['Audio']['Format'];
		}
	}
	/**
	 *	@access	private
	 */
	private function _setFFmpegInPath() {
		$extension = $this->_getExtension($this->_path);
		
		if ($extension == '3g2') {	//ムービーフラグメントを結合
			$this->_ffmpegInPath = $this->workDir . 'TMP_CONCAT.3g2';
			$this->_exec($this->mp4box, '-add ' . $this->_path . ' -new ' . $this->_ffmpegInPath);
		} else {
			$this->_ffmpegInPath = $this->_path;
		}
		
		//WMV9や一部のAACはFFmpegが対応していないので、いったん別の形式に変換
		if ($extension == 'wmv' || $this->_aFormat == 'AAC') {
			$tmpPath = $this->workDir . 'TMP_MENCODER.avi';
			$options = '-oac mp3lame -ovc lavc -lavcopts vcodec=mjpeg';
			$this->_exec($this->mencoder, $this->_ffmpegInPath . " -o $tmpPath $options");
			$this->_ffmpegInPath = $tmpPath;
		}
	}
	/**
	 *	@access	private
	 */
	private function _getExtension($path) {
		$pathInfo = pathinfo($path);
		return strToLower($pathInfo['extension']);
	}
	/**
	 *	@access	private
	 */
	private function _getParams($w, $h, $outExtension) {
		$prm = array();
		$prm['b'] = $this->vBitRate;
		$prm['r'] = $this->vFrameRate;
		$sizeArr = $this->_getFitSize($w, $h);
		if ($sizeArr) {
			$prm['s'] = $this->_toSizeParam($sizeArr[0], $sizeArr[1]);
		}
		if ($this->vPadFlg) {	//PADあり
			if (!$sizeArr) {
				throw new Exception('PADにはサイズ指定が必要');
			}
			$prm['padleft'] = $this->_roundEven(($w - $sizeArr[0]) / 2);
			$prm['padright'] = $prm['padleft'];
			$prm['padtop'] = $this->_roundEven(($h - $sizeArr[1]) / 2);
			$prm['padbottom'] = $prm['padtop'];
		}
		if ($outExtension == 'mp4') {
			$prm['vcodec'] = 'h264';	//圧縮率が高いので
		}
		$prm['ab'] = $this->aBitRate;
		$prm['ar'] = $this->aSampRate;
		$prm['ac'] = $this->aChannel;
		
		return $prm;
	}
	/**
	 *	@access	private
	 */
	private function _getFitSize($w, $h) {
		if ($this->_w && $this->_h) {	//変換元動画のサイズ特定済み
			if ($w || $h) {	//サイズ指定あり
				return P2_Image::calcFitSize($this->_w, $this->_h, $w, $h);

			} else {	//サイズそのまま
				return array($this->_w, $this->_h);
			}
		} else {	//変換元動画のサイズ不明
			if ($w || $h) {	//サイズ指定あり
				//画像を切り出してサイズを取得
				$workPath = $this->workDir . 'TMP_IMAGE.jpg';
				$image = $this->getImage($workPath, 0, $w, $h);
				unlink($workPath);
				return array($image->w, $image->h);

			} else {	//サイズそのまま
				return null;
			}
		}
	}
	/**
	 *	@access	private
	 */
	private function _toSizeParam($w, $h) {
		$w = $this->_roundEven($w);
		$h = $this->_roundEven($h);
		return "$w*$h";
	}
	/**
	 *	@access	private
	 */
	private function _roundEven($size) {
		$size = (integer)floor($size);
		return $size - ($size % 2);
	}
	/**
	 *	@access	protected
	 */
	protected function _exec($exe, $option) {
		$command = "$exe $option";
		if ($this->logFileName) {
			$log = new P2_Log($this->logFileName);
			$log->log($command);
		}
		exec($command, $out);
		return $out;
	}
}
