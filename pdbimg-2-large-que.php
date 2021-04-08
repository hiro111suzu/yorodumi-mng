<?php
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );
define( 'TSV_CMD', _tsv_load( DN_EDIT. '/pdbimg.tsv' ) );

//. dep
_line( "que情報作成 - dep" );
_count();

foreach ( _idlist( 'large' ) as $id ) {
	if ( _count( 100, 0 ) ) break;
	$fn = _fn( 'pdb_mmcif', $id );

	if ( _qok( $fn, $id, 'dep' ) ) continue;

	//- チェーン数多い - 10M 以上なら
	$bb = '';
	$st2 = '';
	if ( filesize( $fn ) > 10000000 )  {
		$bb = J_FILT_BB;
		$st2  = 'select dna or rna; backbone 250;';
	}

	$cmd = _inlist( $id, 'ribosome' )
		? J_RIBOSOME	//- リボソーム (RNAだけでベストアングル)
		: J_BEST
	;

	_save_que( "$id-dep", $fn, [
		'id'	=> $id ,
		'type'	=> 'dep' ,
		'cmd'	=> strtr( J_BASE . $cmd, [
			'<fn>'		=> $fn ,
			'<style>'	=> $st2,
			'<filt>'	=> $bb ,
			'<ccolor>'	=> J_COL_CHAIN ,
			'<cmd2>'	=> TSV_CMD[ "$id-$asb_id" ] ,
			"\r"		=> '' ,
			"\n"		=> ''
		])
	]); 
}


//die();
