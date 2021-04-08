cifからjson

<?php
require_once( "prd-common.php" );

//. main
_count();
$info = [];
$chem = [];
foreach ( _idloop( 'prd_json' ) as $fn ) {
	$id = _fn2id( $fn );
	$json = _json_load2( $fn )->pdbx_reference_molecule[0];

	$info[ "PRD_$id" ] = [ _imp( $json->class,  $json->type ), $json->name ];
	if ( $json->chem_comp_id )
		$chem[ $json->chem_comp_id ] = "PRD_$id";
}
_comp_save( DN_PREP. '/prd/prd_info.json.gz', $info );
_comp_save( DN_PREP. '/prd/chemid2prdid.json.gz', $chem );
