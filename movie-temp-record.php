<?php
//. init
require_once( "commonlib.php" );
define( 'IDLIST_MOVREC', _json_load( DN_PREP. '/idlist_recmov.json' ) );

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
define( 'CMD_CHIMERA', DISPLAY . 'chimera --geometry +0+0 ' );
//define( 'CMD_CHIMERA', DISPLAY. 'chimera --nogui +0+0 ' ); //- 動かない

//.. スナップショット用の方向の名前
$ar_ang = array(
	'bottom'	=> '00332' ,
	'bottom2'	=> '00330' ,
	'top'		=> '00242' ,
	'top2'		=> '00240'
);

//. 複数プロセス実行しないように
define( 'FN_RECORDING', DN_TEMP. '/recoding' );
if ( file_exists( FN_RECORDING) ) {
	_die( '別の録画プロセスが実行中' );
}
touch( FN_RECORDING );

//. エントリごとのループ
foreach( _idlist( 'emdb' ) as $id ) {
	if ( ! IDLIST_MOVREC[ $id ] ) continue;
	_count( 'emdb' );
	chdir( $dn_ent = _fn( 'emdb_med', $id ) );

	//- スナップショットの画像番号
	$num_snap = $ar_ang[ _mng_conf( 'img_angle', $id ) ] ?: "00000" ;

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
		touch( $fn_recording ); //- 作成中の印
		_exec( CMD_CHIMERA. $fn_py .' '. $fn_cmd );
		_del( $fn_recording );
		
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
	}
} //- main loopの終わり

//. end
_del( FN_RECORDING );
_end();
