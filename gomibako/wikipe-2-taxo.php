<?php
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
require_once( "wikipe-common.php" );
define( 'TYPE', 'taxo' );
define( 'IDS', array_keys( _json_load( DN_PREP. '/ranking_taxo.json' ) ) );
$_filenames += [
	'taxo_json' => DN_DATA . '/taxo/json/<id>.json'
];
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
$out = [];
$taxa_list = [];
foreach ( IDS as $sci_name ) {
	_count( 1000 );
	if ( _have_ng_term( $sci_name ) ) continue;
	_m2( $sci_name, 'green' );

	//- taxo_jsonは先頭が大文字
//	$json = _json_load2( _fn( 'taxo_json', ucfirst( _fn_rep( $sci_name ) ) ) );
	$json = _json_load2( _fn_taxo_json( $sci_name ) );
	if ( ! $json ) {
//		_m( "$sci_name: no json file: ". _fn_taxo_json( $sci_name ) , 'green' );
		continue;
	}

	//- 名称、別称
	$name_list = array_merge(
		[ ucfirst( $sci_name ), $json->name ] ,
		(array)$json->oname->{'genbank common name'} ,
		(array)$json->oname->{'common name'} 
	);

	//- 一文字の単語は大文字化してみる
	$name_array = explode( ' ', strtr( $sci_name, [
		'o157'	=> 'O157' ,
		'h7'	=> 'H7',
	]));
	$t = [];
	foreach ( $name_array as $w ) {
		$t[] = strlen( preg_replace( '/[^a-zA-Z]/', '', $w ) ) == 1
			? strtoupper( $w ) : $w;
	}
	$name_list[] = ucfirst( implode( ' ', $t ) );

	if ( _is_virus( $sci_name ) ) {
		if ( _instr( 'human ', $sci_name ) )
			$name_list[] .= strtr( $sci_name, [ 'human ' => '' ] );
	}


	//- ウイルスじゃない
//	if ( _instr( 'phage', $sci_name ) || _instr( 'virus', $sci_name ) ) {
//		foreach ( $
//	} else {
//		$name_list[] = implode( ' ', array_slice( $name_array, 0, 2 ) );
//	}


	//.. まとめ
	if ( ANNOT[ $sci_name ] ) {
		_m2( "annotation: $sci_name => ". ANNOT[ $sci_name ], 'blue' );
		$name_list = [ ANNOT[ $sci_name ] ];
//		_pause( $name_list[0] );
	}
	$en = false;
	foreach ( _uniqfilt( $name_list ) as $w ) {
		if ( strlen( $w ) < 3 ) continue;
		if ( _have_ng_term( $w ) ) continue;
		$en = _get_title_en( $w );
		if ( ! $en )
			$en = _get_title_en( ucfirst( $w ) );
		if ( $en ) {
			_m2( "$w: found" );
			break;
		}
		_m2( "$w: not found", 'red' );
	}

	_download_save( $sci_name, $en );
//	_check_title( $sci_name, $en );

	//.. taxon

	foreach ( (array)$json->line as $key ) {
		$annot = ANNOT[ strtolower( $key ) ];
		if ( $annot ) {
			_m2( "annotation: $key => $annot" );
			$en = $annot;
		} else {
			$en = _get_title_en( $key );
		}
		if ( ! $en ) continue;
//		_check_title( $key, $en );
		_download_save( $key, $en );
	}
}
/*
foreach ( array_keys( $taxa_list ) as $t ) {
	$out[ $t ] = [ $t ];
}
*/
//.. 保存

//_json_save( FN_TAXO_LIST, $out );
//_save_not_found_list();

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

