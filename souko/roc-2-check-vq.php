<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );
//. 
$errcnt = 0;
foreach ( _json_load( WDN . '/allids.json' ) as $id ) {
	foreach ( [
		'prof30' => 434,
		'prof50' => 1224,
		'profout' => 299,
		'profpca' => 3
	] as $type => $num ) {
		if ( ! file_exists( _fn( $type, $id ) ) ) {
			_m( "ファイルがない!: $id - $type" );
			++ $errcnt;
		} else {
			$n = count( _file( _fn( $type, $id ) ) );
			if ( $n != $num ) 
				_m( "$id-$type\t$n != $num" );
			else
				_m();
		}
	}
}
_m( "エラーの数: $errcnt" );

/*
_comp_save( WDN . '/id2cate.json', $id2cate );
_comp_save( WDN . '/cate2id.json', $cate2id );
_comp_save( WDN . '/pairs.json', $pairs );
_comp_save( WDN . '/allids.json', $allids );
*/
//print_r( $pairs );

