<?php

//. init
require_once( "commonlib.php" );
$mov_id_list = array_merge( range( 0, 20 ), [ 'jm' ] );

define( 'ANG_NUM', [
	'bottom'	=> '00332' ,
	'bottom2'	=> '00330' ,
	'top'		=> '00242' ,
	'top2'		=> '00240'
]);

//. エントリごとのループ
foreach( _idlist( 'emdb' ) as $id ) {
	chdir( $dn_base = _fn( 'emdb_med', $id ) );

	//- スナップショットの画像番号
	$num_snap = ANG_NUM[ _mng_conf( 'img_angle', $id ) ] ?: "00000" ;

	//.. ムービー毎のループ
	foreach ( $mov_id_list as $mov_id ) {

		if ( file_exists( "recording$mov_id" ) ) continue; //- レコーディング中？
		if ( _newer( "movies$mov_id.mp4", "img$mov_id/img00374.jpeg" ) ) continue;

		if ( _proc( "encode-$id-$mov_id" ) ) continue;

		if ( ! file_exists( "snapss$mov_id.jpg" ) ) {
			_mov_snaps( $dn_base, $mov_id, $num_snap ); //- スナップショット画像
		}
		_movie_encode( $dn_base, $mov_id );
		_proc();
	}

} //- main loopの終わり

//. end
_end();
