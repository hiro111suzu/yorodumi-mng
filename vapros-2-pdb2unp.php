<?php
require_once( 'vapros-common.php' );

//. unp2pdb-chain
$data = [];
foreach ( _idlist( 'pdb' ) as $pdb_id ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$json = _json_load2( _fn( 'pdb_json', $pdb_id ) );
	$plus = _json_load2( _fn( 'pdb_plus', $pdb_id ) );

	$asym2chain = (array)$json->_yorodumi->id_asym2chain;

	//- ent -> chain
	$ent2chain = [];
	foreach ( (array)$json->entity_poly as $c ) {
		$ent2chain[ $c->entity_id ] = explode( ',', $c->pdbx_strand_id );
	}

	//- ent2unp
	$ent2unp = [];
	foreach ([
		(array)$json->struct_ref ,
		(array)$plus->struct_ref 
	] as $j ) {
		foreach ( $j as $c ) {
			if (
				$c->db_name != 'UNP' ||
				$c->pdbx_db_accession == '' ||
				$c->entity_id  == ''
			) continue;
			$ent2unp[ $c->entity_id ][] = $c->pdbx_db_accession;
		}
	}			

	//- $data 
	foreach ( (array)$ent2chain as $ent_id => $chain_id_list ) {
		foreach ( $chain_id_list as $chain_id )  {
			$data[ $pdb_id ][ $chain_id ] = $ent2unp[ $ent_id ] ?: [];
		}
	}
}
_json_save( FN_PDB2UNP, $data );


