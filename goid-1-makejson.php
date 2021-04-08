<?php
require_once( "commonlib.php" );
$datafn = DN_DATA . '/omo/goid.json.gz';
$data = [];

//. EMDB
_line( 'EMDB' );
foreach ( glob( _fn( 'emdb_old_json', '*' ) ) as $pn ) {
	if ( _count( 1000, 0 ) ) break;
	$id = 'e' . basename( $pn, '.json.gz' );
	preg_match_all( '/"GO:([0-9]+)"/', _gzload( $pn ), $a );
	if ( count( $a[1] ) > 0 ) {
		$data[ $id ] = array_unique( $a[1] );
		_m( "$id :" . _imp( $data[ $id ] ) );
	}
}

//. PDB
_line( 'PDB' );
foreach ( glob( _fn( 'pdb_json', '*' ) ) as $pn ) {
	if ( _count( 1000, 00 ) ) break;
	$id = basename( $pn, '.json.gz' );
	$j = _json_load2( $pn );
	$go = [];
	foreach ( (array)$j->gene_ontology as $g )
		$go[] = $g->goid;
	if ( count( $go ) > 0 )
		$data[ $id ] = array_unique( $go );
//	_m( "$id :" . _imp( $data[ $id ] ) );
}

_json_save( $datafn, $data );
