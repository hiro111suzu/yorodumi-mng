<?php
//. init
//.. common?
include "omo-common.php";
//$smalldb = _data_load( "$_ddn/db-small.data" );

//. init
$data = _data_load( "$omolistdn/idlist.data" );

$ar = [
	'70s-70s' ,
	'70s-80s' ,
	'80s-80s' ,

	'c1-c1' ,
	'c1-c2' ,
	'c2-c2' ,

	'30s-30s' ,
	'30s-50s' ,
	'50s-50s' ,
	'c1-70s' ,
	'c2-80s'
];

$comp = [];
$comp[ 'all' ] = [];

//. 
foreach ( $ar as $name ) {
	$c = explode( '-', $name );
	foreach ( $data[ $c[0] ] as $id0 ) foreach ( $data[ $c[1] ] as $id1 ) {
		if ( $id0 == $id1 ) continue;
		if ( in_array( "$id1|$id0", $comp[ 'all' ] ) ) continue;
		$s ="$id0|$id1";
		$comp[ $name ][] = $s;
		$comp[ 'all' ][] = $s;

		// Ž—‚½‚à‚Ì”äŠr‚©AŽ—‚Ä‚È‚¢‚à‚Ì”äŠr‚©H
		$sim = ( $c[0] == $c[1] ) ? 'sim-sim' : 'sim-nonsim';
		$comp[ $sim ][] = $s;

		// Ž—‚Ä‚é‚È‚ç “¯‚¶DB‚©Aˆá‚¤DB‚©
		if ( $sim == 'sim-sim' ) {
			$db0 = _id2db( $id0 );
			$db1 = _id2db( $id1 );
			if ( $db0 != $db1 )
				$comp[ "db-emdb-pdb" ][] = $s;
			else {
				$comp[ "db-$db0" ][] = $s;
				$comp[ "db-samedb" ][] = $s;
			}
		}
		echo '.';
	}
}

$comp[ 'ribo-same' ] = array_merge( $comp[ '70s-70s' ], $comp[ '80s-80s' ] );
$comp[ 'sub-same'  ] = array_merge( $comp[ '30s-30s' ], $comp[ '50s-50s' ] );
$comp[ 'chap-same' ] = array_merge( $comp[ 'c1-c1' ]  , $comp[ 'c2-c2' ] );

_data_save( "$omolistdn/complist.data", $comp );
//print_r( $comp );
$out = [];
foreach ( $comp as $n => $c )
	$out[ $n ] = count( $c );
//	echo "$n: " .  . "\n";
ksort( $out );
print_r( $out );
