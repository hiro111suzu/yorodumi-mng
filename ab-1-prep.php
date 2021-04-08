<?php
require_once( "commonlib.php" );
define( 'DN_ABWORK', DN_PREP. '/ab_work' );
_mkdir( DN_ABWORK );

$_filenames += [
	'ab_work'     => DN_ABWORK. '/<id>.json.gz' ,
];


//. main loop
foreach ( _idloop( 'pdb_json' ) as $fn_in ) {
	_count( 5000 ); 
	$id = _fn2id( $fn_in );
	$fn_out = _fn( 'ab_work', $id );

	if ( FLG_REDO )
		_del( $fn_out );

	if ( _newer( $fn_out, $fn_in ) ) continue;
	
	$json = _json_load2( $fn_in );

	//- name: entity->pdbx_description
	//- seq: entity_poly->pdbx_seq_one_letter_code_can
	//- src: 
	//- 	entity_src_gen	pdbx_gene_src_scientific_name
	//- 	entity_src_nat	pdbx_organism_scientific
	//- 	pdbx_entity_src_syn	organism_scientific

	$seq = $name = $src = $cid = [];
	//- seq
	foreach ( (array)$json->entity_poly as $c ) {
		if ( $c->type != 'polypeptide(L)' ) continue;
		$seq[ $c->entity_id ] = preg_replace(
			'/[^A-Z]/', '', $c->pdbx_seq_one_letter_code_can
		);
		$cid[ $c->entity_id ] = explode( ',', $c->pdbx_strand_id );
	}

	//- name
	foreach ( (array)$json->entity as $c ) {
		$name[ $c->id ] = $c->pdbx_description;
	}

	//- src
	foreach ([
		'entity_src_gen'		=> 'pdbx_gene_src_scientific_name' ,
		'entity_src_nat'		=> 'pdbx_organism_scientific' ,
		'pdbx_entity_src_syn'	=> 'organism_scientific' ,
	] as $cat => $item ) {
		foreach ( (array)$json->$cat as $c ) {
			$src[ $c->entity_id ] = $c->$item;
		}
	}
	
	//- dat
	$data = [];
	foreach ( $seq as $eid => $s  ) {
		$data[ $eid ] = [
			'seq' => $s ,
			'name' => $name[ $eid ] ,
			'src' => $src[ $eid ] ,
			'cid' => $cid[ $eid ] ,
		];
	}
//	_pause( $data );
	_json_save( $fn_out, $data );
}
_delobs_pdb( 'ab_work' );
