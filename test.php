<?php 
include "commonlib.php";
foreach ([
32459,
32460,
12859,
12864,
12873,
13603,
25066,
25067,
25696,
25834,
25835,
30954,
30955,
31021,
31022,
31027,
31028,
31038,
13936,
30946,
13314,
25737,
25738,
25739,
25740,
25741,
25819,
25820,
25821,
25822,
25823,
13290,
13315,
30938,
13927,
13928,
13929,
22416,
14048,
14064,
14065,
23378,
14066,
23379,
23380,
23381,
23382,
23383,
23384,
23385,
24674,
26038,
26039,
26040,
26041,
26042,
26043,
26045,
26046,
26047,
26048,
26049,
26050,
26051,
26052,
26053,
26055,
26059,

] as $id ) {
	_m( $id );
//	$fn = _fn( 'emdb_med', $id ). '/mapi/proj0.jpg'; //'/mapi/hists.png' ;
	$fn = _fn( 'emdb_med', $id ). '/mapi/hists.png' ;
	if ( file_exists( $fn )  ) {
		_m( 'ある 削除' );
		unlink( $fn );
	} else {
		_m( 'ない' );
	}

}
/*
unset( $a, $b );
_m(
	$a ?: 'hoge' ?: 'fuga'
);
*/
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
