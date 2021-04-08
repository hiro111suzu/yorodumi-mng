<?php
require_once( 'vapros-common.php' );

$pdb2unp  = _json_load( FN_PDB2UNP );
$pdb2comp = _json_load( FN_PDBCHAIN2COMP );
$unp2reactome = _json_load( FN_UNP2REACTOME );

//. prep DB
$db_unp2pdb = new cls_sqlw([
	'fn' => 'vapros_unp2pdb', 
	'cols' => [
		'unp COLLATE NOCASE' ,
		'pdbchain' ,
	],
	'new' => true ,
	'indexcols' => [ 'unp', 'pdbchain' ] ,
]);

$db_pdb2comp = new cls_sqlw([
	'fn' => 'vapros_pdb2comp', 
	'cols' => [
		'pdb COLLATE NOCASE' ,
		'pdbchain' ,
		'comp' ,
		'reactome' ,
	],
	'new' => true ,
	'indexcols' => [ 'pdb', 'pdbchain' ] ,
]);

//. main
foreach ( $pdb2unp as $pdb_id => $c ) {
	if ( _count( 'pdb', 0 ) ) break;
	foreach ( $c as $chain_id => $unp_id_list ) {
		$reactome = [];
		foreach ( $unp_id_list as $unp_id ) {
			$db_unp2pdb->set([ $unp_id, $pdb_id. '-'. $chain_id ]);
			$reactome = array_merge( $reactome, (array)$unp2reactome[ $unp_id ] );
		}
		$db_pdb2comp->set([
			$pdb_id ,
			$pdb_id. '-'. $chain_id ,
			implode( ',', array_filter( (array)$pdb2comp[ $pdb_id ][ $chain_id ] ) ) ,
			implode( ',', _uniqfilt( $reactome ) ) ,
		]);
	}
}

$db_pdb2comp->end();
$db_unp2pdb->end();
