<?php
/**
 *  P2_Template
 *
 *  (experimental)
 *
 *    テンプレートの文法
 *        ${変数名}	変数を出力
 *        %{変数名}	変数をHTMLエスケープして出力
 *        #{ PHP }	PHPの実行結果を出力
 *        %#{ PHP }	PHPの実行結果をHTMLエスケープして出力
 *        !{ PHP }	PHPを実行（if, for, foreach, whileは、最後に"{"を付けないこと）
 *        !{/}	if文などの"}"を閉じる
 *        ${配列.インデックス}	配列の該当値を出力
 *        %{配列.インデックス}	配列の該当値ををHTMLエスケープして出力
 *
 *  require
 *      * (none)
 *
 *  @version 2.1.0
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Template {
    private static $_cacheDir;
  
    /**
     *	キャッシュファイルを入れておくディレクトリを指定する
     *	指定しない場合はテンプレートファイルと同じ場所にキャッシュファイルを作成する
	 *	@param	string	$dir
     */
    public static function setCacheDir($dir) {
        P2_Template::$_cacheDir = $dir;
    }
    /**
     *	表示する
     *	@param	string	$teimlatePath	テンプレートファイルのパス
     *	@param	object	$D	データコンテナ（strClassかそれ相当のオブジェクト）
     */
    public static function show($templatePath, $D) {
        $cachePath = P2_Template::$_cacheDir . '/' . str_replace('/', '_.', $templatePath) . '.cache.php';
        if (!file_exists($cachePath) || filemtime($cachePath) < filemtime($templatePath)) {
            $s = P2_Template::_template2php(file_get_contents($templatePath));
            file_put_contents($cachePath, $s);
        }
        require($cachePath);
    }
    /**
	 *	@access	private
	 */
    private static function _template2php($s) {
        $r['/<\\?xml/']	= '<<?php ?>?xml';
        $r['/%\{(.*?)\.(.*?)\}/']	= '<?php echo htmlspecialchars($$1["$2"], ENT_NOQUOTES); ?>';
        $r['/%\{(.*?)\}/']	= '<?php echo htmlspecialchars($$1, ENT_NOQUOTES); ?>';
        $r['/%#\{(.*?)\}/']	= '<?php echo htmlspecialchars($1, ENT_NOQUOTES); ?>';

        $r['/\$\{(.*?)\.(.*?)\}/']	= '<?php echo $$1["$2"]; ?>';
        $r['/\$\{(.*?)\}/']	= '<?php echo $$1; ?>';
        $r['/#\{(.*?)\}/']	= '<?php echo $1; ?>';

        $r['/!\{(if|for|while)(.*?)\}/']	= '<?php $1$2 { ?>';
        $r['/!\{else(.*?)\}/']	= '<?php } else$1 { ?>';
        $r['/!\{\/(.*?)\}/']	= '<?php } ?>';
        $r['/!\{(.*?)\}/']	= '<?php $1; ?>';

        $s = preg_replace(array_keys($r), array_values($r), $s);

        return preg_replace('/\$([a-z][a-z0-9_]*?)/i', '$D->$1', $s);
    }
}
