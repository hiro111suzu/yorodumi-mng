<?php
require_once( "commonlib.php" );

define( 'DN_MET', DN_PREP. '/met' );
_mkdir( DN_MET );
_mkdir( DN_MET. '/emdb' );
_mkdir( DN_MET. '/pdb' );
_mkdir( DN_MET. '/sas' );

define( 'MET_SYN', _tsv_load2( DN_EDIT. '/met_syn.tsv' ) );

//. func
//.. _ng_name
function _ng_name( $str ) {
	return in_array( strtolower( $str ), [ 'na', 'n-a', 'n/a', 'none', 'null', 'other', 'and' ] );
}

//.. _add_data
function _add_data( $mcateg, $name, $for, $met ) {
	global $data, $year;
	$name = trim( $name );
	if ( !$name ) return;
	if ( _ng_name( $name ) ) return;
	$name = _reg_rep( $name, MET_SYN[ $mcateg ] );
	$key = "$mcateg:" . strtolower( $name );
	$data[ $key ][ 'met' ][] = $met;
	$data[ $key ][ 'name' ][] = $name;
	foreach ( is_string( $for ) ? [ $for ] : $for as $f ) {
		$data[ $key ][ 'for' ][] = $f;
	}
	$data[ $key ]['year'] = $year;
}

//.. _clean_data
function _clean_data(){
	global $data;
	foreach ( $data as $k => $v ) {
		foreach ( $v as $k2 => $v2 ) {
			if ( $k2 == 'year' ) continue;
			$data[$k][$k2] = array_values( _uniqfilt( $v2 ) );
		}
	}
}
