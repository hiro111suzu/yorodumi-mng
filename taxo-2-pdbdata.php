<?php
//. misc init
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );

$all = FLG_REDO ? [] : _json_load( FN_PDB_NAME2TAXID );
$namelist = _json_load( FN_PDB_ID2TAXNAME );

//. remove obs data

$is_pdbid = array_fill_keys( _idlist( 'pdb' ), true );
foreach ( $all as $id => $val ) {
	if ( $is_pdbid[ $id ] ) continue;
	_m( "$id: obsolete" );
	unset( $all[ $id ] );
}
foreach ( $namelist as $id => $val ) {
	if ( $is_pdbid[ $id ] ) continue;
	_m( "$id: obsolete" );
	unset( $namelist[ $id ] );
}
unset( $is_pdbid );

//. main loop
foreach ( _idlist( 'pdb' ) as $id ) {
	if ( _count( 'pdb', 0 ) ) break;
	$fn_json = _fn( 'pdb_json', $id );
	$time_json = filemtime( $fn_json );
	if ( $time_json == $all[$id]['t'] ) continue;

	//.. 由来のみ
	$json = _json_load2( $fn_json );
	$data = [];
	$names = [];
	$flg_name = true;
	foreach ( (array)$json->entity_src_gen as $c ) {
		_cnt( 'gen' );
		_do(
			$c->pdbx_gene_src_scientific_name ,
			$c->pdbx_gene_src_ncbi_taxonomy_id
		);
	}
	foreach ( (array)$json->entity_src_nat as $c ) {
		_cnt( 'nat' );
		_do(
			$c->pdbx_organism_scientific ,
			$c->pdbx_ncbi_taxonomy_id
		);
	}
	foreach ( (array)$json->pdbx_entity_src_syn as $c ) {
		_cnt( 'syn' );
		_do(
			$c->organism_scientific ,
			$c->ncbi_taxonomy_id
		);
	}
	
	//.. 発現ホスト
	foreach ( (array)$json->entity_src_gen as $c ) {
		_cnt( 'host' );
		$flg_name = false;
		_do(
			$c->pdbx_host_org_scientific_name ,
			$c->pdbx_host_org_ncbi_taxonomy_id
		);
	}

	//.. 集計
	$all[$id]['t'] = $time_json;
	$data = array_values( _uniqfilt( $data ) );
	if ( $data ) {
		$all[$id]['n'] = $data;
	}
	$namelist[ $id ] = array_values( _uniqfilt( $names ) );
}
_cnt();
_json_save( FN_PDB_NAME2TAXID, $all );
_json_save( FN_PDB_ID2TAXNAME, array_filter( $namelist ) );

//. リスト作成
$out = [];
foreach ( $all as $c ) foreach ( (array)$c['n'] as $n ) {
	list( $name, $id ) = explode( '|', $n );
	++ $out[ strtolower( $name ) ]['i'][ $id ?: '-' ];
	++ $out[ strtolower( $name ) ]['n'][ $name ];
}
_json_save( FN_LIST_PDB, $out );

