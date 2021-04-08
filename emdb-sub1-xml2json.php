emdb xml => json
"emdbidlist" を作る

<?php
include "commonlib.php";

$idlistfn = DN_DATA . '/emdbidlist.txt';

//. 変換文字列
$rep_pre = _rep_prep( <<<'EOF'

>[\n\r ]+<					|	><			//- 空白改行だけの要素
[\n\r\t]+					|	' '			//- 改行などを空白に
  +							|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>					|				//- xml宣言消す
units=".+?">				|	>			//- 単位を消す(EMDBのみ)

 Kang, SA<					|	 Kang SA<		//- 個別の間違い
 Harrison, SC<				|	 Harrison SC<	//- 個別の間違い

<file (.+?)>(.+?)<\/file>	|	<file $1 name="$2" />

<(sciSpeciesName|expSystem|hostSpecies) ncbiTaxId="(.+?)">(.+?)<	|	<ncbiTaxId>$2</ncbiTaxId><$1>$3<

<externalReference type="(.+?)">(.+?)<\/externalReference>	|	<ref_$1>$2</ref_$1>
<externalReference type="pubmed"\/>							|	

<(contourLevel) source="(.+?)">(.+?)<	|	<$1_source>$2</$1_source><$1>$3<

">([^<]+?)<			|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け
<\/?sampleComponentList>	|			//- sampleComponentListタグは無し
<\/?fittedPDBEntryIdList>	|			//- 同上
<\/?pdbEntryIdList>			|			//- 同上

<\/?(mask|figure|slice|fsc)Set\/?>				|

}					|	_|_			//- }を変えておく

>(na|NA|n\/a)<	|	><	
EOF
);

//- 数値しなかいタグ
$s = '';
foreach ( _file( DN_PREP . '/emdb_xml_numtags.txt' ) as $l ) {
	$s .= "(\"$l\":)\"(-?[0-9\\.]+)\"	|	$1$2\n";
}


$rep_post = _rep_prep( $s . <<<'EOF'

":(\.[0-9])			|	":0$1

"@attributes":{(.+?)}	|	$1			//- 属性を普通の値にする
_\|_					|	}			//- }をもとに戻す
^{						|	{"_id":"__id__",	//- 先頭に_idを付加
,"[a-zA-Z0-9_]+":{}			|				//- 空要素消し	空要素があるとエラーが出る
,"[a-zA-Z0-9_]+":{}			|				//- 空要素消し
,"[a-zA-Z0-9_]+":{}			|				//- 空要素消し
"[a-zA-Z0-9_]+":{},			|				//- 空要素消し
"[a-zA-Z0-9_]+":{},			|				//- 空要素消し
"[a-zA-Z0-9_]+":{},			|				//- 空要素消し
"__dummy__",?			|					//- ダミー要素消し

EOF
);

/*
"(numColumns|originCol|limitCol|spacingCol)":		|	"col":
"(numRows|originRow|limitRow|spacingRow)":			|	"raw":
"(numSections|originSec|limitSec|spacingSec)":		|	"sec":
*/
//. 複数タグ

$tags = [
	'fittedPDBEntryId',
	'externalReference',
	'mask',
	'sampleComponent',
	'figure',
	'fsc' , 
	'pdbEntryId',
	'slice',
	'pdbChainId',
	'shell',
	'fitting' ,
	'imaging' ,
	'imageAcquisition' ,
	'refInterpro',
	'refGo',
	'refUniProt' ,
	'secondaryReference',
	'vitrification',
	'reconstruction' ,
];

//. 
//- 複数ありうるタグは、複数個にして、必ず配列になるようにする

$array_tag = [];
foreach ( array_unique( $tags ) as $t ) {
	if ( $t == '' ) continue;
	$array_tag[ "<$t>" ] = "<$t>__dummy__</$t><$t>";
	$array_tag[ "<$t " ] = "<$t>__dummy__</$t><$t ";
}

_count();

//. convert
//foreach ( glob( DN_EMDB_MR . '/structures/*/header/*.xml' ) as $xfn ) {
foreach ( glob( _fn( 'emdb_xml_orig', '*' ) ) as $xfn ) {
	if ( _instr( '-v', $xfn ) ) continue;
	$id = _numonly( basename( $xfn, '.xml' ) );
	$idlist .= "$id\n";

	$jfn = _fn( 'emdb_json', $id );
	if ( _sametime( $xfn, $jfn ) ) continue;

	//- プレ処理
	$cont = preg_replace(
		$rep_pre[ 'in'  ] ,
		$rep_pre[ 'out' ] ,
		file_get_contents( $xfn )
	);
//	file_put_contents( "$jfn.xml", $cont );

	//- 複数の値を持ち得るタグ対策
	$cont = strtr( $cont, $array_tag );

	//- 変改 ポスト修正
	$cont = preg_replace(
		$rep_post[ 'in'  ] ,
		$rep_post[ 'out' ] ,
		json_encode( simplexml_load_string( $cont ), JSON_UNESCAPED_SLASHES )
	);

	//- 変換、書き込み
	_save_touch([
		'infn'  => $xfn ,
		'outfn' => $jfn ,
		'data'  => strtr( $cont, [ '__id__' => $id ] ) ,
//		'name'  => "emdb-$id-json"
	]);
	
	if ( _count( 100, 0 ) ) break; 
}

//- ID list
_comp_save( $idlistfn, $idlist );

//. なくなったのを削除
_delobs_emdb( 'emdb_json' );



//. 複数タグのチェック
_m( _line( '複数タグのチェック' ) );

$multitag = [];
//$count = 0;
foreach ( glob(  _fn( 'emdb_json', '*' ) ) as $fn ) {
	$id = basename( $fn, '.json.gz' );
	if ( ! ctype_digit( $id ) ) {
		_m( "数字じゃない: $id: $fn" );
	}
	_mt(  _json_load2( $fn ) );
}

function _mt( $a, $n = 'root' ) {
	global $multitag, $count, $id, $tags;
	if ( count( $a ) < 1 ) {
		_m( "$id - $n: zero count"  );
		return;
	}
	foreach ( $a as $n => $v ) {
		if ( is_array( $v ) ) {
			$multitag[ $n ] = 1;
			if ( !in_array( $n, $tags ) ) {
				_m( "$id: \"$n\" が複数ある" );
			}
		}
		if ( is_object( $v ) )
			_mt( $v, $n );
	}
}

$multitag = array_keys( $multitag );
sort( $multitag );
sort( $tags );

if ( $tags != $multitag ) {
	foreach ( $multitag as $t ) {
		if ( ! in_array( $t, $tags ) )
			_m( "$t: 新しい、複数タグ!!!", 1 );
	}

	foreach ( $tags as $t ) {
		if ( ! in_array( $t, $multitag ) )
			_m( "$t: 無くなった、複数タグ？？？", 1 );
	}
}
/*
 else {
	_m( '複数タグの種類、変化なし' );
}
*/

_m( ''
	. '[arrayとして処理したタグ]'
	. implode( ', ', $tags )
	. "\n"
	. '[arrayになっていたタグ]'
	. implode( ', ', $multitag )
);

