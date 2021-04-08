<?php
include "commonlib.php";
$lines = _file( DN_PREP . '/problem/movie-6-check.txt' );
_m( count( $lines ) . ' movies to be made' );
$only = $argv[1];

//. mainloop
foreach ( $lines as $line ) {
	list( $emdbid, $pdbid ) = explode( '-', preg_replace( '/^.+ /', '', $line ) );
	if ( $only != '' && $only != $emdbid && $only != $pdbid ) continue;

	if ( $emdbid == 3374 ) continue;

	chdir( DN_EMDB_MED . "/$emdbid" );
	$fn_pdb = "$pdbid.cif.gz";
	$fn_out = "fit-$pdbid.py";
	$s = "$emdbid - $pdbid";
	
	if ( ! file_exists( $fn_pdb ) ) {
		if ( file_exists( "$pdbid.ent.gz" ) ) {
			_m( "$s: PDB形式" );
			$fn_pdb = "$pdbid.ent.gz";
			continue;
		} else {
			_m( "$s: cifのリンクができていない" );
			continue;
		}
	}
	
	if ( file_exists( 's3.py' ) ) {
		_m( 'もうセッションがあるっぽい' );
		continue;
	}

	
	if ( ! file_exists( 'fit.py' ) ) {
		_m( "$s: fit.pyがない" );
		continue;
	}
	if ( file_exists( $fn_out ) ) {
		_m( "$s: すでに作ってある" );
		continue;
	}

	_m( "$emdbid: $fn_pdb" );
	_exec( DISPLAY . "chimera fit.py ../fit.cmd $fn_pdb ../save_temp.cmd" );
	
	$fn = "tempfit.py";
	if ( ! file_exists( $fn ) and file_exists( "../$fn" ) )
		$fn = "../$fn";
	if ( ! file_exists( $fn ) )
		_m( "$emdbid-$pdbid: ファイル作成失敗" );	

	rename( $fn, $fn_out );

	_m( "$emdbid - $fn_out: 作成成功"  );
}

