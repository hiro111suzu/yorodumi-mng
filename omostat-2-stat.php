<?php

//. init
require_once( "commonlib.php" );
$dn =  '../omopdb/stat';

$dbfn = '/work/sqlitefiles/profdb_s.sqlite';
if ( ! file_exists( $dbfn ) )
	die( 'DBファイルがない' );
$db = new PDO( "sqlite:$dbfn", '', '' );

$ids1 = _file( "$dn/ids1.txt" );
$ids2 = _file( "$dn/ids2.txt" );

_m( count( $ids1 ) );
_m( count( $ids2 ) );

$ign = [];
$count = [];

$outfn = "$dn/results.txt";
$out2fn = "$dn/results2.txt";
$outofn = "$dn/resultso.txt";
$out = '';
$out2 = '';
$outo = '';

define( 'T', "\t" );
define( 'N', "\n" );

_count();
//. main
foreach ( $ids1 as $n => $id1 ) {
	if ( _count( 100, 0 ) ) break;
	$r = _prof( $id1 );
	$prof1 = $r[ 'data' ];
	$pca1  = $r[ 'pca' ];

	$id2 = $ids2[ $n ];
	$r = _prof( $id2 );
	$prof2 = $r[ 'data' ];
	$pca2  = $r[ 'pca' ];

	if ( $ign == [] ) {
		foreach ( $prof1 as $n => $v ) {
			$count[ $n ] = count( $v );
			$ign[ $n ] = round( $count[ $n ] * 0.02 );
		}
		$pnum = count( $prof1 );
	}

	//.. pca
	$a = [];
	foreach ( [ 1, 2, 3 ] as $n ) {
		$a[] = $pca1[ $n ] / $pca2[ $n ];
		$a[] = $pca2[ $n ] / $pca1[ $n ];
	}
	$pca = min( $a );

	//.. score
	$sc = [];
	foreach ( $prof1 as $n => $p ) {
		$sum  = 0;
		$wsum = 0;
		for ( $i = $ign[ $n ]; $i < $count[ $n ]; ++ $i ) {
			$sum  += pow( $prof2[ $n ][ $i ] - $p[ $i ] , 2 );
			$wsum += pow( $prof2[ $n ][ $i ] + $p[ $i ] , 2 );
		}
		$sc[ $n ] = 1 - sqrt( $sum / $wsum );
	}

	$sum = 0;
	foreach ( $sc as $s )
		$sum += pow( $s, 2 );
	$score = sqrt( $sum / $pnum ); 

	$out .= "$score\t$pca\n";
//	$out2 .= "$score\t$pca\t$id1\t$id2\n";
	$l = $score .T. $pca .T. $id1 .T. $id2 .N;
	$out2 .= $l;
	if ( $pca < 0.8 and $score > 0.95 ) {
		_m( $l );
		$outo .= $l;
	}
//	_m( "sc:$score \t pca:$pca" );
}

//. 
file_put_contents( $outfn, $out );
file_put_contents( $out2fn, $out2 );
file_put_contents( $outofn, $outo );

//. 
function _prof( $id ) {
	global $db;
	$n = $db->query( "SELECT data,pca1,pca2,pca3  FROM main WHERE id = \"$id\" " );
//	_sqlite_chk_mng( $db );
	$res = $n->fetch( PDO::FETCH_ASSOC );

	if (
		$res[ 'data' ] == '' or
		$res[ 'pca1' ] == '' or
		$res[ 'pca2' ] == '' or
		$res[ 'pca3' ] == '' 
	) {
		die( "$id: no data" );
	}

	$ret[ 'pca' ] = [
		1 => $res[ 'pca1' ] ,
		2 => $res[ 'pca2' ] ,
		3 => $res[ 'pca3' ]
	];

	foreach ( explode( '|', $res[ 'data' ] ) as $s )
		$ret[ 'data' ][] = explode( ',', $s );
	return $ret;
}

