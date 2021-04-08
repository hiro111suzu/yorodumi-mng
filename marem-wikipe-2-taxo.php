<?php
//. init
require_once( "wikipe-common.php" );
define( 'TYPE', 'taxo' );

$o_dbtx = new cls_sqlite('taxo');
//$o_dbtxid = new cls_sqlite('taxoid');

define( 'KEYS', $o_dbtx->qcol(['select' => 'key']) );

_define_annot( 'taxo' );
define( 'SHOW_DETAIL', false );

define( 'NG_TERMS', [
	'other ' ,
	'unidentified' ,
	'unknown' ,
	'undetermined' ,
	'synthetic' ,
	'artificial' ,
]);

$taxon_all = [];

define( 'TAXA_NOT_GENUS', [
	'Bacteria' ,
]);

//. main 
_line( 'main' );
$out = [];
$taxa_list = [];
foreach ( KEYS as $key ) {
	_count( 1000 );
	$ans = $o_dbtx->qobj([
		'select' => '*',
		'where' => 'key='. _quote( $key )
	])[0];

	$name = $ans->name;
	if ( _have_ng_term( $sci_name ) ) continue;

	//.. マニュアル指定
	if ( ANNOT[ $name ] ){
		_regist_annot_term( $key, ANNOT[ $name ] );
		continue;
	}

	//.. 整理
	$oname = json_decode( $ans->json2 )->on;
	$line = (array)array_filter( array_reverse( (array)explode( '|', $ans->line ) ) );

	//.. 名称など
	//- 名称、別称
	$name_list = [];
	foreach ( array_merge(
		[ $name ] ,
		(array)$oname->gs ,
		(array)$oname->eq ,
		(array)$oname->s ,
		(array)$oname->gc ,
		(array)$oname->c 
	) as $n ) {
		$name_list[] = $n;
		$name_list[] = _cpt($n );
	}


	//.. 短くしてみる
	foreach ( $name_list as $n ) {
		$name_list[] = _short_name( $n );
	}

	//.. 親
	foreach ( $line as $l ) {
		$name_list[] = $l;
		if ( ! _instr( ' ', $l ) ) break;
	}

	//.. check
//	if ( $key == 3665 ) {
//		_pause( $name_list );
//	}

	//.. まとめ
	_regist( $key, $name_list );
	
	//.. taxon
	foreach ( $line as $l ) {
		$taxon_all[ $l ] = true;
	}
}

//. taxon
_line( 'taxa' );
foreach ( array_keys( $taxon_all ) as $t ) {
	_regist( $t, in_array( $t, TAXA_NOT_GENUS ) ? [ $t ]: [ "$t (genus)", $t ] );
}
_m( '>>>' . $regist[ 'Euarchontoglires'] );
_regist_save();

//. function _short_name
//.. _short_name
function _short_name( $name ) {
	if ( _is_virus( $name ) ) {
		$out = '';
		foreach ( explode( ' ', $name ) as $w ) {
			$out .= " $w";
			if ( _is_virus( $out ) )
				return trim( $out );
		}
	} else {
		list( $w1, $w2 ) = explode( ' ', $name, 3 );
		return "$w1 $w2";
	}
}

//.. _is_virus
function _is_virus( $name ) {
	return _instr( 'virus', $name ) || _instr( 'phage', $name );
}

