カテゴリを管理
id2categ.json
categ.tsv

<?php
//. misc. init
require_once( "commonlib.php" );

define( 'FN_DATA', DN_EDIT. "/categ.tsv" );
if ( ! file_exists( FN_DATA ) ) {
	die( 'ファルがない: ' . FN_DATA );
}

$categ_tsv = _tsv_load2( FN_DATA, true );	//- tsvファイルとして読み込んだデータ
$categ_txt = file_get_contents( FN_DATA );		//- テキストとして読み込んだデータ
define( 'FITDB', _json_load( DN_PREP. '/emn/fitdb.json.gz' ) );
//_die( $categ_tsv );

//. tsvファイルに新規データ追加
//.. tsvにないIDを探す（新規ID）
//- EMDB loop
foreach( _idlist( 'emdb' ) as $id ) {
	if ( $categ_tsv[ 'emdb' ][ $id ] != '' ) continue;
	if ( ! _emn_json( 'status', "emdb-$id" )->map ) continue;
	_m( "新規データ: $id" );
	$categ_txt = strtr( $categ_txt, [ '//new-emdb' => "$id\t_\n//new-emdb" ] );
	$changed = true;
}

//- PDB loop
foreach( _idlist( 'epdb' ) as $id ) {
	if ( $categ_tsv[ 'pdb' ][ $id ] != '' ) continue;
	_m( "新規データ: $id" );
	$categ_txt = strtr( $categ_txt, [ '//new-pdb' => "$id\t_\n//new-pdb" ]  );
	$changed = true;
}

//.. 新規IDがあったら書き込み
if ( ! $changed ) {
	_m( '新規データなし' );
} else {
	//- categ tsv
	file_put_contents( FN_DATA, $categ_txt );
}

//. json作成
//.. IDを EMN custom の順で並べる
$dids = [];
foreach ( ( new cls_sqlite( 'main' ) )->qcol([
	'select'    => 'db_id' ,
	'order by'  => 'release DESC, sort_sub DESC, db_id'
]) as $did ) {
	//- マップのない奴を除く
	if ( substr( $did, 0, 1 ) == 'e' && ! _emn_json( 'status', $did )->map ) {
		continue;
	}
	$dids[] = strtolower( $did );
}
_m( "データ数: " . count( $dids ) );

//.. tsvから再読み込み
$categ_tsv = _tsv_load2( FN_DATA, true );

$both = [];
foreach ( [ 'emdb', 'pdb' ] as $db ) {
	foreach ( $categ_tsv[ $db ] as $i => $c ) {
		$both[ "$db-$i" ] = trim( $c );
	}
}

$names = array_keys( $categ_tsv[ 'name' ] );

$cat2id = [];
$id2cat = [];
foreach ( $dids as $did ) {
	list( $db, $id ) = _did( $did );
	$cat = $both[ $did ];

	//- 未定義
	if ( $cat == '_' ) {
		$c = $both[ FITDB[ $did ][0] ];
		if ( $c && $c != '_') {
			//- フィットエントリから取得
			$cat = $c;
		} else {
			$cat = 'uncat';
			_problem( "$did: カテゴリ未分類" );
		}
	}
	if ( ! in_array( $cat, $names ) ) {
		$cat = 'uncat';
		_problem( "$did: 不明なカテゴリ名" );
	}

	$cat2id[ $cat ][] = $did;
	$id2cat[ $id ] = $cat;
}

//.. データ整形
$out = [];
$unit = 1 / count( $names ); //- 色決め用
$num = 0;
foreach ( $names as $n ) {
	if ( count( (array)$cat2id[ $n ] ) == 0 ) {
		$n == 'uncat'
			? _m( '未分類データなし' )
			: _problem( "'$n'に分類されたデータ数がゼロ", 1 )
		;
		continue;
	}

	$out[ $n ] = array(
		'id' => implode( ',', $cat2id[ $n ] ) ,
		'j'  => $categ_tsv[ 'nameja' ][ $n ] ,
		'e'  => $categ_tsv[ 'name'   ][ $n ] ,
		'cl' => _hsv2rgb( $num * $unit, 1, 0.8 )
	);
	++ $num;
}

_comp_save( DN_DATA . '/emn/categ.json'		, $out );
_comp_save( DN_DATA . '/emn/id2categ.json'	, $id2cat );
_end();

//. function 

//.. _hsv2rgb 色 （使っていないが）
function _hsv2rgb( $H, $S, $V ) {
    $RGB = []; 

    if ( $S == 0 ) { 
        $R = $G = $B = $V * 255; 
    } else { 
        $var_H = $H * 6; 
        $var_i = floor( $var_H ); 
        $var_1 = $V * ( 1 - $S ); 
        $var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) ); 
        $var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) ); 

        if       ($var_i == 0) { $var_R = $V     ; $var_G = $var_3  ; $var_B = $var_1 ; } 
        else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $V      ; $var_B = $var_1 ; } 
        else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $V      ; $var_B = $var_3 ; } 
        else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $V     ; } 
        else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $V     ; } 
        else                   { $var_R = $V     ; $var_G = $var_1  ; $var_B = $var_2 ; } 

        $R = $var_R * 255; 
        $G = $var_G * 255; 
        $B = $var_B * 255; 
    } 

    $ret = '#';
	foreach ( array( $R, $G, $B ) as $n )
		$ret .= substr( '00' . dechex( $n ), -2 );
//	_m( "$H, $S, $V =. $ret" );
	return $ret;
//    return $RGB; 
//	return array( hoge( $R ), hoge( $G ), hoge( $B ) );
}

//.. _did
function _did( $did ) {
	return explode( '-', (string)$did );
}
