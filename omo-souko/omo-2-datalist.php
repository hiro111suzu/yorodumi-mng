<?php
//. init
//.. common?
include "omo-common.php";
$smalldb = _data_load( "$_ddn/db-small.data" );

//. init

$data = array();
$idlist = array();

//. ribo / chap
$ar = array(
	'70s' => 'ribosome/70' ,
	'80s' => 'ribosome/80' ,
	'c1' => 'protein/1' ,
	'c2' => 'protein/2' 
);

//$splist = _splist();

foreach ( $ar as $name => $dn ) {
	foreach ( scandir( "$omolistdn/$dn" ) as $fn ) {
		if ( substr( $fn, 0, 1 ) == '.' ) continue;
		$id = substr( $fn, 0, 4 );

		//- リボソーム全体はPDBデータ無し（vqが作れないから）
		if ( $name == '70s' or $name == '80s' ) {
			if ( in_array( $id, $pdbidlist ) )
				continue;
		}
		_d( $name, $id );
/*
		if ( count( $splist[ $id ] ) > 0 )
			$db = 'pdbsplit';
		else if ( in_array( $id, $emdbidlist ) )
			$db = 'emdb';
		else if ( in_array( $id, $pdbidlist ) )
			$db = 'pdb';
		else
			die( "$dn - $id: bad data!!!" );
*/
	}
}

//. 30s-50s
foreach( _file( "$omolistdn/30-50.txt" ) as $l ) {
	if ( substr( $l, 0, 1 ) == '.' ) {
		$name = substr( $l, 2 );
		continue;
	}
	if ( strlen( $l ) != 4 ) {
		echo "========== $l ==========";
		continue;
	}
	_d( $name . 's', $l );
}

// print_r( $data );
_data_save( "$omolistdn/idlist.data", $data );
_data_save( "$_ddn/omokage_categ.data", $data );
file_put_contents( "$omolistdn/idls.data", implode( "\n", $idlist ) );

//. 
function _d( $name, $id ) {
	global $data, $idlist;
	$did = _id2did( $id );
	$data[ $name ][] = $did;
	echo "$name: $did\n";
	$idlist[] = $did;
}


