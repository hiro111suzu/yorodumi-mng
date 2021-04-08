<?php
require( "commonlib.php" );
$data = [];
foreach ( $emdbidlist as $id ) {
	$xml = simplexml_load_file( _fn( 'emdb_xml_orig', $id ) );
	if ( count( $xml->deposition->obsoleteList ) > 0 ) {
		_line( "$id:" . print_r( $xml->deposition->obsoleteList, true ) );
	}
	
	

}