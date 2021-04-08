<?php
//. init
require_once( "wikipe-common.php" );
define( 'TYPE', 'misc' );

//define( 'SHOW_DETAIL', true );
define( 'SHOW_DETAIL', false );

define( 'REPLACE', array_change_key_case( TSV_ANNOT[ 'misc' ], CASE_UPPER ) );

define( 'NG_TERMS', []);

//. main

//.. term manual
_line( 'manual' );

foreach ( REPLACE as $key => $term ) {
	_regist_annot_term( strtoupper( $key ), $term );
}

//.. ec term
_line( 'ec/go/pfam/reactome/interpro' );
_m( 'ec', 1 );
foreach ( _json_load( FN_EC_NAME ) as $key => $val ) {
	if ( _instr( ' *EC ', $val ) ) continue;
	if ( _instr( ' EC ', $val ) ) continue;
	if ( _instr( 'deleted', $val ) ) continue;
	_do( $val );
}

//.. go 
_m( 'go', 1 );
foreach ( _json_load( FN_GO_JSON ) as $key => $val ) {
	$val[1] = _reg_rep( $val[1], [
		'/[0-9]+ iron, [0-9]+ sulfur /' => 'Iron-sulfur '
	]);
	_do( $val[1] );
}

//.. pfam
_m( 'pfam', 1 );
foreach ( _json_load( FN_PFAM_JSON ) as $key => $val ) {
	_do( $val[1] );
}

//.. reactome 
_m( 'reactome', 1 );
foreach ( _json_load( FN_REACT_JSON ) as $key => $val ) {
	_do( strtolower( $val[1] ) );
}

//.. prosite 
_m( 'prosite', 1 );
foreach ( _json_load( FN_PROSITE_JSON ) as $key => $val ) {
	_do( trim( $val[1], '. ' ) );
}
//.. interpro 
_m( 'interpro', 1 );
foreach ( _json_load( FN_INTERPRO_JSON ) as $key => $val ) {
	_do( trim( $val[1], '. ' ) );
}

//.. cath
_m( 'CATH', 1 );
foreach ( gzfile( DN_FDATA. '/dbid/cath-b-newest-names.gz' ) as $line ) {
	$n = explode( ' ', trim( $line ), 2 )[1];
	if ( ! $n ) continue;
	$n = _reg_rep( $n, [
		 '/;.*/' => '' ,
		 '/, .*/' => ''
	]);
	_do( $n );
}

//.. bird
_m( 'bird', 1 );
foreach ( _json_load( DN_PREP. '/prd/prd_info.json.gz' ) as $c ) {
	foreach ( explode( ', ', $c[0] ) as $s ) {
		_do( $s ); 
	}
	_do( $c[1] );
}
foreach ( glob( DN_DATA. '/bird/json/*.json.gz' ) as $fn ) {
	foreach ( (array)_json_load2( $fn )->pdbx_reference_molecule_synonyms as $c ) {
		_do( $c->name );
	}
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
	_do( $w );
}

//.. met
_line( 'met' );
foreach ( _tsv_load2( DN_EDIT. '/met_annot.tsv' ) as $k1 => $v1 ) {
	foreach ( $v1 as $k2 => $v2 ) {
		if ( $k1 != 'wikipe' && $k2 != 'wikipe' ) continue;
		if ( $v2 == 'x' ) continue;
		foreach ( explode( '|' , $v2 ) as $s )
			_regist_annot_term( $s, $s );
	}
}
//.. doc
_line( 'doc' );
foreach ( _json_load2( DN_PREP. '/doc.json.gz' ) as $i => $c ) {
	if ( ! $c->wikipe ) continue;
	foreach ( is_array( $c->wikipe ) ? $c->wikipe : [ $c->wikipe ] as $w ) {
		_do( $w );
		_m( $w );
	}
}


//. save
$regist;
_regist_save();

//. function _do
function _do( $key ) {
	if ( strlen( $key ) < 4 ) return;
	$term = $key;
	if ( REPLACE[ strtoupper( $term ) ] ) return; //- もうレジストされているはず

	if ( _regist_term( $key, $term ) ) {
		_cnt( 'direct' );
		return;
	}
	if ( _regist_term( $key, ucfirst( $term ) ) ) {
		_cnt( 'direct ucfirst' );
		return;
	}
	if ( _regist_term( $key, $key, true ) ) {
		_cnt( 'direct nocase' );
		return;
	}

	foreach ( _reps_wikipe_terms() as $rep ) {
		$term = trim( _reg_rep( strtolower( $term ), $rep ), ' .,' );
		if ( strlen( $term ) < 4 ) break;
		if ( REPLACE[ strtoupper( $term ) ] ) break;
		if ( _regist_term( $term, $term ) ) {
			_cnt( 'rep' );
			continue;
		}
		if ( _regist_term( $term, ucfirst( $term ) ) ) {
 			_cnt( 'rep ucfirst' );
			continue;
		}
		if ( _regist_term( $term, $term, true ) )
			_cnt( 'rep nocase' ); 
//		$term_list[] = $term;
	}
//	_regist( $key, $term_list );
}


