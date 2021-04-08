<?php
//die('工事中');
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
require_once( "wikipe-common.php" );
define( 'TYPE', 'taxo' );
_define_annot( 'taxo' );
define( 'SHOW_DETAIL', false );

define( 'NG_TERMS', [
	'other ' ,
	'unidentified' ,
	'synthetic' ,
	'artificial' ,
//	'(strain' ,
//	'subsp.' ,
]);

//. main 
$o_sql = new cls_sqlite( 'taxo' );
$taxon_done = [];

foreach ( $o_sql->qcol([ 'select' => 'key' ]) as $id )  {
	_count( 1000 );

	$name = $json2 = $line = '';
	extract( $o_sql->qar([
		'select' => [ 'name', 'json2', 'line' ],
		'where' => "key='$id'" ]
	)[0]);
	if ( _have_ng_term( $name ) ) continue;

	//.. 手動で指定
	$annot = ANNOT[ strtolower( $name ) ];
	if ( $annot ) {
		_regist_annot_term( $name, $annot );
		_regist_annot_term( $id, $annot );
		_m( "$name => $annot" );
		continue;
	}

	//.. 名称、別称
	$name_list = [ $name, ucfirst( $name ) ];
	foreach ( (array)json_decode( $json2 )->on as $c ) {
		$name_list = array_merge( $name_list, $c );
	}
	_m2( $name, 'green' );

	//- 一文字の単語は大文字化してみる
	$name_array = explode( ' ', strtr( $name, [
		'o157'	=> 'O157' ,
		'h7'	=> 'H7',
	]));
	$t = [];
	foreach ( $name_array as $w ) {
		$t[] = strlen( preg_replace( '/[^a-zA-Z]/', '', $w ) ) == 1
			? strtoupper( $w ) : $w;
	}
	$name_list[] = ucfirst( implode( ' ', $t ) );

	//- human hogehoge virus => hogehoge virus
	if ( _is_virus( $name ) ) {
		if ( _instr( 'human ', $name ) )
			$name_list[] .= strtr( $name, [ 'human ' => '' ] );
		$name_list[] = preg_replace( '/(virus).+$/i', '$1', $name ); 
//		_pause([ $name, preg_replace( '/(virus).+$/i', '$1', $name ) ]);
	} else {
		$name_list[] = implode( ' ', array_slice(
			explode( ' ', $name ) ,
			0, 2
		));
	}

	//.. まとめ
	_regist( $name, $name_list );
	_regist( $id, $name_list );
//	_pause( $regist );
//	$regist = [];

	//.. taxon
	foreach ( array_filter( explode( '|', $line ) ) as $key ) {
		if ( $taxon_done[ $key ] ) continue;
		$taxon_done[ $key ] = true;

		$annot = ANNOT[ strtolower( $key ) ];
		if ( $annot ) {
			_regist_annot_term( $key, $annot );
			continue;
		}
		_regist( $key, [ $key ] );
	}
//	_pause( $regist );
//	$regist = [];

}

//. 保存終了
_regist_save();


//. function
//.. _is_virus
function _is_virus( $name ) {
	return _instr( 'virus', $name ) || _instr( 'phage', $name );
}

