PDBMLplusのシュリンクデータを作ってみる
<?php
//. misc init						
require_once( "commonlib.php" );
_initlog( "make shrinkplus-pdb data" );

ini_set("memory_limit","1024M"); 

//. replace data								
$in = array(
	'/\<PDBx:/' ,
	'/\<\/PDBx:/' ,
	'/.+\<[^\>]+[ :]nil="true" *\/\>[\n\r]+/' ,
	'/.+\<[^\>]+[ :]nil="true" *\>\<.+?\>[\n\r]+/' ,
	'/\<datablock .+?\>/s'
);
$out = array(
	'<' ,
	'</' ,
	'',
	'',
	'<datablock>'
);

$deltag = array (
	'entity_poly_seqCategory',
	'pdbx_poly_seq_schemeCategory',

	'chem_compCategory',
	'ndb_struct_na_base_pairCategory',
	'ndb_struct_na_base_pair_stepCategory',
	'pdbx_nonpoly_schemeCategory', 
	'pdbx_struct_legacy_oper_listCategory',
	'pdbx_struct_oper_list' ,
	'pdbx_struct_sheet_hbondCategory',
	'pdbx_unobs_or_zero_occ_atomsCategory',
	'pdbx_unobs_or_zero_occ_residuesCategory',
	'pdbx_validate_chiralCategory',
	'pdbx_validate_close_contactCategory',
	'pdbx_validate_main_chain_planeCategory',
	'pdbx_validate_peptide_omegaCategory',
	'pdbx_validate_planesCategory',
	'pdbx_validate_rmsd_angleCategory',
	'pdbx_validate_rmsd_bondCategory',
	'pdbx_validate_torsionCategory',
	'pdbx_validate_symm_contactCategory',
//	'struct_asymCategory',
	'struct_conf' ,
	'struct_connCategory',
	'struct_ref_seqCategory',
	'struct_ref_seq_difCategory',
	'struct_sheetCategory',
	'struct_sheet_orderCategory',
	'struct_sheet_rangeCategory',
	'pdbx_struct_chem_comp_diagnosticsCategory',
	'struct_biol_genCategory',
	'struct_confCategory'

);
/*
foreach ( $deltag as $tag ) {
	$deltagreg[] = '/ +\<' . $tag . '.+\<\/' . $tag . '\>[\n\r]+/s';
}
*/

//. main loop								
foreach( $pdbidlist as $id ) {

	$plusfn = "$_pdbdir/$id.pdbmlplus.xml";
	$pshrfn  = "./test-plus/$id-s.xml";

	if ( $_redo )
		_del( $pshrfn );
	if ( file_exists( $pshrfn ) ) {
		_m();
		continue;
	}
	_log( "$id: made shrink-plus-pdb data" );
	$file = file_get_contents( $plusfn );
	$file = preg_replace( $in, $out, $file );
//	$file = str_replace( 'hogehoge', "pdb-$id", $file );
//	file_put_contents( "./test-plus/pdb-$id.test", $file );
//	return;
	
	$xml = simplexml_load_string( $file );
	foreach ( $deltag as $tag ) {
		$xml->$tag = '';
	}
	$s = preg_replace( '/([\n\t]+).+?(\<\/datablock\>)/', '\1\2', $xml->asXML() );

//	$s = preg_replace( $deltagreg, 's', $file );

	file_put_contents( $pshrfn, $s );
//	return;
}
//_writelog();


