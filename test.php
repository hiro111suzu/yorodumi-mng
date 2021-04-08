<?php 
include "commonlib.php";
unset( $a, $b );
_m(
	$a ?: 'hoge' ?: 'fuga'
);

/*
 $text = "私は日本人です";
 // 翻訳モード（日→英）
 $enc = "ja|en";

$url = "https://translate.google.com/translate_t?langpair=$enc&ie=UTF8&oe=UTF8&text=".urlencode($text);
$html = file_get_contents($url);
_m( $html );
// テキスト部分取り出し
$pattern = '/<div id=result_box dir="ltr">(.*?)<\/div>/';
preg_match($pattern, $html, $matche);
$trans = $matche[1];
// 翻訳されたテキスト
echo $trans;

//$str = 'hogerian';
//echo "start\n";
//_m( 'hoge', -1 );
//_m( 'fuga', 1 );
//foreach ( range( 1, 10 ) as $i ) {
//	echo "\033[1A\33[2K$i\n";
//	sleep(1);
//	echo "hogerian-fugafuga\n";
//}


//echo "\033[1A\033[1;31m$str\033[0m";
//die( json_encode([ 'hoge' => 'fuga' ]) );
/*
$o = new cls_entid( '1003' );
echo $o->mainjson()->deposition->primaryReference->journalArticle->articleTitle;
*/
