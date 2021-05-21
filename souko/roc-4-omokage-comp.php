<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );
define( 'SCDN', WDN . '/scores' );

//. 


$pairs = _json_load( WDN . '/pairs.json' );
_m( 'ペアの数:' . count( $pairs ) );

$tests = [
	'30' => [ '30' ] ,
	'50' => [ '50' ] ,
	'out' => [ 'out' ] ,
	'pca' => [ 'pca' ] ,
	'all' => [ '30', '50', 'out', 'pca'  ]
];

foreach ( $tests as $name => $type ) {
	_line( $name );
	$sc = [];
	foreach ( $pairs as $ids => $a ) {
		$i = explode( ',', $ids );
		$sc[ $ids ] = _score( $i[0], $i[1], $type );
	}
	_comp_save( SCDN . "/omo_$name.json", $sc );
}


//. func

function _score( $id1, $id2, $types ) {
	$prof = [];
	foreach ( $types as $t ) {
		$prof1[ $t ] = _file( _fn( "prof$t", $id1 ) );
		$prof2[ $t ] = _file( _fn( "prof$t", $id2 ) );
	}

	//- each profile
	$sc = [];
	foreach ( $prof1 as $t => $prof ) {
		$sum = 0;
		$wsum = 0;
		$cnt = count( $prof );
		for ( $i = round( $cnt * 0.02 ); $i < $cnt; ++ $i ) {
			$sum  += pow( $prof[ $i ] - $prof2[ $t ][ $i ] , 2 );
			$wsum += pow( $prof[ $i ] + $prof2[ $t ][ $i ] , 2 );
		}
		$sc[ $t ] = 1 - sqrt( $sum / $wsum );
	}

	//- merge
	$sum = 0;
	foreach ( $sc as $s )
		$sum += pow( $s, 2 );
	return sqrt( $sum / count( $sc ) );
}

