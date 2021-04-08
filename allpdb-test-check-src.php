<?php
require_once( "commonlib.php" );
//. main loop
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );

	$json = _json_load2( $fn );
	$cnt = [
		'poly' => count( $json->entity_poly ) ,
		'gen'  => count( $json->entity_src_gen ) ,
		'nat'  => count( $json->entity_src_nat ) ,
		'syn'  => count( $json->pdbx_entity_src_syn )
	];
//	_kvtable( $cnt, $id );
	extract( $cnt );
//	_m( "$id: $poly" );
	if ( $poly >= $gen + $nat + $syn ) continue;
	_kvtable( $cnt, "数が合わない $id" );

}

