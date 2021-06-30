<?php
//. init
require_once( "commonlib.php" );
require_once( 'marem-common.php' );

//-
define( 'FN_FLG_SYNC', DN_PREP. '/marem/sync' );
if ( file_exists( FN_FLG_SYNC ) ) {
	_marem_log( 'hwork_sync 開始' );
	rename( FN_FLG_SYNC, FN_FLG_SYNC. '-doing' );
	_php( 'marem-hwork-sync' );
	rename( FN_FLG_SYNC. '-doing', FN_FLG_SYNC. '-done' );
	_marem_log( 'hwork_sync 終了' );
}
/*
if ( file_exists( FN_FLG_SYNC ) ) {
	_marem_log( 'sync-map 開始' );
	rename( FN_FLG_SYNC, FN_FLG_SYNC. '-doing' );
	passthru( ' ~/sync-map.sh' );
	rename( FN_FLG_SYNC. '-doing', FN_FLG_SYNC. '-done' );
	_marem_log( 'sync-map 終了' );
}
define( 'FN_FLG_SYNC2', DN_PREP. '/marem/sync2' );
if ( file_exists( FN_FLG_SYNC2 ) ) {
	_marem_log( 'hwork_sync 開始' );
	rename( FN_FLG_SYNC2, FN_FLG_SYNC2. '-doing' );
	passthru( ' ~/hwork_sync.sh' );
	rename( FN_FLG_SYNC2. '-doing', FN_FLG_SYNC2. '-done' );
	_marem_log( 'hwork_sync 終了' );
}
*/
define( 'IDLIST_MOVREC', _json_load( DN_PREP. '/idlist_recmov.json' ) );
if ( ! IDLIST_MOVREC ) {
	_marem_log( 'Rec確認 ジョブなし' );
	die();
}
$count_mov = 0;
$count_did = 0;
foreach ( array_keys( IDLIST_MOVREC ) as $k ) {
	if ( _instr( '-', $k ) )
		++ $count_mov;
}
_marem_log( "rec開始 - ムービー数: $count_mov" );

//.. スクリプトテンプレート
//... 回転だけ
define( 'SCR_SOLID', <<<EOD
reset; wait
<matrix>
reset; wait

movie record fformat jpeg directory ./img#/ pattern img*
roll y 2 180; wait
wait 15
roll x 2 180; wait
movie stop
stop noask 
EOD
);

//... 表面ムービー用
define( 'SCR_SURF', <<<EOD
reset; wait
<matrix>
reset; wait

movie record fformat jpeg directory ./img#/ pattern img*
roll y 2 180; wait
wait 15
roll x 2 180; wait
reset pos1; wait
reset pos2 180; wait
reset pos1 180; wait
reset; wait
movie stop
stop noask 
EOD
);

//... あてはめムービー用
define( 'SCR_FIT', <<<EOD
reset; wait
<matrix>

movie record fformat jpeg directory ./img#/ pattern img*
roll y 2 180; wait
wait 15
roll x 2 180; wait
reset pos1; wait
reset pos1+ 50; wait
reset pos2 180; wait
reset pos1+ 180; wait
reset pos1 50; wait
reset; wait
movie stop
stop noask 
EOD
);

//... chimeraコマンド
define( 'CMD_CHIMERA', DISPLAY . ' timeout -k 60 900 chimera --geometry +0+0 ' );
//define( 'CMD_CHIMERA', DISPLAY. 'chimera --nogui +0+0 ' ); //- 動かない

//.. スナップショット用の方向の名前
define( 'ANG_NUM', [
	'bottom'	=> '00332' ,
	'bottom2'	=> '00330' ,
	'top'		=> '00242' ,
	'top2'		=> '00240'
]);

//. 複数プロセス実行しないように
if ( file_exists( FN_RECORDING) ) {
	_marem_log( 'rec 終了 - 別の録画プロセスが実行中' );
	_die( '別の録画プロセスが実行中' );
}
touch( FN_RECORDING );

//. エントリごとのループ
foreach( array_keys( IDLIST_MOVREC ) as $id ) {
	if ( _instr( '-', $id ) ) continue;
	_m( $id );

	if ( file_exists( DN_MAREM_WORK. '/stop' ) ) {
		_marem_log( 'ストップファイルにより停止' );
		_m( 'ストップファイルにより停止', 'red' );
		break;
	}
	if ( ! IDLIST_MOVREC[ $id ] ) continue;
	_count( 'emdb' );
	chdir( $dn_ent = _fn( 'emdb_med', $id ) );

	if ( ! file_exists( "emd_$id.map" ) ) {
		_marem_log( "$id - mapファイルがない" );
		continue;
	}

	//- スナップショットの画像番号
	$num_snap = ANG_NUM[ _mng_conf( 'img_angle', $id ) ] ?: "00000" ;

//. ムービーごとのループ
	foreach ( range( 1, 20 ) as $movid ) {
		if ( ! IDLIST_MOVREC[ "$id-$movid" ] ) continue;
		//.. ファイル名
		$dn_frame  		= "img$movid";
		$fn_py    		= "s$movid.py";
		$fn_pyc   		= "s$movid.pyc";
		$fn_redo  		= "r$movid";
		$fn_frame 		= "$dn_frame/img00370.jpeg";
		$fn_cmd   		= "m$movid.cmd";		
		$fn_recording	= "recording$movid";
		$fn_snapl  		= "snapl$movid.jpg";
		$fn_snaps  		= "snaps$movid.jpg";
		$fn_snapss 		= "snapss$movid.jpg";
		$fn_matrix		= "matrix.txt";
		$fn_matrix2		= "ym/matrix.txt";


		//.. やるかやらないか
		//- セッションがなければやらない
		if ( ! file_exists( $fn_py ) ) continue;

		//- 再作成マーク
		if ( file_exists( $fn_redo ) )
			_del( $fn_snaps, $fn_frame, $fn_redo );

		//- 画像があったらやらない
		if ( file_exists( $fn_frame ) || file_exists( $fn_snaps ) ) continue;

		//.. 準備
		//- 画像用ディレクトリ作成
		if ( is_dir( $dn_frame ) )
			exec( "rm -rf $dn_frame" );
		mkdir( $dn_frame );

		//- cmdファイル作成
		$py = file_get_contents( "s$movid.py" );
		$templ = _instr( "'pos1'", $py )
			? (
				_instr( "'pos1+'", $py ) ? SCR_FIT : SCR_SURF
			)
			: SCR_SOLID
		;

		//- 1番目なら、matrixも作成
		$matrix = (
			( $movid == 2 || $movid == 2 ) &&
			! file_exists( $fn_matrix ) &&
			! file_exists( $fn_matrix2 )
		)
			? 'matrixget matrix.txt'
			: ''
		;

		file_put_contents( $fn_cmd, 
			strtr( $templ, [ 'img#' => "img$movid", '<matrix>' => $m ] ) 
		);

		//.. record
		_line( "generating movie frames",  "$id - $movid" );
		_del( $fn_pyc );
		if ( file_exists( $fn_recording ) ) { //- 念の為
			_marem_log( "$id-$movid: 別プロセスがこのIDを録画中" );
			continue;
		}
		touch( $fn_recording ); //- 作成中の印
		_marem_log( "$id-$movid: rec 開始" );
		_exec( CMD_CHIMERA. $fn_py .' '. $fn_cmd );
		_del( $fn_recording );
		++ $count_did;
		_marem_log( "$id-$movid: rec 終了 ($count_did / $count_mov)" );

		//- いらないファイルを削除
		_del(
			$fn_snapl ,
			$fn_snaps ,
			$fn_snapss ,
			"movie$movid.mp4" ,
			"movies$movid.mp4" ,
			"movie$movid.webm" ,
			"movies$movid.webm" ,
			$fn_cmd
		);
		if ( file_exists( $fn_frame ) ) {
			_log( "$id-$movid: ムービーフレーム画像作成" );
			_mov_snaps( $dn_ent, $movid, $num_snap ); //- スナップショット画像
		} else {
			_problem( "$id-$movid: ムービーフレーム画像作成失敗" );
		}
		_m( "完了", 1 );
		_auto_run( 'mov' );
	}
} //- main loopの終わり

//. end

_del( FN_RECORDING );
_marem_log( 'rec 終了' );
_end();
