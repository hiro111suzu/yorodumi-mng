<?php
// make vq

//. init
require_once( "commonlib.php" );
_initlog( "pdb-9: make vq pdb" );
define( BLIST, 'blacklist_make_pdb_vq' );

//. start main loop
foreach ( $vqnums as $vqnum ) {
	_m( "vq: {$vqnum}個", 1 );
	//- コマンドファイル
	$cmdfn = "temp/pdb_{$vqnum}vq.txt";
	file_put_contents( $cmdfn, ''
		. "2\n"		//- Do you want to mass-weight the atoms?(水とか) No
		. "1\n"		//- B-factor threshold? No
		. "$vqnum\n" //- cvの数
		. "1\n"		//- コネクティビティ計算するか？
		. "1\n"		//- No
	);

	foreach ( $pdbidlist as $id ) {
		$did = "pdb-$id";
		$pdbfn = _fn( 'pdb_pdb' );
		$vqfn  = _fn( "vq$vqnum" );

		if ( $_redo )
			_del( $vqfn );

		//- blacklist
		if ( _blist2( $id ) ) {
			_del( $vqfn );
			continue;
		}

		if ( _newer( $vqfn, $pdbfn ) ) continue;
		if ( _proc( "pdb-$id-vq-$vqnum" ) ) continue;

	//.. run qpdb
		_m( "$id: " );
		
		//- tmpドライブに解凍
		$uncfn = DN_TEMP . "/$id.ent";
		copy( $pdbfn, "$uncfn.gz" );
		exec( "gunzip $uncfn.gz" );

		exec( "qpdb $uncfn $vqfn <$cmdfn" );
		$res = "失敗 !!!!!";
		if ( file_exists( $vqfn ) ) {
			if ( filesize( $vqfn ) < 100 ) {
				unlink( $vqfn );
			} else {
				$res = "成功";
			}
		}

		_del( $uncfn );
		_log( "PDB-$id VQ-$vqnum 作成" . $res );
		_proc();
	}
}

_writelog();
return;
