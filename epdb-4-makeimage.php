<?php
//. misc init
require_once( "commonlib.php" );
$asb_json = _json_load( FN_ASB_JSON );

$a = _tsv_load2( DN_EDIT. '/pdb_movie_param.tsv' );
define( 'CONF_STYLE'	, $a['style']   );
define( 'CONF_ROTATION'	, $a['rotation']);
define( 'CONF_COLOR'	, $a['color']   );
define( 'CONF_SP'		, $a['sp_mov'] );
define( 'CONF_AS_EMDB'	, $a['as_emdb'] );

define( 'FIT_JSON'		, _json_load( DN_PREP. '/fit_confirmed.json.gz' ) );

$blist = new cls_blist( 'epdb_img' );

//. add fn
$_filenames += [
	'dn_pre'      => DN_PDB_MED. '/<id>/pre_<s1>' ,
	'img_pre'     => DN_PDB_MED. '/<id>/pre_<s1>/10.jpg',
	'orient_json' => DN_PDB_MED. '/<id>/pre_<s1>/orient.json' ,
	'matrix_json' => DN_EMDB_MED. '/<id>/ym/matrix.json'
];

//. jmolcmd
//.. 基本
define( 'JMS_1', <<<EOD
set UNDO off;
set background white;
set ambientPercent 20;
set diffusePercent 40;
set specular ON;
set specularPower 80;
set specularExponent 2;
set specularPercent 70;
set antialiasDisplay ON;
set ribbonBorder ON;
set imageState OFF;
frank OFF;
select all;
EOD
);

define( 'JMS_2', <<<EOD
select hetero; wireframe 0.5; spacefill 50%; color CPK;
hide water;
select all;
slab off; set zshade on; set zshadepower 1; slab 60;
EOD
);

define( 'JMS_LOAD_SURF', <<<EOD
isosurface s1 file "<<1>>/ym/o1.zip|o1.jvxl";
isosurface s1 translucent 0.70 [xffffff];
backbone only; backbone 300;
EOD
);

//.. 色・スタイル
define( 'JMS_BASE'		, 'cartoon ONLY; color chain;' );
define( 'JMS_BASE_BB'	, 'backbone 100; backbone only; color chain;' );

define( 'JMS_ST', [
	'trace'		=> 'trace ONLY; trace 300;' , 
	'bb'		=> 'backbone 100; backbone only;' , 
	'ball_rna'	=> 'select rna; cpk 250; select all;' ,
]);

define( 'JMS_COL_MONO'	, 'color monomer;' );
define( 'JMS_COL_MOL'	, 'color molecule' );
define( 'JMS_COL_GRP'	, 'color group;' );
define( 'JMS_COL_CHAIN'	, 'color chain;' );

define( 'JMS_FILT_BB'	, '*.CA,*.P,![ca],![HETATOM]' );

define( 'JMS_SPLIT'		, <<<EOD
model all; center all;
select !1.1; color white; color translucent 0.9;
select all;
EOD
);

//.. 回転
define( 'JMS_ROTATION', <<<EOD
reset;
r=[[<<1>>],[<<2>>],[<<3>>]];
rq=quaternion(r);
t={ <<4>> };
rotateSelected MOLECULAR @rq; translateSelected @t;
center all;
EOD
);

define( 'JMS_ROT_BEST', 'rotate best; rotate z 90;' );

define( 'ROT_CMD_DIC', [
	'x90'	=> 'rotate x 90;',
	'x270'	=> 'rotate x 270;',
	'y90'	=> 'rotate y 90;',
	'y270'	=> 'rotate y 270;',
	'z90'	=> 'rotate z 90;',
	'z180'	=> 'rotate z 180;',
	'z270'	=> 'rotate z 270;',
	'icos1' => 'rotate x 31.7174744114610053;',
	'icos2' => 'rotate x 121.7174744114610053;',
	'best'	=> JMS_ROT_BEST,
]);

//.. 撮影スクリプト
define( 'JMS_SAVEIMG', 'write image jpg 90 "<<1>>.jpg"' );


define( 'JMS_6ORI', _imp_scr(
	_scr_rep( JMS_SAVEIMG, 10 ) ,
	'rotate y 90' ,
	_scr_rep( JMS_SAVEIMG, 14 ) ,
	'rotate y 90;' ,
	_scr_rep( JMS_SAVEIMG, 13 ) ,
	'rotate y 90;' ,
	_scr_rep( JMS_SAVEIMG, 15 ) ,
	'rotate y 90;' ,
	'rotate x 90;' ,
	_scr_rep( JMS_SAVEIMG, 12 ) ,
	'rotate x 180;' ,
	_scr_rep( JMS_SAVEIMG, 11 ) ,
	JMS_ROT_BEST ,
	_scr_rep( JMS_SAVEIMG, 16 ) 
));

define( 'ORIENT_JSON_BASE', [
	10 => [ 'name' => '前' , 'script' => ''] ,
	14 => [ 'name' => '左' , 'script' => 'rotate y 90;'  ] ,
	13 => [ 'name' => '後' , 'script' => 'rotate y 180;' ] ,
	15 => [ 'name' => '右' , 'script' => 'rotate y 270;' ] ,
	12 => [ 'name' => '下' , 'script' => 'rotate x 90;'  ] ,
	11 => [ 'name' => '上' , 'script' => 'rotate x 270;' ] ,
	16 => [ 'name' => 'best' , 'script' => JMS_ROT_BEST ] ,
]);                                      

/*
write image jpg 90 "0.jpg";
rotate y 90;
write image jpg 90 "4.jpg";
rotate y 90;
write image jpg 90 "3.jpg";
rotate y 90;
write image jpg 90 "5.jpg";
rotate y 90;
rotate x 90;
write image jpg 90 "2.jpg";
rotate x 180;
write image jpg 90 "1.jpg";

	0 => '',
	4 => 'rotate y 90;',
	3 => 'rotate y 180;',
	5 => 'rotate y 270;',
	2 => 'rotate x 90;',
	1 => 'rotate x 270;',


EOD
*/


define( 'JMS_FIT_FOCUS_MAP', <<<EOD
load APPEND "<<1>>/ym/pg1.pdb";
model all;
selectionHalos on; color SELECTIONHALOS yellow; select 1.;
EOD
);

define( 'JMS_FIT_ZOOM1', 'center 2.;zoom 120;' );
define( 'JMS_FIT_ZOOM2', 'center 2.;zoom 150;' );
define( 'JMS_FIT_IMG0' , _scr_rep( JMS_SAVEIMG, 10 ) );
define( 'JMS_FIT_IMG1' , _imp_scr( JMS_FIT_ZOOM1, _scr_rep( JMS_SAVEIMG, 11 ) ) );
define( 'JMS_FIT_IMG2' , _imp_scr( JMS_FIT_ZOOM2, _scr_rep( JMS_SAVEIMG, 12 ) ) );

//. main loop
foreach ( _idlist( 'epdb' ) as $pdb_id ) {
	_count( 'epdb' );

	$orient_script = '';
	$orient_json = [];

	$dn_ent = _fn( 'pdb_med', $pdb_id );
	$fn_pdb = _fn( 'pdb_mmcif', $pdb_id );
	$fit_info = (array)FIT_JSON[ "pdb-$pdb_id" ];
	if ( CONF_AS_EMDB[ $pdb_id ] )
		$fit_info[] = CONF_AS_EMDB[ $pdb_id ]; //- 特定のEMDBエントリに方向を合わせる
	_mkdir( $dn_ent ); //- ディレクトリなければ作る

	//.. deposited
	$dn_pre = _fn( 'dn_pre', $pdb_id, 'dep' );

	if ( _chkdir( $pdb_id, 'dep' ) ) {
		_jmolpre([
			_jm_load( $fn_pdb ),
			'model all' ,
			JMS_BASE ,
			//- チェーン数１なら虹色
			_inlist( $pdb_id, 'multic' ) ? '' : JMS_COL_MONO ,

			//- スタイル指定？
			CONF_STYLE[ $pdb_id ] ? JMS_ST[ CONF_STYLE[ $pdb_id ] ] : '', 

			//- あらかじめ回す指定？
			ROT_CMD_DIC[ CONF_ROTATION[ "$pdb_id-dep" ] ]
		], _prep_orient_info( $pdb_id, 'dep' ) );
		_check_result( $pdb_id, 'dep' );
	}

	//.. assembly
	$asb_list = $asb_json[ $pdb_id ];
	if ( $asb_list ) {
		//... assembly 初期処理
		//- スタイル 共通
		$jms_style = '';
		if ( CONF_STYLE[ $pdb_id ] )
			$jms_style = JMS_ST[ CONF_STYLE[ $pdb_id ] ];
		$jms_style = '';
		if ( CONF_STYLE[ "$pdb_id-a" ] )
			$jms_style = JMS_ST[ CONF_STYLE[ "$pdb_id-a" ] ];

		//- 色 共通
		$jms_grp  = CONF_COLOR[ "$pdb_id-a" ] == 'grp' ? JMS_COL_GRP : '' ;

		//- split 調査
		$ids_split    = $asb_list[ 'sp'  ];
		$ids_split2   = $asb_list[ 'sp2' ];

		//- slipt x bm 用、同じassembly-IDシリーズじゃないものは却下
		$ids_split_bm = [];
		$k = array_keys( $asb_list );
		$a = CONF_SP[ $pdb_id ] == 'sp2' || !$ids_split ? $ids_split2 : $ids_split;
		foreach ( (array)$a as $i ) {
			if ( !$i ) continue;
			if ( $k != array_keys( (array)$asb_json[ $i ] ) ) continue;
			$ids_split_bm[] = $i;
		}
//		_pause( $ids_split_bm );
	}

	foreach ( (array)$asb_list as $asb_id => $asb_info ) {
		//... assembly 毎回共通処理
		if ( ! _chkdir( $pdb_id, $asb_id ) ) continue;

		//- style: 5000以上のオリゴマーなら、cartoonじゃなくてbackbone
		$jms_base = $asb_info[ 'num' ] > 5000 ? JMS_BASE_BB : JMS_BASE;

		//- icosの部分構造など、best方向へ
		$jms_prerot = in_array( $asb_info[ 'type' ], [
			'icosahedral pentamer' ,
			'icosahedral 23 hexamer'
		])
			? JMS_ROT_BEST
			: ROT_CMD_DIC[ CONF_ROTATION[ "$pdb_id-$asb_id" ] ]
		;

		//... split
		if ( $asb_id == 'sp' ) {
			_line( "Split 画像作成", $pdb_id );

			$filter = [
				count( (array)$pdb_ids_split ) > 6 ? JMS_FILT_BB: '', //- 大きすぎる奴は、主鎖のみ
				CONF_SP[ $pdb_id ] == 'sp-bm' ? 'biomolecule 1' : '',
			];

			_jmolpre([
				_jm_load( $fn_pdb, $filter, 1 ) ,
				_jm_load_split( $ids_split, $filter ) ,
				$jms_base, $jms_style, JMS_SPLIT, $jms_grp, $jms_prerot
			], _prep_orient_info( $pdb_id, $asb_id ) );

		//... split2
		} else if ( $asb_id == 'sp2' ) {
			_line( "Split 2 画像作成", $pdb_id );

			$filter = [
				count( $ids_split2 ) > 6 ? JMS_FILT_BB : '', //- 大きすぎる奴は、主鎖のみ
				CONF_SP[ $pdb_id ] == 'sp-bm' ? 'biomolecule 1' : '',					
			];

			_jmolpre([
				_jm_load( $fn_pdb, $filter, 1 ) ,
				_jm_load_split( $ids_split2, $filter ) ,
				$jms_base, $jms_style, JMS_SPLIT, $jms_grp, $jms_prerot
			], _prep_orient_info( $pdb_id, $asb_id ) );
		
		//... biomol x split
		//- biomol も splitペアのデータと一緒に作画
		} else if ( $ids_split_bm ) {
			_line( "split x biomol 画像作成", $pdb_id . ': ' . _imp( $ids_split_bm ) );

			//- 大きすぎる奴は、主鎖のみ
			$filter = [
				$asb_info[ 'num' ] * ( count( $ids_split_bm ) +1) > 50 ? JMS_FILT_BB : '', 
				"biomolecule $asb_id" ,					
			];

			_jmolpre([
				_jm_load( $fn_pdb, $filter, 1 ) ,
				_jm_load_split( $ids_split_bm, $filter ) ,
				$jms_base, $jms_style, JMS_COL_MOL, JMS_SPLIT, $jms_grp, $jms_prerot
			], _prep_orient_info( $pdb_id, $asb_id ) );

		//... biomol
		} else {
			_line( "biomol 画像作成", "$pdb_id - $asb_id" );
			$filter = 
				$asb_info[ 'type' ] == 'icos' ||
				$asb_info[ 'type' ] == 'comp' ||  
				$asb_info[ 'num' ] > 50 ||
				CONF_STYLE[ "$pdb_id-a" ] == 'bb'
			? JMS_FILT_BB : '';

			_jmolpre([
				_jm_load( $fn_pdb, [ $filter, "biomolecule $asb_id" ], 1 ), 
				$jms_base, JMS_COL_MOL, $jms_grp, $jms_prerot
			], _prep_orient_info( $pdb_id, $asb_id ) );
		}

		//... チェック
		_check_result( $pdb_id, $asb_id );
	}

	//.. Jmol fit
	foreach ( (array)$fit_info as $eid ) {
		$eid = _numonly( $eid ); //- emdb-xxxxで受け取るので
		if ( $blist->inc( "$eid-$pdb_id" ) ) continue;

		if ( ! _emn_json( 'status', "emdb-$eid" )->pg1 ) continue;
		if ( ! file_exists( $fn_mtx = _fn( 'matrix_json', $eid ) ) ) continue;

		if ( ! _chkdir( $pdb_id, "jm$eid", [ _fn( 'mapgz', $eid ) ] ) ) continue;

		//- どのアセンブリを当てはめるか
		$filter = [ JMS_FILT_BB ];
		$main_img = _mng_conf( 'pdb_main_mov', $pdb_id );
		if ( $asb_list[1] && $main_img != 'dep' ) {
			$i = $main_img ?: $asb_list[1][ 'name' ] ?: 1;
			$filter = [ JMS_FILT_BB, "biomolecule $i" ];
		}

		//- script / orient_json
		$jms_focus = _scr_rep( JMS_FIT_FOCUS_MAP, _fn( 'emdb_med', $eid ) );
		_json_save( _fn( 'orient_json', $pdb_id, "jm$eid" ), [
			10 => [
				'name' => "fit $eid" ,
				'script' => '' ,
			] ,
			11 => [
				'name' => "zoom 120" ,
				'script' => _imp_scr( $jms_focus, JMS_FIT_ZOOM1 ),
			] ,
			12 => [
				'name' => "zoom 150" ,
				'script' => _imp_scr( $jms_focus, JMS_FIT_ZOOM2 ) ,
			]
		]);
		
		//- rotation matrix
		$mtx = _json_load( $fn_mtx );

		//- jmol実行
		_jmolpre([
			_jm_load( $fn_pdb, $filter, 1 ) ,
			'select all' ,
			_scr_rep( JMS_ROTATION, $mtx[1], $mtx[2], $mtx[3], $mtx[4] ) ,
			_scr_rep( JMS_LOAD_SURF, _fn( 'emdb_med', $eid ) ) ,
			CONF_COLOR[ "$pdb_id-a" ] == 'grp' ? JMS_COL_GRP : JMS_COL_CHAIN
		],
			_imp_scr( JMS_FIT_IMG0, $jms_focus, JMS_FIT_IMG1, JMS_FIT_IMG2 )
		);
		_check_result( $pdb_id, "jm$eid" );
	}
}

//. なくなったデータを検出、削除
$fit_annot = [];
foreach ( _file( DN_EDIT. '/fit_annot.tsv' ) as $line )
	$fit_annot[ explode( "\t", $line )[0] ] = true;

foreach ( _idloop( 'pdb_med', '不要ディレクトリ調査' ) as $dn ) {
	$pdb_id = basename( $dn );
	//.. エントリディレクトリ
	if ( ! _inlist( $pdb_id, 'epdb' ) ) {
		if ( $argar[ 'delete' ] ) {
			exec( "rm -rf $dn" );
			_log( "$pdb_id: メディアディレクトリを削除" );
		} else {
			_problem( "$pdb_id: エントリディレクトリを削除すべき (delete=1 オプションで自動削除)" );
		}
		continue;
	}

	//.. preディレクトリ
	foreach ( glob( "$dn/pre_*" ) as $dn_pre ) {
		$mov_id = strtr( basename( $dn_pre ), [ 'pre_' => '' ] );
		if ( $mov_id == 'dep' ) continue;
		if ( $asb_json[ $pdb_id ][ $mov_id ] ) continue;
		$emdb_id = _numonly( $mov_id );
		if ( in_array( "emdb-$emdb_id", (array)FIT_JSON[ "pdb-$pdb_id" ] ) ) 
			continue;
		if ( $fit_annot[ "$emdb_id-$pdb_id" ] )
			continue;

		if ( $argar[ 'delete' ] ) {
			_delmov( $pdb_id, $mov_id, '不要ムービーになった' );
			exec( "rm -rf $dn_pre" );
			_log( "$pdb_id: ディレクトリ $mov_id を削除" ); 
		} else {
			_problem( "$pdb_id: preディレクトリ $mov_id を削除すべき (delete=1 オプションで自動削除)" );
		}
		continue;
	}

	//.. del_movフラグ
	foreach ( glob( "$dn/pre_*" ) as $dn_pre ) {
		$n = "$dn_pre/del_mov" ;
		if ( ! file_exists( $n ) ) continue;
		_del( $n );
		$mov_id = strtr( basename( $dn_pre ), [ 'pre_' => '' ] );
		_delmov( $pdb_id, $mov_id, '削除指定により' );
	}
}
_end();

//. function Jmol スクリプト操作

//.. _jmolpre Jmol実行して、プレ画像を作成
//- プレビュー用 jmol
function _jmolpre( $jms_main, $jms_imaging ) {
	//- 本番にも使うコマンド
	$cmd = _imp_scr( JMS_1, _imp_scr( $jms_main ), JMS_2 ); 
	file_put_contents( 'script.txt', $cmd );

	//- プレビュー画像作成
//	_pause( _imp_scr( $cmd  ) ); 
//	_pause( _imp_scr( $jms_imaging  ) ); 
	
	_jmol( _imp_scr( $cmd, $jms_imaging ), 200 );
	return;
}

//.. _impc コマンドをつなげる
//- 単純にセミコロンと改行でつなげる、単に見やすくするのみ
function _imp_scr() {
	$ar = [];
	foreach ( func_get_args() as $a ) {
		if ( is_array( $a ) ) 
			$ar = array_merge( $ar, $a );
		else
			$ar[] = $a;
	}
	return strtr( implode( ";\n", array_filter( $ar ) ), [';;' => ';'] );
}

//.. _jm_load
//- ロードスクリプト作成
function _jm_load( $fn, $filter = [], $model_num = '', $flg_append = false ) {
	$filter = array_filter( $filter );
	return implode( ' ', array_filter([
		'load',
		$flg_append ? 'APPEND' : '' ,
		"\"$fn\"" ,
		$model_num ,
		$filter ? 'FILTER "'. implode( ', ', $filter ) .'"' : ''
	]) );
}

//.. _jm_load_split
function _jm_load_split( $ids, $filter ) {
	$s = [];
	foreach ( (array)$ids as $i )
		$s[] = _jm_load( _fn( 'pdb_mmcif', $i ), $filter, 1, true );
	return _imp_scr( $s );
}

//.. _scr_rep
//- スクリプト文字列のテンプレート文字入れ替え
//- 最初の引数がテンプレート、あとは1から順番にパラメータ
function _scr_rep() {
	$rep_ar = [];
	foreach ( func_get_args() as $num => $arg ) {
		if ( $num == 0 )
			$template = $arg;
		else
			$rep_ar[ "<<$num>>" ] = $arg;
	}
	return strtr( $template, $rep_ar );
}

//.. _prep_orient_info 方向のデータ処理
//- 方向のスクリプトとデータを作成
//- em fit の方向を計算、スクリプトにして返す
//- orinet_json保存

function _prep_orient_info( $pdb_id, $asb_id ) {
	global $fit_info, $orient_json, $orient_script; 
	//- 各ID 初回のみ実行
	if ( ! $orient_script ) {
		$orient_json = ORIENT_JSON_BASE;
		$scr = [ JMS_6ORI ];
		$num = 20;
		foreach ( (array)$fit_info as $eid ) {
			if ( ! file_exists( $fn = _fn( 'matrix_json', $eid ) ) ) {
				_m( "$eid から方向を取得できない" );
				continue;
			}
			$mtx = _json_load( $fn );
			$rot = _scr_rep( JMS_ROTATION, $mtx[1], $mtx[2], $mtx[3], $mtx[4] );
			$orient_json[ $num ] = [
				'name' => "fit to $eid" ,
				'script' => $rot
			];
			$scr[] = _imp_scr( $rot, _scr_rep( JMS_SAVEIMG, $num ) );
			++ $num;
			_m( "$eid から方向を取得" );
		}
		$orient_script = _imp_scr( $scr );
	}
	_json_save( _fn( 'orient_json', $pdb_id, $asb_id ), $orient_json );
	
	return $orient_script;
}

//. function 実行管理
//.. chkdir 前処理
//- ディレクトリチェック
//- 	削除指定、redo指定、利用ファイルの更新をチェック
//- 作業しなくてもいいなら、falseを返す
//- すべきなら
//- 	古いファイルを削除、メッセージ、ログ出力、_proc登録
//- 	ディレクトリ作成、カレントディレクトリ移動
//- 	trueを返す

function _chkdir( $pdb_id, $asb_id, $fns_src = [] ) {
	global $fit_info;
	//... init
	$dn = _fn( 'dn_pre', $pdb_id, $asb_id );
	$fn_out = _fn( 'img_pre', $pdb_id, $asb_id );
	$cause = [];
	$msg = "$pdb_id: #$asb_id:";

	//... やるかやらないか
	//- 新規ディレクトリ
	if ( ! is_dir( $dn ) )
		$cause[] = "新規データ" ;

	//- 削除指定
	if ( file_exists( "$dn/del" ) )
		$cause[] = "再作成指定" ;
	//- redo指定
	if ( FLG_REDO ) 
		$cause[] = "redo指定" ;

	//- マトリックデータ更新
	foreach ( (array)$fit_info as $eid ) {
		if ( _newer( $fn_out, _fn( 'matrix_json', $eid ) ) ) continue;
		$cause[] = "$eid のマトリックス更新";
	}

	//- 併用するファイル
	foreach ( (array)$fns_src as $fn ) {
		if ( _newer( $fn_out, $fn ) ) continue;
		$cause[] = basename( $fn ) . " の更新";
	}

	//- mmCIF
	if ( ! _newer( $fn_out, _fn( 'pdb_mmcif', $pdb_id ) ) )
		$couse[] = "mmCIFファイル更新";

	if ( !$cause ) return false;

	//- テスト
/*
	if ( $cause )
		_m( '作成理由: ' . _imp( $cause ) );
	if ( is_dir( $dn ) ) {
		_pause( "$msg 再作成することになったけど良いか？" ); //---------------
	} else {
		_pause( "$msg 新規作成することになったけど良いか？" ); //---------------
	}
*/
	if ( _proc( "$pdb_id-$asb_id-cand-img" ) ) return false;

	//... ディレクトリ準備
	_line( "$msg 候補画像作成" );
	if ( is_dir( $dn ) ) {
		_log( "$msg 候補画像 再作成の理由 - " . _imp( $cause ) );
		exec( "rm -rf $dn" );
	} else {
		_log( "$msg 候補画像用 ディレクトリ新規作成" );
	}
	mkdir( $dn );
	chmod( $dn, 0777 );
	chdir( $dn );
	return true;
}

//.. _check_result 後処理
//- 画像ができているかチェックして、メッセージ
//- proc解除も行う
function _check_result( $pdb_id, $asb_id ) {
	$msg = "$pdb_id: #$asb_id: 候補画像作成 -" ;
	if ( file_exists( _fn( 'img_pre', $pdb_id, $asb_id ) ) )
		_log( "$msg 完了" );
	else
		_problem( "$msg 失敗" );
	_proc();
}

//.. _delmov 動画削除
function _delmov( $pdb_id, $mov_id, $reason = false) {
	$num = _del(
		_fn( 'pdb_snap', $pdb_id, "l$mov_id" ) ,
		_fn( 'pdb_snap', $pdb_id, "s$mov_id" ) ,
		_fn( 'pdb_snap', $pdb_id, "ss$mov_id" ) ,
		_fn( 'pdb_mp4' , $pdb_id, "$mov_id" ) ,
		_fn( 'pdb_mp4' , $pdb_id, "s$mov_id" ) ,
		_fn( 'pdb_webm', $pdb_id, "$mov_id" ) ,
		_fn( 'pdb_webm', $pdb_id, "s$mov_id" ) 
	);
	if ( $num ) {
		_log(
			"$pdb_id: movie #$mov_id $num 個のメディアファイルを削除" 
			. ( $reason ? " (理由: $reson)": '' )
		);
	}
}

