<?php
//. init
require_once( "commonlib.php" );
$idlist = [];
$num = 0;
//. エントリごとのループ
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	chdir( $dn_ent = _fn( 'emdb_med', $id ) );

//. ムービーごとのループ
	foreach ( range( 1, 20 ) as $movid ) {
//	foreach ( [2] as $movid ) { //- 2を優先させたいときに使う

		//.. ファイル名
		$dn_frame  		= "img$movid";
		$fn_py    		= "s$movid.py";
		$fn_pyc   		= "s$movid.pyc";
		$fn_redo  		= "r$movid";
		$fn_frame 		= "$dn_frame/img00370.jpeg";
		$fn_cmd   		= "m$movid.cmd";		
		$fn_snapl  		= "snapl$movid.jpg";
		$fn_snaps  		= "snaps$movid.jpg";
		$fn_snapss 		= "snapss$movid.jpg";

		//.. やるかやらないか
		//- セッションがなければやらない
		if ( ! file_exists( $fn_py ) ) continue;

		//- 再作成マーク
		if ( file_exists( $fn_redo ) )
			_del( $fn_snaps, $fn_frame, $fn_redo );

		//- 画像があったらやらない
		if ( file_exists( $fn_frame ) || file_exists( $fn_snaps ) ) continue;

		//.. 登録
		$idlist[ "$id" ] = 1;
		$idlist[ "$id-$movid" ] = 1;
		++ $num;
	}
} //- main loopの終わり

//. end
_json_save( DN_PREP. '/idlist_recmov.json', $idlist );
_m( $num. ' movies to be recorded' );

_end();

