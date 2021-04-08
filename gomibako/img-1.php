img1
<?php

//. init
require_once( "commonlib.php" );
$vqdn  = '/novdisk2/db/omopdb/vq';
$atom = _getcrd( "$vqdn/1xxx-d-50.pdb" );

$fn = "../test/pdbimg/1xxx-d.csv";
//- 座標csv作成
$s = "x,y,z\n";
foreach ( $atom as $a )
	$s .= $a[ 'x' ] .','. $a[ 'y' ] .','. $a[ 'z' ] . "\n" ;
file_put_contents( $fn, $s );
die();


$profdn  = '/novdisk2/db/omopdb/prof';
$errdn = '/novdisk2/db/omopdb/error';
$dbfn = '/novdisk2/db/omopdb/profdb.sqlite';
//_initlog( "pdb-9: make vq pdb" );
//define( BLIST, 'blacklist_make_pdb_vq' );

define( 'FLTWD', 0.3 );

//- R pcaコマンド
$rcmd = <<<EOF
pc<-princomp(read.csv("<infn>"),cor=FALSE)
sink("<outfn>")
pc
sink()
EOF;

//. main
//die();
foreach ( scandir( $vqdn ) as $fn ) {
	if ( substr( $fn, -6 ) != '30.pdb' ) continue;
	$a = explode( '-', $fn );
	$id = $a[0] . '-' . $a[1];
	$vq30fn = "$vqdn/$fn";
	$vq50fn = strtr( $vq30fn, [ '30.pdb' => '50.pdb' ] );

	$proffn = "$profdn/$id.txt";

	if ( _newer( $proffn, $vq30fn ) ) continue;
	if ( _newer( $proffn, $vq50fn ) ) continue;

	//.. 実行
	$data = [];

	//... get atom
	$atom30 = _getcrd( $vq30fn );
	$atom   = _getcrd( $vq50fn );
	if ( count( $atom30 ) != 30 or count( $atom ) != 50 ) {
		_m( "$id: bad data", -1 );
		continue;
	}

	//... vq30, 50
	//- vq30
	$data[] = _getprof( $atom30 );

	//- vq50
	$data[] = _getprof( $atom );
	
	//... outer
	//- 重心
	$cg = [];
	foreach ( $atom as $a ) {
		$cg[ 'x' ] += $a[ 'x' ];
		$cg[ 'y' ] += $a[ 'y' ];
		$cg[ 'z' ] += $a[ 'z' ];
	}
	$cg[ 'x' ] /= 50;
	$cg[ 'y' ] /= 50;
	$cg[ 'z' ] /= 50;

	//- 重心からの距離
	$dist = [];
	foreach ( $atom as $num => $a ) {
		$dist[ $num ] =sqrt( 
			pow( $cg[ 'x' ] - $a[ 'x' ], 2 ) +
			pow( $cg[ 'y' ] - $a[ 'y' ], 2 ) +
			pow( $cg[ 'z' ] - $a[ 'z' ], 2 )
		);
	}

	//- 遠い順
	arsort( $dist ); //- 「値」を基準に降順ソート
	$atom2 = [];
	foreach ( $dist as $num => $v )
		$atom2[] = $atom[ $num ];

	$data[] = _getprof( $atom2, 25 );

	//... pca
	$fn1 = _tempfn( 'csv' );
	$fn2 = _tempfn( 'txt' );

	//- 座標csv作成
	$s = "x,y,z\n";
	foreach ( $atom as $a )
		$s .= $a[ 'x' ] .','. $a[ 'y' ] .','. $a[ 'z' ] . "\n" ;
	file_put_contents( $fn1, $s );

	//- pca計算
	_Rrun( strtr( $rcmd, array( '<infn>' => $fn1, '<outfn>' => $fn2 ) ) );
	$a = _file( $fn2 );
	$out = preg_split( '/ +/', trim( $a[4] ) );

	_del( $fn1, $fn2 );

	//- data
	$data[] = $out[0];
	$data[] = $out[1];
	$data[] = $out[2];

	//.. save
	file_put_contents( $proffn, implode( "\n", $data ) );
	_m( "$id - {$out[0]}, {$out[1]}, {$out[2]}" );

//	$cnt ++;
//	if ( $cnt > 5 ) break;
}
//_m( $cnt );

//. func
//.. _data
function _data( $in ) {
	global $data;
	if ( is_array( $in ) )
		$in = implode( ' ', $in );
	$data = trim( "$data,\"$in\"", ',' );
}

//.. _get
function _get( $id, $col ) {
	global $db;
	$res = $db
		->query( "SELECT $col FROM main WHERE id = '$id'" )
		->fetchAll( PDO::FETCH_ASSOC )
	;
	return $res[0][ $col ];
}

//.. _getcrd
function _getcrd( $fn ) {
	$ret = [];
	foreach ( _file( $fn )  as $n => $l ) {
		$atom[ $n ][ 'x' ] = substr( $l, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $l, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $l, 46, 8 );
	}
	return $atom;
}

//.. _getprof
function _getprof( $atom, $cnt = '' ) {
	$n = _nn( $cnt, count( $atom ) );

	//- 全組み合わせ距離
	$prof = [];
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = sqrt(
				pow( $atom[ $a1 ][ 'x' ] - $atom[ $a2 ][ 'x' ], 2 ) +
				pow( $atom[ $a1 ][ 'y' ] - $atom[ $a2 ][ 'y' ], 2 ) +
				pow( $atom[ $a1 ][ 'z' ] - $atom[ $a2 ][ 'z' ], 2 )
			);
		}
	}
	sort( $prof );

	//- 微分もどき
	$out = [];
	$fv = floor( count( $prof ) * FLTWD );
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$out[ $i ] += $d - $v;
		}
	}
	return implode( ' ', $out );
}

