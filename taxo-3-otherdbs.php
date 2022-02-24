<?php
//. misc init
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );
define( 'TSV_REP', _tsv_load2( FN_TSV_REP ) );

$sum = _json_load( FN_LIST_PDB );
$namelist = [];

//. uniprot
$data = [];
foreach ( _idloop( 'unp_json' ) as $fn ) {
	_count( 5000 );
	$j = _json_load2( $fn );
	_do( $j->org, $j->taxid );
}
//- uniprotは100倍
foreach ( array_filter( $data ) as $n ) {
	list( $name, $id ) = explode( '|', $n );
	$sum[ strtolower( $name ) ]['i'][ $id ?: '-' ] += 100;
	$sum[ strtolower( $name ) ]['n'][ $name ] += 100;
}

//. emdb

$all = [];
_count();
foreach ( _idloop( 'emdb_json' ) as $fn ) {
	$flg_name = true; //- IDと関連づけるフラグ
	$id = _fn2id( $fn );
	_count( 1000 );
	$data = [];
	$names = [];
	$json = _emdb_json3_rep( _json_load2( $fn ) )->sample;

	//.. nat
	foreach ( _uniqfilt( array_merge(
		_branch( $json, 'macromolecule[*]->natural_source' ) ,
		_branch( $json, 'supramolecule[*]->natural_source' )
	)) as $c ) {
//		_pause( $c );
		_do(
			_rep( 'emdb', $c->organism ) ,
			$c->{"organism.ncbi"}
		);
	}
	//- virus
	foreach ( (array)$json->supramolecule as $c ) {
		if ( ! $c->sci_species_name ) continue;
		_do(
			$c->sci_species_name,
			$c->{"sci_species_name.ncbi"}
		);
	}

	//.. recombinant
	$flg_name = false;
	foreach ( _uniqfilt( array_merge(
		_branch( $json, 'macromolecule[*]->recombinant_expression' ) ,
		_branch( $json, 'supramolecule[*]->recombinant_expression' )
	)) as $c ) {
		_do(
			_rep( 'emdb', $c->recombinant_organism ) ,
			$c->{"recombinant_organism.ncbi"}
		);
	}


/*
		//.. source
		foreach ( $v1 as $v2 ) {
			$flg_name = true;
			if ( $v2->natSpeciesName ) { //- これは無いみたい
				_do( 
					_rep( 'emdb', $v2->natSpeciesName ), 
					$v2->ncbiTaxId 
				);
				_cnt( 'nat' );
			}
			if ( $v2->sciSpeciesName ) {
				_do(
					_rep( 'emdb', $v2->sciSpeciesName ),
					$v2->ncbiTaxId 
				);
				_cnt( 'sci' );
			}

			//.. exp sys tem/host
			$flg_name = false;
			if ( is_object( $v2->engSource ) ) {
				_do(
					_rep( 'emdb', $k2->engSource->expSystem ),
					$k2->engSource->ncbiTaxId 
				);
				_cnt( 'exp' );
//				_m( 'exp: ' . _rep( 'emdb', $k2->engSource->expSystem ));

			}
			if ( is_object( $v2->natSource ) ) {
				_do(
					_rep( 'emdb', $k2->natSource->hostSpecies ),
					$k2->natSource->ncbiTaxId
				);
				_cnt( 'host' );

//				_m( 'host: '. _rep( 'emdb', $k2->natSource->hostSpecies ) );
	//			$k2->natSource->ncbiTaxId );
			}
		}
	}
*/

	$data = array_values( _uniqfilt( $data ) );
	if ( $data ) {
		$all[$id] = $data;
	}
//	_m( "$id:" );
//	_pause( array_values( _uniqfilt( $names ) ) ); 
	$namelist[ "e|$id" ] = array_values( _uniqfilt( $names ) );
}
_cnt();
foreach ( $all as $c ) foreach ( (array)$c as $n ) {
	list( $name, $id ) = explode( '|', $n );
	++ $sum[ strtolower( $name ) ]['i'][ $id ?: '-' ];
	++ $sum[ strtolower( $name ) ]['n'][ $name ];
}


//. sasbdb

_count();
$all = [];
_line( 'SASBDB' );
foreach ( _idlist( 'sasbdb' ) as $id ) {
	if ( _count( 'sas', 0 ) ) break;
	$fn_json = _fn( 'sas_json', $id );

	$flg_name = true;
	$json = _json_load2( $fn_json );
	$data = [];
	$names = [];
	foreach ( (array)$json->entity_src_gen as $c ) {
		_cnt( 'gen' );
		_do(
			$c->pdbx_gene_src_scientific_name ?:
			_rep( 'sas', $c->gene_src_common_name )   //- これしかない模様
			,
			$c->pdbx_gene_src_ncbi_taxonomy_id
		);
		_do(
			$c->pdbx_host_org_scientific_name ,
			$c->pdbx_host_org_ncbi_taxonomy_id
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
	$data = array_values( _uniqfilt( $data ) );
	if ( $data ) {
		$all[$id] = $data;
	}
	$namelist[ "s|$id" ] = array_values( _uniqfilt( $names ) );	
}
_cnt();
_json_save( FN_OTHERS_ID2TAXNAME, array_filter( $namelist ) );

//. リスト作成
foreach ( $all as $c ) foreach ( (array)$c as $n ) {
	list( $name, $id ) = explode( '|', $n );
	++ $sum[ strtolower( $name ) ]['i'][ $id ?: '-' ];
	++ $sum[ strtolower( $name ) ]['n'][ $name ];
}

//. virushost
//- 単純に名前を追加
foreach ( _json_load( FN_VIRUS2HOSTS ) as $c ) {
	foreach ( $c as $n ) {
		if ( ! $sum[ strtolower( $n ) ] )
			$sum[ strtolower( $n ) ]['n'][ $n ] = 1;
	}
}

//. save
_json_save( FN_LIST_ALL, $sum );

//. function
function _rep( $type, $n ) {
	return _reg_rep( $n, TSV_REP[ $type ] );
}

//. function
function _do_test( $name, $tid ) {
	global $id, $cnt_do;
	if ( ! $cnt_do )
		$cnt_do = 0;
	_m( "$id: $name - $tid" );
	++ $cnt_do;
	if ( $cnt_do < 50 ) return;
	_pause( 'pause' );
	$cnt_do = 0;
}
