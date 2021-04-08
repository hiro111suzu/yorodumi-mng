<?php
require_once( "wikipe-common.php" );
//. main 
$out = [];
foreach ( array_merge(
	glob( DN_WIKIPE. '/chem/*.json.gz' ) ,
	glob( DN_WIKIPE. '/taxo/*.json.gz' ) ,
	glob( DN_WIKIPE. '/misc/*.json.gz' )
) as $fn ) {
	if ( _count( 500, 0 ) ) _pause( 'pause' );
	$json = _json_load2( $fn );
	$time_json = filemtime( $fn );
	$fn_out = _fn_wkp_json( $json->et );
	$out = [
		'et' => $json->et ,
		'ea' => $json->ea ,
		'jt' => $json->jt ,
		'ja' => $json->ja ,
	];

	if ( file_exists( $fn_out ) ) {
		$j = _json_load( $fn_out ) ;
		if ( $out != $j )  {
/*
			_line( '同じタイトルで内容が違う', $json->et );
			foreach ( $out as $k => $v ) {
				if ( $v != $j[ $k ] ) {
					_m( $k, 'green' );
					_m( $j[$k] );
					_m( "=>", 'red' );
					_m( $v ) ;
				}
			}
*/
			if ( $time_json < filemtime( $fn_out ) )
				continue;
		}
	}
	_json_save( $fn_out, $out );
	touch( $fn_out, $time_json );
}


