<?php
//. misc init
require_once( "commonlib.php" );

//. 消すタグ

$t = <<<EOD
entity_poly_seq
pdbx_poly_seq_scheme
chem_comp
ndb_struct_na_base_pair
ndb_struct_na_base_pair_step
pdbx_nonpoly_scheme 
pdbx_struct_legacy_oper_list
pdbx_struct_oper_list
pdbx_struct_sheet_hbond
pdbx_unobs_or_zero_occ_atoms
pdbx_unobs_or_zero_occ_residues
pdbx_validate_chiral
pdbx_validate_close_contact
pdbx_validate_main_chain_plane
pdbx_validate_peptide_omega
pdbx_validate_planes
pdbx_validate_rmsd_angle
pdbx_validate_rmsd_bond
pdbx_validate_torsion
pdbx_validate_symm_contact
struct_conn
struct_ref_seq
struct_ref_seq_dif
struct_sheet
struct_sheet_order
struct_sheet_range
pdbx_struct_chem_comp_diagnostics
struct_biol_gen
struct_conf
struct_site_gen
struct_site
pdbx_struct_mod_residue
struct_mon_prot_cis
EOD;

$deltag = [];
foreach ( explode( "\n", $t ) as $s ) {
	$s = trim( $s );
	if ( $s != '' )
		$deltag[] = $s;
}

//. main loop
_line( 'main loop' );
foreach( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$fn_in   = _fn( 'pdb_json' , $id );
	$fn_out  = _fn( 'epdb_json', $id );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	$json = _json_load2( $fn_in );

	//- いらない内容を除去
	foreach ( $deltag as $tag )
		unset( $json->$tag );

	//- 変換、書き込み
	_save_touch([
		'fn_in'  => $fn_in ,
		'fn_out' => $fn_out ,
		'data'  => $json ,
		'name'  => "epdbjson-$id"
	]);

	$i = filesize( $fn_in  ); $ik = round( $i / 1024 , 2 );
	$o = filesize( $fn_out ); $ok = round( $o / 1024 , 2 );
	_m( "$id: " . round( $o / $i * 100, 2 ) . "%\t( $ik kB\t=> $ok kB )"  );
//	if ( _count( 100, 10 ) ) break; 
}

//. いらなくなったjsonを消す
_delobs_misc( 'epdb_json', 'epdb' );
_end();
