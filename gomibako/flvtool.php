<?php
die( "このスクリプトは用済み" );
require_once( "commonlib.php" );
/*
foreach ( $emdbidlist as $id ) {
	$dn = "$_ddn/jdata/$id";
	$dirs = scandir( $dn );
	if ( count( $dirs ) > 0 ) foreach ( $dirs as $fn ) {
		if ( substr( $fn, -4 ) != '.flv' ) continue;
		echo "$id: $fn\n";
		
		exec( "flvtool2 -U $dn/$fn");

	}
}
*/
foreach ( $pdbidlist as $id ) {
	$dn = "$_ddn/pdbdata/$id";
	$dirs = scandir( $dn );
	if ( count( $dirs ) > 0 ) foreach ( $dirs as $fn ) {
		if ( substr( $fn, -4 ) != '.flv' ) continue;
		echo "$id: $fn\n";
		
		exec( "flvtool2 -U $dn/$fn");

	}
}

