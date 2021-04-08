<?php 
require_once dirname(__FILE__) . '/commonlib.php';

//$metname
//'X-RAY DIFFRACTION'

define( 'RES_SEG', [ 3, 4, 5 ] );

$data = [];
foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 5000 );
	$json = _json_load2( $fn );
	if ( substr( $json->rdate, 0, 4 ) != 2018 ) continue;
	if ( in_array( 'X-RAY DIFFRACTION', $json->method  ) )
		++ $data[ 'x-ray' ];
	++ $data['all'];
}
_kvtable( $data );

//die();
