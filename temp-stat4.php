<?php 
require_once dirname(__FILE__) . '/commonlib.php';

//$metname
//'X-RAY DIFFRACTION'

define( 'RES_SEG', [ 3, 4, 5 ] );

$data = [];
foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 5000 );
	$json = _json_load2( $fn );
	$year = substr( $json->rdate, 0, 4 );
	if ( in_array( 'ELECTRON MICROSCOPY', $json->method  ) ) {


		foreach ( RES_SEG as $lim ) {
			if ( $lim <= $json->reso ) continue;
			++ $data[ $year ][ $lim ];
		}
		++ $data[ $year ][ 'EM all' ];
	}
	if (
		in_array( 'SOLUTION NMR', $json->method  ) ||
		in_array( 'SOLID-STATE NMR', $json->method  )
	) {
		++ $data[ $year ][ 'NMR' ];
	}
}

//die();
ksort( $data );
$cols = array_merge( RES_SEG, ['EM all', 'NMR']  );
$out = [ "year\t". implode( "\t", $cols ) ];
foreach ( $data as $year => $d ) {
	$o = [ $year ];
	foreach ( $cols as $col ) {
		$o[] = $d[ $col ];
	}
	$out[] = implode( "\t", $o );
}
$out = implode( "\n", $out );
file_put_contents( 'stat4.tsv', $out );
_m( $out );

