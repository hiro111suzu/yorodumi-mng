<?php
require_once( "uniprot-common.php" );
$evc_list = [];
foreach ( _idloop( 'unp_json' ) as $fn_in ) {
	if ( _count( 5000, 0 ) ) break;
	$json = _json_load2( $fn_in )->evd ;
	foreach ( (array)$json as $ar ) {
		_cnt( $ar[0] );
		$evc_list[ $ar[0] ] = true;
	}
}
_cnt();
$json = [];
foreach ( array_keys( $evc_list ) as $evc ) {
	$u = "https://www.ebi.ac.uk/QuickGO/services/ontology/eco/terms/$evc/";
	$name = json_decode( file_get_contents( $u ) )->results[0]->name;
	$json[ $evc ] = $name;
	_m( "$evc: $name" );
}
_comp_save( DN_DATA. '/pdb/evcode.json', $json );
//https://www.ebi.ac.uk/QuickGO/services/ontology/eco/terms/ECO:0000255/
//https//www.ebi.ac.uk/QuickGO/services/ontology/eco/terms/ECO:0000213/)

	
