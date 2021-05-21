<?php
//. init
//.. common?
include "omo-common.php";
$smalldb = _data_load( "$_ddn/db-small.data" );
//$omolistdn = 'omo-list';

$ar = array_merge(
	scandir( "$omolistdn/ribosome" ) ,
	scandir( "$omolistdn/ribosome/70" ) ,
	scandir( "$omolistdn/ribosome/80" ) ,
	scandir( "$omolistdn/ribosome/no" ) ,
	scandir( "$omolistdn/protein" ) ,
	scandir( "$omolistdn/protein/1" ) ,
	scandir( "$omolistdn/protein/2" ) ,
	scandir( "$omolistdn/protein/2o" ) ,
	scandir( "$omolistdn/protein/no" )
);

$flist = array();

foreach ( $ar as $fn ) {
	if ( substr( $fn, 0, 1 ) == '.' ) continue;
	if ( strlen( $fn ) < 6 ) continue;
	$id = substr( $fn, 0, 4 );
	if ( in_array( $id, $flist ) )
		die( "$id: redundant data" );
	$flist[] = $id;
}

//. ファイルコピー

$catini = parse_ini_file( "$_ddn/categ.ini", true );
foreach ( $catini[ 'emdb' ] as $id => $cat) {
	if ( in_array( $id, $flist ) ) continue;
	if ( $cat == 'ribosome' ) {
		copy( _fn( '_snap', $id, 'ss2' ), "$omolistdn/ribosome/$id.jpg" );
		echo "$id: ribo\n";
	}
	if ( $cat == 'protein' ) {
		copy( _fn( '_snap', $id, 'ss2' ), "$omolistdn/protein/$id.jpg" );
		echo "$id: prot\n";
	}
}


foreach ( $catini[ 'emdb' ] as $id => $cat) {
	if ( in_array( $id, $flist ) ) continue;
	if ( $cat == 'ribosome' )
		_copyimg( $id, 'ribosome' );
	if ( $cat == 'protein' )
		_copyimg( $id, 'protein' );
}

foreach ( $catini[ 'pdb' ] as $id => $cat) {
	if ( in_array( $id, $flist ) ) continue;
	if ( $cat == 'ribosome' )
		_copyimg( $id, 'ribosome' );
	if ( $cat == 'protein' )
		_copyimg( $id, 'protein' );
}


function _copyimg( $id, $cat ) {
	global $pdbidlist, $_ddn, $omolistdn, $smalldb;
	if ( in_array( $id, $pdbidlist ) ) {
		$s = $smalldb[ "pdb-$id" ][ 'img' ];
		if ( $s != 'f' )
			$a = "_$s";
		$fn = "$_ddn/pdbimage_s/$id$a.jpg";
		if ( ! file_exists( $fn ) )
			die( "nofile - $fn" );
	} else {
		$fn = _fn( 'snap', $id, 'ss2' );
	}
	copy( $fn, "$omolistdn/$cat/$id.jpg" );
	echo "$id: $cat\n";
}

//. 
/*
foreach ( $ini[ 'ribosome' ] as $id )
	echo "$id\n";
*/