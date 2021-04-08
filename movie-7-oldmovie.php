古くなったムービーをチェック
<?php

//. init
require_once( "commonlib.php" );

$blist = new cls_blist( 'ignore_oldmov' );

$dn_work = DN_PREP. "/oldmov";
if ( ! is_dir( $dn_work ) )
	die( 'No working dir' );

$dn_old  = "$dn_work/old";    //- oldになったやつ
$dn_ok   = "$dn_work/ok";     //- 手作業でここに移動
$dn_done = "$dn_work/done";   //- スクリプトがここに移動
$dn_ign  = "$dn_work/ignore";
$list_fn_session = [
	's2.py',
	's1,py',
	'session2.py',
	'session1.py'
];

define( 'CHIMERA_CMD', <<<EOD
cd <dn_media>
open <fn_session>; wait
copy file <fn_img> jpeg supersample 1
stop noask
EOD
);

//- chimera コマンドを入れるファイル
$fn_cmd = _tempfn( 'cmd' );

//. main loop
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( $blist->inc( $id ) ) continue;

	$o_id = ( new cls_entid )->set_emdb( $id );
	$dn_media = DN_EMDB_MED. "/$id";
	$fn_img = "$dn_old/$id.jpg";

	//- 違うマップを読み込んでいる奴を表示
	$mapname = $o_id->movjson()->{1}->{'map name'};
	if ( $mapname != '' and  $mapname != "emd_{$id}.map" ) {
		_problem( "$o_id: 別のマップのムービー - " . $mapname );
	}

	//- oldmovじゃなかったら、やらない
	if ( ! $o_id->status()->oldmov ) {
		if ( file_exists( $fn_img ) ) {
			_del( $fn_img );
			_m( "$id: oldmovから画像を削除しました" );
		}
		continue;
	}

	//- startファイルを削除
	$fn_start = "$dn_media/start.py";
	$fn_map   = _fn( 'map', $id );
	
	if ( file_exists( $fn_map ) && file_exists( $fn_start ) )
		if ( filemtime( $fn_map ) > filemtime( $fn_start ) )
			_del( $fn_start );

	if ( file_exists( "$dn_ign/$id.jpg" ) ) continue;
	_problem( "{$o_id}: ムービーが古い" );

	//- 既に画像があったら、やらない
	if ( file_exists( $fn_img ) ) continue;
	if ( file_exists( "$dn_ok/$id.jpg" ) ) continue;

	//- セッションファイル決定
	foreach( $list_fn_session as $n ) {
		$fn_session = "$dn_media/$n";
		if ( file_exists( $fn_session ) ) break;
	}

	//- コマンドファイル作成
	file_put_contents( $fn_cmd ,
		strtr( CHIMERA_CMD, [
			'<dn_media>'	=> $dn_media ,
			'<fn_session>'	=> $fn_session ,
			'<fn_img>'		=> $fn_img 
		])
	);

	//- chimera実行
	_m( '$id: 画像作成のためChimera 実行...' );
	_m( "$id-$fn_cmd" );
	exec( DISPLAY . "chimera $fn_cmd" );
	_m( "完了" );
}

_del( $fn_cmd );

//. okな奴をtouch
$flg = false;
foreach ( glob( "$dn_ok/*.jpg" ) as $fn ) {
	$bn = basename( $fn );
	$id = _numonly( $bn );
	touch( _fn( 'emdb_snap', $id, 'ss1' ) );
	rename( $fn, "$dn_done/$bn" );
	_m( "$id - Touched" );
	$flg = true;
}

//if ( $flg )
//	_php( 'both-1' );

_end();
