<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );


//. 
$datalist = [];
$cate2id = [];
$id2cate = [];
$allids = [];

foreach ( _file( WDN . '/datalist.txt' ) as $line ) {
	$a = explode( ' ', $line );
	$id = _id2did( substr( $a[0], -4 )  );
	$cate = trim( $a[1] );
/*
	$datalist = [
		 'id'	=> $id ,
		 'cate'	=> $cate
	];
*/
	$id2cate[ $id ] = $cate;
	$cate2id[ $cate ][] = $id;
	$allids[] = $id;
}

$cnt = [];
$pairs = [];
foreach ( [
	'ribo' => [ '70s', '80s' ] ,
	'comp' => [ '30s', '50s' ] ,
	'chap' => [ 'c1', 'c2' ]
] as $gp => $cates ) {
	 $ids = array_merge( $cate2id[ $cates[0] ], $cate2id[ $cates[1] ] );
//	 $pairs[ $gp ] = [];
	 foreach ( $ids as $id1 ) foreach ( $ids as $id2 ) {
	 	if ( $id1 == $id2 ) continue;
	 	if ( $pairs[ "$id2,$id1" ] != '' ) continue;
	 	$pairs[ "$id1,$id2" ] = [
	 		'grp' => $gp ,
	 		'same' => $id2cate[ $id1 ] == $id2cate[ $id2 ]
	 	];
	 	++ $cnt[ $gp ][ $id2cate[ $id1 ] == $id2cate[ $id2 ] ? 1 : 0 ];
	 }
}
print_r( $cnt );

_comp_save( WDN . '/id2cate.json', $id2cate );
_comp_save( WDN . '/cate2id.json', $cate2id );
_comp_save( WDN . '/pairs.json', $pairs );
_comp_save( WDN . '/allids.json', $allids );

//print_r( $pairs );

