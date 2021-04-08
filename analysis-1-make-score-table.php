<?php
//- スコアテーブルを計算

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );
ini_set( "memory_limit", "8192M" );

//- 出力データ
define( 'FN_SIMTABLE_JSON', DN_PREP . "/simtable_lim.json.gz" );

//. distprofを読み込む
_line( "プロファイル読み込み" );
$status = _json_load2( FN_DBSTATUS );
$profdb = new cls_sqlite( 'profdb_s' );
define( 'SNAP2ASB', [
	'1'    => '1' ,
	'2'    => '2' ,
	'dep'  => 'd' ,
	'sp'   => 'd' ,
	'sp2'  => 'd' ,
]);
//$cnt = [];
$prof = [];
$time = [];
foreach ( _joblist() as $a ) {
	_count( 'both' );
	extract( $a ); //-$db, $id, $did
	$ida = $db == 'pdb'
		? $id . '-' . SNAP2ASB[ $status->$did->snap ]
		: "e$id"
	;

	$bin = $profdb->qcol([
		'select' => 'data',
		'where' => "id = \"$ida\"" 
	])[0];
	if ( $bin == '' ) continue;
	$prof[ $did ] = _bin2prof( $bin );

	$fn = 	$db == 'pdb'
		? _fn( 'prof_pdb', $ida )
		: _fn( 'prof_emdb', $id )
	;

	$time[ $did ] = file_exists( $fn ) 
		? filemtime( $fn )
		: 0
	;

	_cnt( 'total' );
	_cnt( $db );
}

unset( $status, $profdb );
_cnt();

//. 以前のデータ読み込み、消えたデータ消去
_count();
if ( FLG_REDO or ! file_exists( FN_SIMTABLE_JSON ) ) {
	_line( '全比較やり直し' );
	$table =[];
} else {
	//- プロファイルのあるデータ
	$ex = [];
	foreach ( array_keys( $prof ) as $did )
		$ex[ $did ] = true;

	//- 読み込み
	_line( '以前のデータ読み込み' );
	$table = _json_load( FN_SIMTABLE_JSON ); //- 既存のデータを読み込み
	$jsontime = filemtime( FN_SIMTABLE_JSON );

	//- なくなったデータ、古いデータ抽出
	_m( 'なくなったデータ、古いデータ抽出' );
	$to_del = [];
	$ids_obso = [];
	$ids_changed = [];

	foreach ( array_keys( $table ) as $did ) {
		if ( ! $ex[ $did ] ) {
			$to_del[] = $did;
			$ids_obso[] = $did;
		} else if ( $jsontime < $time[ $did ] ) {
			$to_del[] = $did;
			$ids_changed[] = $did;
		}
	}

	if ( count( $ids_obso ) > 0 )
		_m( "取り消し: " . _imp( $ids_obso ) );
	if ( count( $ids_changed ) > 0 )
		_m( "更新: " . _imp( $ids_changed ) );

	//- 列消し
	foreach ( (array)$to_del as $i )
		unset( $table[ $i ] );

	//- 行消し
	foreach ( array_keys( $table ) as $i1 ) {
		foreach ( (array)$to_del as $i2 ) {
			unset( $table[ $i1 ][ $i2 ] );
		}
	}
}


//. 総当り
_line( '総当り比較' );

$par = [];
$c = count( $prof );
_m( "データ数: $c, 総当り数: " . ( $c * ( $c - 1 ) ) / 2 );

foreach ( $prof as $id1 => $prof1 ) {
	_count( 'emdb' );
//	$table[ $id1 ][ $id1 ] = 1;

	//- 無視する長さ、デーテ数など取得
	if ( count( $par ) == 0 )
		$par = _count_ign( $prof1 ); // $count, $ign, $pnum

	foreach ( $prof as $id2 => $prof2 ) {
		if ( $id1 == $id2 ) continue;
		if ( $table[ $id2 ][ $id1 ] != '' ) continue;
		if ( ! FLG_REDO && ! in_array( $id1, $ids_changed ) && ! in_array( $id2, $ids_changed ) )
			continue;
		$score = _getscore( $prof1, $prof2, $par );
		if ( $score < 0.7 ) 
			unset( $table[ $id1 ][ $id2 ], $table[ $id2][ $id1 ] );
		else
			$table[ $id1 ][ $id2 ] = $table[ $id2][ $id1 ] = $score;
	}
}

_comp_save( FN_SIMTABLE_JSON, $table );


