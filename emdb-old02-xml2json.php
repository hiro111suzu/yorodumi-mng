emdb xml => json

<?php
die();
include "commonlib.php";
define( 'DB_TYPE', 'emdb' );
include "common-xml2json.php";

//. init
//.. pre変換文字列
define( 'REP_PRE', _rep_prep( <<<'EOD'

>[\n\r ]+<					|	><			//- 空白改行だけの要素
[\n\r\t]+					|	' '			//- 改行などを空白に
  +							|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>					|				//- xml宣言消す
units=".+?"(\/?>)			|	$1			//- 単位を消す(EMDBのみ)



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
EOD
));

//.. 個別のpre変換
$ptcl_rep_pre = [
	5118 => _rep_prep('
		 Harrison, SC<				|	 Harrison SC<
	') ,

	//- 3434: 不等号がうまくパースされない
	3434 => _rep_prep('
		&lt;		|	 &lt; 
	')
];

//.. 数値しなかいタグ (未使用)
// $s = '';
// foreach ( _file( DN_PREP . '/emdb_xml_numtags.txt' ) as $l ) {
//	$s .= "(\"$l\":)\"(-?[0-9\\.]+?)\.?\"	|	$1$2\n";
//}

//.. post変換文字列
define( 'REP_POST', _rep_prep( <<<'EOD'
":(\.[0-9])				|	":0$1
EOD
. STR_REP_POST
));

//.. 複数値タグ

$tags = [
	'fittedPDBEntryId',
//	'externalReference',
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
sort( $tags );
define( 'MULTI_TAG', $tags );

_prep_multitag( MULTI_TAG );

//. convert
_count();
foreach ( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	_cnt( 'total' );
	$fn_in  = _fn( 'emdb_xml', $id );
	$fn_out = _fn( 'emdb_old_json', $id );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	//.. プレ処理
	$cont = _pre_conv(
		file_get_contents( $fn_in ) ,
		$ptcl_rep_pre[ $id ]
	);

	//.. ポスト処理
	_post_conv( compact( 'cont', 'id', 'fn_in', 'fn_out' ) );
}

//.. 集計
_line( '集計' );
_cnt();

//. なくなったのを削除
_delobs_emdb( 'emdb_old_json' );

//. 複数値タグのチェック

//.. 全 jsonチェック

$tag_types = [
	'pdbChainId'=> [],
	'pdbEntryId'=> [],
	'refGo'=> [],
	'refInterpro'=> [],
	'refUniProt'=> [],
	'shell'=> [],
];

_count();
$multi_tag_found = [];
foreach ( _idloop( 'emdb_old_json', '複数値タグのチェック' ) as $fn ) {
	$id = _fn2id( $fn );
	_count( 'emdb' );
	if ( ! ctype_digit( $id ) ) {
		_problem( "数字じゃないID: $id: $fn" );
	}
	if ( ! file_exists( $fn ) )
		_problem( "$id: no json file" );
	else 
		_mt( _json_load2( $fn ) );
}

//.. 多重走査するための関数
function _mt( $obj, $name = 'root' ) {
	global $multi_tag_found, $id, $tag_types;
	if ( count( (array)$obj ) == 0 ) {
//		_problem( "$id - \"$name\" 値なし"  );
//		_pause();
		return;
	}
//	_pause();

	foreach ( $obj as $key => $val ) {
		if ( is_array( $val ) ) {
			$multi_tag_found[ $key ] = true;
			if ( ! in_array( $key, MULTI_TAG ) ) {
				_problem( "$id: タグ名 \"$key\" が配列になっている（複数要素）" );
			}
		}

		//- 集計
		$type = gettype( $val );
		if ( $type == 'integer' || $type == 'double' )
			$type = 'numeric';
		++ $tag_types[ $key ][ $type ];

		//... より深い階層
		if ( $type == 'object' )
			_mt( $val, $key );

		if ( $type == 'array' ) foreach( $val as $child ) {
			if ( is_object( $child ) )
				_mt( $child, $key );
		}
	}
}

//.. 集計

$multi_tag_found = array_keys( $multi_tag_found );
sort( $multi_tag_found );

if ( MULTI_TAG != $multi_tag_found ) {
	foreach ( $multi_tag_found as $t ) {
		if ( ! in_array( $t, MULTI_TAG ) )
			_problem( "$t - 新しい複数要素タグ" );
	}

	foreach ( MULTI_TAG as $t ) {
		if ( ! in_array( $t, $multi_tag_found ) )
			_problem( "$t - 無くなった複数要素タグ" );
	}
} else {
	_m( "複数要素タグ処理、問題なし" );
}

ksort( $tag_types );
_json_save( DN_PREP . '/emdbxml_tagtypes.json', $tag_types );

//. end
_end();
