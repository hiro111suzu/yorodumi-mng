download PDBe-chemimg
==========

<?php
require_once( "commonlib.php" );
$imgdn = "$_ddn/pdbechemimg";

//. loop
$limit = 14;
$url = 'http://www.ebi.ac.uk/pdbe-srv/pdbechem/image/showNew?size=100&code=';

$ids = [];
foreach ( _tsv_load( "$imgdn/count.tsv" ) as $id => $num ) {
	if ( $num < $limit ) continue;
	$imgfn = "$imgdn/$id.gif";
	if ( file_exists( $imgfn ) ) continue;
	copy( "$url$id", $imgfn );
	_checkf( $imgfn, $id );
//	_m( "download $id" );
	sleep( 30 );
//	_m( "$id: $num" );
}

