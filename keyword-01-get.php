検索用キーワードファイル作成
emdbとpdb
sasは、sas-7で

<?php
require_once( "commonlib.php" );

define( 'DO_EMDB', true );
//define( 'DO_EMDB', false );

$dn = DN_PREP . '/keyword';

$kw = [];
$test_count = [];
$test_nn = [];

//. emdb data
_line( 'EMDBデータ' );
if ( DO_EMDB ) {
	$db = 'emdb';
	foreach ( _idlist( 'emdb', 'キーワード収集 EMDB' ) as $id ) {
		_count( 'emdb' );
		$fn_in  = _fn( 'maindb_json', "emdb-$id" );
		$fn_out = _fn( 'emdb_kw' , $id );
		if ( _same_time( $fn_in, $fn_out ) ) continue;
		$terms = explode( ' | ', (string)_json_load( $fn_in )[ 'search_words' ] );
		foreach ( $terms as $term ) {
			$kw[] = trim( $term );
		}

/*

		$kw = [ $id ];

		//- deposition
		$cur = 'dep';
		$cjson = $json->deposition;
		_ad( 'title' );
		_ad_c( 'authors' );
		_ad_c( 'keywords' );
		_ad( 'fittedPDBEntryId' );
		
		//- citation
		$cur = 'citation';
		$cjson = $json->deposition->primaryReference->journalArticle;
		_ad( 'articleTitle' );
		_ad_c( 'authors' );
		_ad( 'journal' );
		_ad( 'ref_pubmed' );

		//- map
		$cur = 'map';
		$cjson = $json->map;
		_ad( 'annotationDetails' );

		//- sample
		$cur = 'sample';
		$cjson = $json->sample;
		_ad( 'name' );
		_ad( 'compDegree' );
		foreach ( $cjson->sampleComponent as $cjson ) {
			_ad( 'entry' );
			_ad( 'sciName' );
			_ad( 'synName' );

			$cjson = $cjson->{ $cjson->entry };
			_ad( 'sciSpeciesName' );
		}
		
		//- experiment
		$e= $json->experiment;

		$cur = 'vitrification';
		foreach ( $e->vitrification as $cjson ) {
			_ad( 'details' );
			_ad( 'instrument' );
		}

		$cur = 'imaging';
		foreach ( $e->imaging as $cjson ) {
			 _ad( 'detector' );
			 _ad( 'microscope' );
		}

		//- proc
		$cur = 'processing';
		$p = $json->processing;
		foreach ( (array)$p->reconstruction as $cjson ) {
			_ad( 'algorithm' );
			_ad( 'software' );
		}

*/
		_sv();
	}
}
_delobs_emdb( 'emdb_kw' );

//. pdb data
$db = 'pdb';
_count();

foreach ( _idloop( 'pdb_json', 'キーワード収集 PDB' ) as $fn_in ) {
	//.. keyword
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn_in );
	$fn_out = _fn( 'pdb_kw' , $id );
	if ( ! _same_time( $fn_in, $fn_out ) ) {
		$json = _json_load2( $fn_in );
		$kw = [ $id ];

//		_pdb( 'audit_author',	'name' );
//		_pdb( 'chem_comp',		[ 'id', 'name' ] );
		_pdb( 'citation',		[ 'title', 'pdbx_database_id_PubMed', 'journal_abbrev' ] );
//		_pdb( 'citation_author', 'name' );
		_pdb( 'pdbx_database_related', 'db_id' );
		_pdb( 'entity', [ 'pdbx_description', 'details' ] );
		_pdb( 'entity_keywords', 'text' ); 
		_pdb( 'entity_name_com', 'name' );
		_pdb( 'entity_name_sys', 'name' );
		_pdb( 'entity_poly',
			[ 'pdbx_seq_one_letter_code', 'pdbx_seq_one_letter_code_can', 'type' ] );
		_pdb( 'entity_src_gen', 
			[ 'pdbx_gene_src_scientific_name', 'gene_src_common_name', 'details' ] );
		_pdb( 'entity_src_nat', [ 'common_name', 'details', 'pdbx_organism_scientific' ] );
		_pdb( 'pdbx_entity_name', 'name' );
		_pdb( 'pdbx_entity_nonpoly', 'name' );
//		_pdb( 'struct', [ 'pdbx_descriptor', 'title' ] );
		_pdb( 'struct_keywords', [ 'pdbx_keywords', 'text' ] );
		_pdb( 'struct_ref', [ 'pdbx_db_accession', 'db_code' ] );

		_pdb( 'exptl', 'method' );
		_pdb( 'ndb_struct_conf_na', 'feature' );

		_pdb( 'em_3d_fitting', 'software_name' );
		_pdb( 'em_3d_reconstruction', 
			[ 'ctf_correction_method', 'details', 'method', 'software' ] );
			
		_pdb( 'em_assembly',
			['aggregation_state', 'composition', 'details', 'name' ] );

		_pdb( 'em_detector', [ 'type', 'details' ] );

		_pdb( 'em_entity_assembly', [
			'details' ,
			'ebi_cell' ,
			'ebi_cellular_location',
			'ebi_organelle',
			'ebi_organism_common',
			'ebi_organism_scientific',
			'ebi_tissue',
			'go_id',
			'ipr_id',
			'name',
			'oligomeric_details',
			'synonym',
			'type'
		]);

		_pdb( 'em_vitrification', [ 'details', 'instrument', 'method' ] );
		_pdb( 'em_imaging', [ 'details', 'microscope_model', 'specimen_holder_model' ] );
		_pdb( 'em_sample_preparation', 'details' );
		_pdb( 'em_detector', [ 'details', 'type' ] );
		_pdb( 'em_3d_fitting',
			[ 'details', 'method', 'ref_protocol', 'em_3d_fitting.software_name' ] );


//		_pdb( 'pdbx_entry_details',
//			[ 'compound_details', 'nonpolymer_details', 'sequence_details', 'source_details' ]);
		$kw = array_merge(
			$kw ,
			explode( '|', _ezsqlite([
				'dbname'	=> 'strid2dbids' ,
				'where'		=> [ 'strid', $id ] ,
				'select'	=> 'dbids'
			]))
		);
		foreach ( (array)array_keys( _json_load( _fn( 'pdb_metjson', $id ) ) ) as $k ) {
			_ad_main( 'm:' . $k );
		}
		foreach ( (array)$json->pdbx_deposit_group as $c ) {
			_ad_main( 'grp_dep:' . $c->group_id );
		}
		_sv();
	}
	
	//.. author
	$fn_out = _fn( 'pdb_auth' , $id );
	if ( ! _same_time( $fn_in, $fn_out ) ) {
		if ( !$json )
			$json = _json_load2( $fn_in );
		_pdb( 'audit_author',	'name' );
		_pdb( 'citation_author', 'name' );
		_sv();
	}
}
_delobs_pdb( 'pdb_kw' );
_delobs_pdb( 'pdb_auth' );
_end();

//. function:
//.. _pdb
function _pdb( $cat, $ar ) {
	global $cur, $cjson, $json;
	$cur = $cat;
	foreach ( (array)$json->$cat as $cjson ) {
		if ( ! is_array( $ar ) )
			$ar = [ $ar ];
		foreach ( $ar as $k ) {
			if ( in_array( "$cat $k", [ 'struct_keywords text' ] ) ) {
				_ad_c( $k );
			} else {
				_ad( $k );
			}
		}
	}
}


//.. functions ad:
//- cjson(グローバル)に現在のjsのツリー
//- 探索する$s要素名
//... _ad
function _ad( $s ) {
	global $cjson;
	$v = $cjson->$s;
	if ( _instr( 'seq_one_letter_code', $s ) )
		$v = _reg_rep( $v, [ "/[\n\r\t ]/" => ''] );
	
	if ( is_array( $v ) )
		foreach ( $v as $c ) {
			_ad_main( $c, $s );
		}
	else
		_ad_main( $v, $s );
}

//... _ad_c: コンマ区切り
function _ad_c( $s ) {
	global $cjson;
	foreach ( explode( ',', (string)$cjson->$s ) as $k )
		_ad_main( $k, $s );
}

//... _ad_main
function _ad_main( $s, $type = '-' ) {
	global $kw, $test_count, $test_nn, $db, $cur;
	$type = "$db/$cur/$type";
	$s = trim( $s );
	if (
		strlen( $s ) < 4 ||
		( strlen( $s ) < 6 && $s == _numonly( $s ) ) ||
		in_array( strtolower( $s ), [ 'na', 'n/a', '-', 'none', 'other' ] )
	) {
		$s == '';
	}
	if ( $s != '' ) {
		$kw[] = $s;
		++ $test_nn[ $type ];
	}
	++ $test_count[ $type ];
}


//.. _sv: データを整理して保存
function _sv() {
	global $kw, $id, $fn_in, $fn_out;
	$kw = array_unique( (array)$kw );
	file_put_contents( $fn_out, implode( "\n", $kw ) );

//	_line( $id );
//	_pause( implode( "\n", $kw ) );
	touch( $fn_out, filemtime( $fn_in ) );
	$kw = [];
}

//. 読み込む項目、準備中
/*
audit_author
	name
chem_comp
	id, 
	name
citation
	title
	pdbx_database_id_PubMed
	journal_abbrev
citation_author
	name
pdbx_database_related
	db_id
entity
	pdbx_description
	details
	pdbx_ec
entity_keywords
	text 
entity_name_com
	name
entity_name_sys
	name
entity_poly
	pdbx_seq_one_letter_code
	pdbx_seq_one_letter_code_can
	type
entity_src_gen
	pdbx_gene_src_scientific_name
	gene_src_common_name
	details
entity_src_nat
	common_name
	details
	pdbx_organism_scientific
pdbx_entity_name
	name
pdbx_entity_nonpoly
	name
struct
	pdbx_descriptor
	title
struct_keywords
	pdbx_keywords
	text
struct_ref
	pdbx_db_accession
	db_code
exptl
	method
em_3d_fitting
	software_name
em_3d_reconstruction 
	ctf_correction_method
	details
	method
	software
em_assembly
	aggregation_state
	composition
	details
	name
em_detector
	type
	details
em_entity_assembly
	details ,
	ebi_cell ,
	ebi_cellular_location
	ebi_organelle
	ebi_organism_common
	ebi_organism_scientific
	ebi_tissue
	go_id
	ipr_id
	name
	oligomeric_details
	synonym
	type
em_vitrification
	details
	instrument
	method
em_imaging
	details
	microscope_model
	specimen_holder_model
em_sample_preparation
	details
em_detector
	details
	type
em_3d_fitting
	details
	method
	ref_protocol
	em_3d_fitting.software_name
pdbx_entry_details
	compound_details
	nonpolymer_details
	sequence_details
	source_details



*/

