<?php
//. misc init
require_once( "commonlib.php" );
define( 'MANUAL_ORI', 
	_tsv_load2( DN_EDIT. '/pdb_movie_param.tsv' )[ 'manual_orientation' ]
);

//- やりなおしID
$redoids = [];
foreach ( $argv as $i ) {
	if ( ! _inlist( $i, 'epdb'  ) ) continue;
	$redoids[] = $i;
	_m( "やりなおし $id" );
}

$_filenames += [
	'dn_pre'      => DN_PDB_MED. '/<id>/pre_<s1>' ,
	'img_pre'     => DN_PDB_MED. '/<id>/pre_<s1>/1.jpg',
	'orient_json' => DN_PDB_MED. '/<id>/pre_<s1>/orient.json' ,
];

define( 'MODE_DEL', in_array( 'del', $argv ) );


//. 動作モード

//define( 'FLG_IGNORE_OLD', true );
define( 'FLG_IGNORE_OLD', $argv[1] == 'FLG_IGNORE_OLD' );
if ( FLG_IGNORE_OLD )
	_m( '更新データのムービーを再作成しないモード', 1 );


define( 'DELOLD', $argv[1] == 'delold' );
if ( DELOLD )
	_m( '古いムービーを強制的につくりなおすモード', 1 );


//. Jmol スクリプト
define( 'MOVCMD', <<<EOD

function saveimg( num ) {
	var fn = "img" + ( "00000" + num )[-4][0] + ".jpeg";
	write image jpg 90 @fn;
	num++;
	return num;
}

var num = 0; # グローバル変数の使い方が分からん
# Y rotate
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	rotate y 2;
}
# sleep
for ( var i = 0; i < 15; i++ ) {
	num = saveimg( num );
}
# X rotate
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	rotate x 2;
}
# slab
slab on;
var sl = 100;
var dp = 0;
for ( var i = 0; i < 50; i++ ) {
	num = saveimg( num );
	dp += 1.6;
	depth @{ dp % 0 }; # %0 => round
	slab 100;
}
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	sl -= 0.555555555555556;
	dp -= 0.555555555555556;
	depth @{ dp % 0 };
	slab @{ sl % 0 };
}
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	sl += 0.555555555555556;
	dp += 0.555555555555556;
	depth @{ dp % 0 };
	slab @{ sl % 0 };
}
for ( var i = 0; i < 50; i = i+1) {
	num = saveimg( num );
	dp -= 1.6;
	depth @{ dp % 0 };
	slab 100;
}
EOD
);

//- ???
$rotate = [
	0 => '',
	4 => 'rotate y 90;',
	3 => 'rotate y 180;',
	5 => 'rotate y 270;',
	2 => 'rotate x 90;',
	1 => 'rotate x 270;',
];

define( 'ANG_NAME2NUM', [
	'bottom'	=> '00332',
	'bottom2'	=> '00330',
	'top'		=> '00242',
	'top2'		=> '00240',
	'y90'		=> '00045'
]);

define( 'JMS_ZSHADE', 'slab off; set zshade on; set zshadepower 1; slab 60' );

$cnt_newori = 0;

//. main loop
_count();
foreach( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$dn_entry = _fn( 'pdb_med', $id );

	//- エントリごと、マニュアル操作による方向 (マップのないリボソームなど)
	$str_orient = MANUAL_ORI[ $id ]
		? 'q={' . MANUAL_ORI[ $id ] . '};rotate MOLECULAR {0 0 0} @q;center all;'
		: ''
	;

	//.. ムービーごとのループ
	foreach ( glob( "$dn_entry/pre_*" ) as $dn_pre ) {
		if ( file_exists( "$dn_pre/ok" ) ) continue; //- 現在のムービーでOKモード
		//... ファイル名など
		$movid = substr( basename( $dn_pre ), 4 );
		$fn_orient = "$dn_pre/ori.txt";
		$fn_snapl  = _fn( 'pdb_snap', $id, "l$movid" );
		$fn_snaps  = _fn( 'pdb_snap', $id, "s$movid" );
		$fn_snapss = _fn( 'pdb_snap', $id, "ss$movid" );
		$fn_mp4	   = _fn( 'pdb_mp4' , $id, $movid );
		$fn_script = "$dn_pre/script.txt"; //- スクリプト
		$dn_frame  = "$dn_entry/img$movid"; //- ムービーフレームフォルダ
		$fn_frame_last = "$dn_frame/img00834.jpeg";

		$msg = "$id: #$movid";

		//... やるかやらないか
		//- 最終フレームか、ムービーがあれば、作らなくていい
		//- 最終フレーム画像があったら、とにかくやらない

		if ( file_exists( $fn_frame_last ) ) {
//			_m( '最終フレームあり' );
			continue;
		}
		if ( is_dir( $dn_frame ) ) {
			_problem( "$msg: ムービーフレーム画像 作りかけ" );
			continue;
		}

		if ( ! file_exists( $fn_orient ) ) {
			if ( $str_orient == '' )
				continue;
			else {
				file_put_contents( $fn_orient, $str_orient );
				_m( "$id:$fn_orient" );
			}
		}

		if ( in_array( $id, $redoids ) )
			_del( $fn_snaps );

		if (
			file_exists( $fn_snaps ) && 
			! file_exists( $fn_mp4 ) &&
			! file_exists( $fn_frame_last ) 
		) {
			if ( MODE_DEL ) {
				_m( 'ムービーファイルがないので、スナップショットを削除' );
				_del( $fn_snaps );
			} else {
				_problem( "$msg: スナップショットがあるのに、ムービーがない" );
			}
		}

//		_pause( $dn_frame );

/*
		if ( _newer( $fn_snaps, $fn_script ) ) {
			_m( "$msg: スクリプトが古い" );
			continue;
		}
*/
		if ( FLG_IGNORE_OLD && file_exists( $fn_snaps ) ) //- 更新データは無視モード
			continue;

		//- 方向ファイルの更新？
		if ( file_exists( $fn_snaps ) && ! _newer( $fn_snaps, $fn_orient ) ) {
			++ $cnt_newori;
			_m( "$msg: 方向ファイル指定が新しい, $cnt_newori" );
			_del( $fn_snaps );
		}
		if ( file_exists( $fn_snaps ) )
			continue;

		if ( _proc( "record-movie-$msg" ) ) continue;
		
		//.. ムービー用画像シリーズ
		_line( 'ムービー用画像作成開始', "$id-$movid" );


		exec( "rm -rf $dn_frame" );
		mkdir( $dn_frame );
		chdir( $dn_frame );
		
		//- _mng で指定された方向取得
		$o = _json_load2( _fn( 'orient_json', $id, $movid ) )
			->{ file_get_contents( $fn_orient ) }
			->script
		;
		//- test
		_m( 
			file_get_contents( $fn_script ) 
			. $o 
			. JMS_ZSHADE
			. MOVCMD
		);

		_jmol( file_get_contents( $fn_script ) 
			. $o 
			. JMS_ZSHADE
			. MOVCMD 
		);

		//.. スナップショット画像
		//- スナップショットの方向
		$num =
			ANG_NAME2NUM[ _mng_conf( 'img_angle', $id ) ]
			?: ANG_NAME2NUM[ _mng_conf( 'img_angle',
				$movid == 'sp2'
					? "$id-sp"
					: "$id-$movid" 
			)]
			?: '00000'
		;

		_mov_snaps( $dn_entry, $movid, $num );
		_proc();
		_m( "$msg: 完了" );
	}
}

_end();
