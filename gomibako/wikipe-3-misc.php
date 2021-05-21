<?php
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
require_once( "wikipe-common.php" );
define( 'TYPE', 'misc' );
//define( 'SHOW_DETAIL', true );
define( 'SHOW_DETAIL', false );

define( 'REPLACE', TSV_ANNOT[ 'misc' ] );
//. main

//.. term manual
_line( 'manual' );

foreach ( REPLACE as $key => $term ) {
	_do( true, $key, $key );
}

//.. ec term
_line( 'ec/go/pfam/reactome/interpro' );
foreach ( _json_load( DN_DATA. '/pdb/ecnum2name.json.gz' ) as $key => $val ) {
	if ( _instr( ' *EC ', $val ) ) continue;
	if ( _instr( ' EC ', $val ) ) continue;
	if ( _instr( 'deleted', $val ) ) continue;
	_do( true, $val );
}

//.. go 
foreach ( _json_load( DN_PREP. '/go_info.json.gz' ) as $key => $val ) {
	$val[1] = _reg_rep( $val[1], [
		'/[0-9]+ iron, [0-9]+ sulfur /' => 'Iron-sulfur '
	]);
	_do( true, $val[1] );
}

//.. pfam
foreach ( _json_load( DN_PREP. '/pfam_description.json.gz' ) as $key => $val ) {
	_do( true, $val[1] );
}

//.. reactome 
foreach ( _json_load( DN_PREP. '/reactome.json.gz' ) as $key => $val ) {
	_do( true, strtolower( $val[1] ) );
}

//.. prosite 
foreach ( _json_load( DN_PREP. '/prosite.json.gz' ) as $key => $val ) {
	_do( true, trim( $val[1], '. ' ) );
}
//.. interpro 
foreach ( _json_load( DN_PREP. '/interpro_info.json.gz' ) as $key => $val ) {
	_do( true, trim( $val[1], '. ' ) );
}

//.. autocomplete keyword
_line( 'keyword' );
foreach ( array_keys( _json_load( DN_PREP. '/keyword/kwinfo.json.gz' )['kw'] ) as $w ) {
	$w = _reg_rep( strtolower( $w ), [
		'/$bdna$b/' => 'DNA', 
		'/$brna$b/' => 'RNA', 
		'/$batp$b/' => 'ATP', 
		'/$bgtp$b/' => 'GTP', 
		'/$badp$b/' => 'ADP', 
		'/$bamp$b/' => 'AMP', 
	]);
	_do( true, $w );
}

//.. met
_line( 'met' );
foreach ( _tsv_load2( DN_PREP. '/met/annot.tsv' ) as $k1 => $v1 ) {
	foreach ( $v1 as $k2 => $v2 ) {
		if ( $k1 != 'wikipe' && $k2 != 'wikipe' ) continue;
		if ( $v2 == 'x' ) continue;
	//	_m( "$v2" );
	//	_do( true, strtolower( $v ), $v );
		_download_save( $v2, $v2 );
	}
}

//. function _do
function _do( $flg, $key, $term = '' ) {
	$term = _nn( $term, $key );
	$rep = REPLACE[ strtolower( $term ) ];
//	_m( $term, 'red' );
	if ( $rep ) {
		if ( $rep == 'x' ) {
			$fn = _fn_wikipe_json( 'misc', $key );
			if ( file_exists( $fn ) ) {
				_del( $fn );
				_m( "削除: $key", 'green' );
			} else {
				_m2( "$key: ignore" );
			}
			return;
		}
		$en = $rep;
	} else {
		if ( strlen( $term ) < 4 ) return;

		$en = _get_title_en( $term );
		if ( ! $en )
			$en = _get_title_en( ucfirst( $term ) );
		if ( ! $en ) {
//			$term_t = $term;
//			$en_t = '';
			foreach ( _reps_wikipe_terms() as $rep ) {
				$term = trim( _reg_rep( strtolower( $term ), $rep ), ' .,' );
				if ( strlen( $term ) < 5 ) break;
				$en = _get_title_en( $term );
				if ( $en ) break;
			}
//			if ( $en_t )
//				_pause( "$term => \n$term_t: $en_t" );
		}

//e		}
//		if ( $flg_rep && !$en ) {
//			_m( "Missing replacement: $orig -> $term", 'red' );
//		}
		if ( $en ) 
			_m2( "$term: found", 'blue' );
		else 
			_m2( "$term: Not found", 'red' );

	}
	
	if ( $flg ) 
		_download_save( $key, $en );	
	else
		_check_title( $key, $en );	
	if ( file_exists( DN_TEMP . '/proc/stop' ) ) die();

}


