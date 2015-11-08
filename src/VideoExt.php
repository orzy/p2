<?php
/**
 *  P2_VideoExt
 *
 *  require
 *      * P2_Video
 *
 *  @version 2.1.5
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_VideoExt extends P2_Video {
	const QCIF_WIDTH = 176;
	const QCIF_HEIGHT = 144;

	const QVGA_WIDTH = 320;
	const QVGA_HEIGHT = 240;
	
	const AU_MAX_FILE_SIZE = 1500;

	/**
	 *	MP4ファイルを分割する
	 *	@param	integer	$fileSize
	 */
	public function splitMp4($fileSize) {
		$this->_exec($this->mp4box, "-splits $fileSize " . $this->_path);
	}
	/**
	 *	docomo用に変換する
	 *	※出力の拡張子は".3gp"で（P2_Videoでプログレッシブダウンロード設定される）
	 *	@param	string	$outPath
	 *	@param	string	$imgPath	（省略可）画像を切り出す場合のパス
	 *	@param	integer	$time	（省略可）画像を切り出す場合の開始からの秒数
	 *	@param	array	$prm	（省略可）FFmpegの追加パラメータ
	 */
	public function toDocomo($outPath, $imgPath = '', $time = 0, array $prm = array()) {
		$this->aBitRate = 80;
		$this->aSampRate = 16000;
		$this->_toMobile($outPath, $imgPath, $time, $prm);
	}
	/**
	 *	au用に変換する
	 *	@param	string	$outPath
	 *	@param	string	$imgPath	（省略可）画像を切り出す場合のパス
	 *	@param	integer	$time	（省略可）画像を切り出す場合の開始からの秒数
	 *	@param	array	$prm	（省略可）FFmpegの追加パラメータ
	 */
	public function toAu($outPath, $imgPath = '', $time = 0, array $prm = array()) {
		$this->aBitRate = 64;
		$this->aSampRate = 24000;
		$this->_toMobile($outPath, $imgPath, $time, $prm);
	}
	/**
	 *	@access	private
	 */
	private function _toMobile($outPath, $imgPath, $time, array $prm) {
		//ちょっと古い機種にも対応
		$this->vBitRate = = 64;
		$this->vFrameRate = 10;
		$this->vPadFlg = true;
		$this->aChannel = 2;

		$w = P2_VideoExt::QCIF_WIDTH;
		$h = P2_VideoExt::QCIF_HEIGHT;
		$prm['acodec'] = 'aac';
		
		$this->convert($outPath, $w, $h, $imgPath, $time, $prm);
	}
	/**
	 *	iPod用に変換する
	 *	※出力の拡張子は".mp4"で（P2_Videoで設定がH.264になる）
	 *	@param	string	$outPath
	 *	@param	string	$imgPath	（省略可）画像を切り出す場合のパス
	 *	@param	integer	$time	（省略可）画像を切り出す場合の開始からの秒数
	 *	@param	array	$prm	（省略可）FFmpegの追加パラメータ
	 */
	public function toIPod($outPath, $imgPath = '', $time = 0, array $prm = array()) {
		$this->vBitRate = = 500;
		$this->vFrameRate = 30;
		$this->vPadFlg = true;
		$this->aBitRate = 160;
		$this->aSampRate = 48000;
		$this->aChannel = 2;

		$w = P2_VideoExt::QVGA_WIDTH;
		$h = P2_VideoExt::QVGA_HEIGHT;
		$prm['vlevel'] = 13;	//Baselineプロファイル レベル1.3
		$prm['g'] = 30;	//GOP

		$this->convert($outPath, $w, $h, $imgPath, $time, $prm);
	}
}
