<?php
/* 
ccp4ヘッダとxml比較
*/

//. init
require_once( "commonlib.php" );

$srcbase = "$_rootdn/emdb-beta/structures";
$destbase = "$_rootdn/beta-test";

$idlist = array();
foreach ( scandir( $srcbase ) as $dn ) {
	if ( substr( $dn, 0, 3 ) != 'EMD' ) continue;
	$idlist[] = substr( $dn, -4 );
}

$result = array();

//. start main loop
foreach( $idlist as $id ) {
//	if ( $id == '1050' ) break;

	$jsonfn = "$destbase/$id/mapinfo.json";
	$xmlfn = "$srcbase/EMD-$id/header/emd-$id.xml";
	if ( ! file_exists( $jsonfn ) or ! file_exists( $xmlfn ) ) continue;

	$json = _json_load( $jsonfn );
	$x = simplexml_load_file( $xmlfn );
	$xml = $x->map;

//	echo "\n=== $id ===\n";
	
	//.. check
	$out = array();
	_c( 'NC',		'dimensions', 'numColumns' );
	_c( 'NR',		'dimensions', 'numRows' );
	_c( 'NS',		'dimensions', 'numSections' );
	_c( 'NCSTART',	'origin', 'originCol' );
	_c( 'NRSTART',	'origin', 'originRow' );
	_c( 'NSSTART',	'origin', 'originSec' );
	
	_c( 'X length',	'cell', 'cellA' );
	_c( 'Y length',	'cell', 'cellB' );
	_c( 'Z length',	'cell', 'cellC' );
	_c( 'APIX X', 	'pixelSpacing', 'pixelX' );
	_c( 'APIX Y', 	'pixelSpacing', 'pixelY' );
	_c( 'APIX Z', 	'pixelSpacing', 'pixelZ' );

	_c( 'Alpha',	'cell', 'cellAlpha' );
	_c( 'Beta',		'cell', 'cellBeta' );
	_c( 'Gamma',	'cell', 'cellGamma' );

	_c( 'DMIN',		'statistics', 'minimum' );
	_c( 'DMAX',		'statistics', 'maximum' );
	_c( 'DMEAN',	'statistics', 'average' );
	_c( 'DMIN',		'statistics', 'minimum' );


	if (
		$json[ 'APIX X' ] == 1 and
		$json[ 'APIX Y' ] == 1 and
		$json[ 'APIX Z' ] == 1
	)
		$out[] = "pixel spacing = 1 ???";

	if ( count( $out ) > 0 ) {
		echo "[$id]\n" . implode( "\n", $out ) . "\n";
		$result[ $id ] = $out;
	}
	
//	if ( $json[ 'NC' ] % 2 == 1 )
//		echo "ODD data!!!\n";
} // end of main loop (foreach)
_json_save( "$_ddn/betadata.json", $result );

die();

function _c( $name, $x1, $x2 ) {
	global $xml, $json, $result, $out;
	$j = $json[ $name ];
	$x = _nn( $xml->$x1->$x2, 'NULL' );
	if ( $j == $x ) return;

	//- stat
	if ( $x1 == 'statistics' ) {
		if ( (float)$j !== 0.0 ) {
			$dif =(float)$x / (float)$j;
			if ( 0.98 < $dif and $dif < 1.02 )  return;
		} else {
			if ( -0.02 < $x and $x < 0.02 ) return;
		}
	} else if ( $x1 == 'pixelSpacing' or $x1 == 'cell' ) {
		if ( (float)$j !== 0.0 ) {
			$dif =(float)$x / (float)$j;
			if ( 0.999 < $dif and $dif < 1.001 )  return;
		}
	}	
	$out[] = "$x: XML-map,$x1,$x2 != $j: CCP4-$name";

}

