emdb xml => json

<?php
include "commonlib.php";


//. main

$data = [];
$nutag = [];

$numtag = [];
$strtag = [];

foreach ( glob( DN_EMDB_MR . '/structures/*/header/*.xml' ) as $xfn ) {
	$id = substr( basename( $xfn, '.xml' ), -4 );
	$x = simplexml_load_file( $xfn );
	_f( $x );
}

function _f( $xml ) {
	global $id;
	if ( count( $x = $xml->children() ) > 0 ) foreach ( $x as $n => $v ) {
		if ( count( $v->children() ) > 0 )
			_f( $v );
		else {
			$v = trim( $v );
			if ( $n != 'nominalDefocusMin' ) continue;
			if ( $v == '' or $v == 'na' or $v == 'NA' or $v == 'n/a' ) continue;
//			_m( 'hoge' );
			if ( is_numeric( (string)$v ) ) {
//				_m( 'numeric' );
				continue;
			}
			_m( "$id : $n : [$v]" );
		}
			
	}
}

