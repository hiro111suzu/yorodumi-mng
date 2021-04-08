<?php 
require_once dirname(__FILE__) . '/commonlib.php';

define( 'DN_JSON', DN_PREP. '/unp2inchikey' );
_mkdir( DN_JSON );

//. data json作成
foreach ( _idlist( 'pdb' ) as $id ) {
	$fn_out = DN_JSON. '$id.json';
	$fn_json = _fn( 'pdb_json', $id );
	$fn_plus = _fn( 'pdb_json', $id );
	if (
		_newer( $fn_out, $fn_json ) &&
		_newer( $fn_out, $fn_plus )
	) continue;
	
	//.. proc
	$out = [];
	$json = _json_load2( _fn( 'pdb_json', $pdb_id ) );

	foreach
	
}

