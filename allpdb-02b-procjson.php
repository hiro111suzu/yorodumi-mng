<?php
include "commonlib.php";

/*

auth_ => label_ があればいらない
	pdbx_unobs_or_zero_occ_residues
	struct_sheet_range
	struct_conf
	struct_site
	struct_site_gen
	ndb_struct_na_base_pair
	struct_ref_seq
*/


//. init

//. 実行
_line( '変換開始' );

foreach ( _idloop( 'pdb_json_pre', 'pdb_json_pre => pdb_json' ) as $fn_in ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = _fn2id( $fn_in );

//	if ( $id != '6sv4' ) continue;

	$fn_out = _fn( 'pdb_json', $id );
	if ( _same_time( $fn_in, $fn_out ) && ! FLG_REDO ) continue;
	$json = _json_load2( $fn_in );

	//.. poly_seq_scheme
	$id_chain2asym = [];
	$id_asym2chain = [];
	$seq_auth2label = [];
	$seq_label2auth = [];
	foreach ( (array)$json->pdbx_poly_seq_scheme as $c ) {
		if ( $c->hetero != 'n' ) continue;
		$seq_num = $c->pdb_seq_num. $c->pdb_ins_code;
		$id_asym2chain[ $c->asym_id       ] = $c->pdb_strand_id;
		$id_chain2asym[ $c->pdb_strand_id ] = $c->asym_id;
		$seq_auth2label[ $c->asym_id ][ $seq_num   ] = $c->seq_id;
		$seq_label2auth[ $c->asym_id ][ $c->seq_id ] = $seq_num;
	}

	//.. model_list
	//- モデル数
	$model_list = [];
	foreach ([
		'ndb_struct_na_base_pair' ,
		'ndb_struct_na_base_pair_step' ,
		'pdbx_distant_solvent_atoms' ,
		'pdbx_struct_special_symmetry' ,
		'pdbx_unobs_or_zero_occ_atoms' ,
		'pdbx_unobs_or_zero_occ_residues' ,
		'pdbx_validate_chiral' ,
		'pdbx_validate_close_contact' ,
		'pdbx_validate_main_chain_plane' ,
		'pdbx_validate_peptide_omega' ,
		'pdbx_validate_planes' ,
		'pdbx_validate_polymer_linkage' ,
		'pdbx_validate_rmsd_angle' ,
		'pdbx_validate_rmsd_bond' ,
		'pdbx_validate_symm_contact' ,
		'pdbx_validate_torsion' ,
		'struct_mon_prot_cis' ,
	] as $c ) foreach ( (array)$json->$c as $c1 ) {
		if ( $c1->PDB_model_num != '' )
			$model_list[ $c1->PDB_model_num ] = 1;
	}
	$model_list = array_keys( $model_list );
	sort( $model_list );

	//.. delete
	foreach ([
		'pdbx_poly_seq_scheme' ,
		'pdbx_distant_solvent_atoms' ,
		'pdbx_unobs_or_zero_occ_atoms' ,
		'pdbx_struct_conn_angle' ,
		'pdbx_struct_sheet_hbond' ,
		'struct_sheet_order' ,
		'entity_poly_seq' ,
	] as $categ_name ) {
		unset( $json->$categ_name );
	}

	//.. pdbx_nonpoly_scheme 水を消す
	foreach ( (array)$json->pdbx_nonpoly_scheme as $num => $c ) {
		if (
			$c->mon_id == 'HOH' ||
			$c->mon_id == 'DOD'
		)
			unset( $json->pdbx_nonpoly_scheme[ $num ] );
	}

	//.. struct_conn hydrogを消す
	foreach ( (array)$json->struct_conn as $num => $c ) {
		if ( $c->conn_type_id == 'hydrog' )
			unset( $json->struct_conn[ $num ] );
	}

	//.. label_* と auth_* があれば auth_*を消す
	foreach ([
		'pdbx_unobs_or_zero_occ_residues' ,
		'struct_sheet_range' ,
		'struct_conf' ,
		'struct_site' ,
		'struct_site_gen' ,
		'ndb_struct_na_base_pair' ,
		'ndb_struct_na_base_pair_step' ,
		'struct_ref_seq' ,
	] as $categ ) {
		foreach ( (array)$json->$categ as $num => $c ) {
//			_m( "$categ - #$num" );
//			_pause( "before:\n". _json_pretty( $c ) );			
			foreach( $c as $item_name => $c2 ) {
				if ( ! _instr( 'auth_', $item_name ) ) continue;
				$label_name = strtr( $item_name, [ 'auth_' => 'label_' ] );
				if ( ! $c->$label_name ) continue;
				unset( $json->$categ[ $num ]->$item_name );
//				_cnt( "$categ.$item_name" );
			}
//			_pause( "after:\n". _json_pretty( $c ) );
		}
	}
//	_cnt();
//	_pause();

	//.. add
	$json->_yorodumi = compact(
		'id_chain2asym' ,
		'id_asym2chain' ,
		'seq_auth2label' ,
		'seq_label2auth' ,
		'model_list'
	);

	_json_save( $fn_out, $json );
	touch( $fn_out, filemtime( $fn_in ) );
}

//. 無くなったデータを消す
_delobs_pdb( 'pdb_json' );
_end();
