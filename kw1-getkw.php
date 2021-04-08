<?php
require_once( "commonlib.php" );
$kws = [];
$smalldb = _json_load( DN_DATA . '/db-small.json.gz' );
$str = '';
//. emdb

foreach ( _idlist( 'emdb' ) as $id ) {
	_kw( explode( ' ', $smalldb[ "emdb-$id" ][ 'title' ] ) );
}

foreach ( _idlist( 'epdb' ) as $id ) {
	_kw( explode( ' ', $smalldb[ "pdb-$id" ][ 'title' ] ) );
	_kw( explode( ' ', $smalldb[ "pdb-$id" ][ 'desc' ] ) );
}

//- ‚Ü‚Æ‚ß
sort( $kws );
$kws = array_unique( $kws );
$kws = explode( '|', strtolower( implode( '|' , $kws ) ) );

$kws = preg_replace(
	[ '/[\.,\-]$/', '/[\(\):;]+/', '/-like$/' ] ,
	[ ''          , ''           , '' ] ,
	$kws
);

sort( $kws );
$kws = array_unique( $kws );

echo implode( "\n",$kws )
	. "\n"
	. count( $kws ) . ' words'
;
//. func
fnction _str( $s ) {
	global $str;
	if ( $s == '' ) return;
	$str .= strtolower( $s . "\n" );
	
}

function _kw( $s ) {
	global $kws;
	if ( $s == '' ) return;
	if ( is_array( $s ) )
		$kws = array_merge( $kws, $s );
	else
		$kws[] = $s;
}
