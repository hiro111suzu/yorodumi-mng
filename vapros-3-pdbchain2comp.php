<?php
require_once( 'vapros-common.php' );

//. unp2pdb-chain
$data = [];
foreach ( _idlist( 'pdb' ) as $pdb_id ) {
	if ( _count( 'pdb', 0 ) ) break;
	$json = _json_load2( _fn( 'pdb_json', $pdb_id ) );

	//- site2comp
	$site2comp = [];
	foreach ( (array)$json->struct_site as $c ) {
		$det = strtoupper( $c->details );
		if ( ! _instr( 'BINDING SITE FOR ', $det ) ) continue;
		list( $type, $comp ) = explode( ' ', strtr( $det, [
			'BINDING SITE FOR ' => ''
		]), 3 );
		if ( in_array( $type, [
			'RESIDUE',
			'RESIDUES',
			'MONO-SACCHARIDE' ,
		]) && $comp ) {
			$site2comp[ $c->id ] = $comp;
		}
	}

	$asym2chain = (array)$json->_yorodumi->id_asym2chain;

	//- data
	$chain2comp = [];
	foreach ( (array)$json->struct_site_gen as $c ) {
		$chain_id = $asym2chain[ $c->label_asym_id ];
		if ( $chain_id == '' ) continue;
		$chain2comp[ $chain_id ][ $site2comp[ $c->site_id ] ] = true;
	}

	foreach ( $chain2comp as $chain => $comp ) {
		$data[ $pdb_id ][ $chain ] = array_keys( $comp ); 
	}
}
_json_save( FN_PDBCHAIN2COMP, $data );


