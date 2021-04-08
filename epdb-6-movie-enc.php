<?php
//. misc init
require_once( "commonlib.php" );

//. encode

foreach( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$dn_entry = _fn( 'pdb_med', $id );
	foreach ( glob( "$dn_entry/img*" ) as $dn_frame ) {
		$mov_id = substr( basename( $dn_frame ), 3 );
		$fn_frame_last = "$dn_frame/img00834.jpeg";

		if ( ! file_exists( $fn_frame_last ) ) {
			_problem( "PDB-$id: [encode-movie-$id-$mov_id]: フレーム画像つくりかけ" );
			continue;
		}

		if ( _newer( _fn( 'pdb_mp4', $id, $mov_id ), $fn_frame_last ) ) {
			_problem( "PDB-$id: [mov-$mov_id] 古いフレーム画像が残っている？" );
			continue;
		}

		if ( _proc( "encode-movie-$id-$mov_id" ) ) continue;
		_movie_encode( $dn_entry, $mov_id );
		_proc();
	}
}
_cnt();

_end();
