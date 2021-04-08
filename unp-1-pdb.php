<?php
require_once( "unp-common.php" );
$pdbid2unpid = [];

//. pdbid2unpid
$flg_changed = false;
foreach ( _idloop( 'qinfo', 'PDBからUniprotID収集' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = _fn2id( $fn );
	$unp_ids = [];

	//.. qinfoから
	foreach ( (array)_json_load2( $fn )->ref as $ar ) {
		list( $d, $i ) = $ar;
		if ( $d != 'UNP' ) continue;
		$unp_ids[ $i ] = true;
	}

	//.. plus jsonから
	foreach ( (array)_json_load2( _fn( 'pdb_plus', $id ) )->struct_ref as $c ) {
		if ( $c->db_name != 'SIFTS_UNP' ) continue;
		if ( ! $c->pdbx_db_accession ) continue;
		$unp_ids[ $c->pdbx_db_accession ] = true;
	}
	if ( ! $unp_ids ) continue;
	$pdbid2unpid[ $id ] = array_keys( $unp_ids );
}

_comp_save( FN_PDBID2UNPID, $pdbid2unpid );


