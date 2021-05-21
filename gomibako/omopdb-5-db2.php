omo-pdb-3: DBへロード

<?php

//. init
require_once( "commonlib.php" );
define( 'MODE',  $argv[1] == 'ss' ? 'ss' : 's' );
$mode = MODE;
_m( "mode = " . MODE );

$vqdn	= '/novdisk2/db/omopdb/vq';
$errdn	= '/novdisk2/db/omopdb/error';
$profdn	= '/novdisk2/db/omopdb/prof';
$dbfn	= "/novdisk2/db/omopdb/profdb_$mode.sqlite";
$timefn	= "/novdisk2/db/omopdb/datatime_$mode.json.gz";
$dtime = _json_load( $timefn );

//. データベース 準備

$db = new PDO( "sqlite:$dbfn", '', '' );
$db->beginTransaction();

//.. テーブルがなければ作成
$res = $db->query( 
	"select name from 'sqlite_master' where type='table' and name='main'" ) ;

if ( $res->fetch() == '' ) {
	$columns = implode( ',', [
		'id	UNIQUE' ,
		'data' ,
		'pca1	REAL' ,
		'pca2	REAL' ,
		'pca3	REAL'
	] );

	$res = $db->query( "CREATE TABLE main( $columns )" );
	_m( "新DBファイル作成" );
}

//. main

define( 'Q', '"' );
define( 'C', ',' );
define( 'B', '|' );

$dbtime = filemtime( $dbfn );

foreach ( glob( "$profdn/*.txt" ) as $fn ) {
	$id = basename( $fn, '.txt' );
	$time = filemtime( $fn );

	//- タイムスタンプ比較
	if ( $dtime[ $id ] == $time ) continue;

	//.. 読み取り
//	$data = [];
	$f = _file( $fn );
	$d = _sh( $f[0] ) .B. _sh( $f[1] ) .B. _sh( $f[2] ) .B. $f[3] .C. $f[4] .C. $f[5];
	
	$data = Q . $id . Q . C
//		. Q . strtr( gzencode( $d ), [ '"' => '""' ] ) . Q . C
		. Q . $d . Q . C
		. $f[3] . C
		. $f[4] . C
		. $f[5]
	;
	
//	_m( "[$id=====================]\n"
//		. strtr( gzencode( $d ), [ '"' => '""' ] ) 
//		. "[=====================]"
//	);
//	_m( $data );

	//.. write

	$res = $db->query( "INSERT OR REPLACE INTO main VALUES ($data)" );//->fetchAll();
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id ロードエラー: " . print_r( $er, 1 ) );
	else {
		_m( "$id: ロード成功" );
		$dtime[ $id ] = $time; 
	}

//	if ( _count( 5000, 0 ) ) break;
}

//. main emdb
_line( 'EMDBデータ' );

$emdbdata = [];
foreach ( $emdbidlist as $id ) {
//	if ( $id != 5001 ) continue;
	$eid = "e$id";

	//- プロファイルが全部あるか
	$fn = [
		'30'  => _fn( 'prof30' , "emdb-$id" ) ,
		'50'  => _fn( 'prof50' , "emdb-$id" ) ,
		'out' => _fn( 'profout', "emdb-$id" ) ,
		'pca' => _fn( 'profpca', "emdb-$id" )
	];
	$f = 0;
	foreach ( $fn as $n ) {
		if ( file_exists( $n ) ) continue;
		$f = 1;
		break;
	}
	if ( $f ) continue;

	$emdbdata[] = $eid;

	//- タイムスタンプ同じ?
	$time = filemtime( $fn[ '50' ] );
	if ( $dtime[ $eid ] == $time ) continue;

	//- ファイル読み込み
	$p = [];
	foreach ( $fn as $n => $f )
		$p[ $n ] = _file( $f );

	//- ロード
	$data = Q . $eid . Q . C
		. Q 
			. _sh( $p[ '30' ]  ) . B
			. _sh( $p[ '50' ]  ) . B
			. _sh( $p[ 'out' ] ) . B
			. implode( ',', $p[ 'pca' ] )
		. Q . C
		. $p[ 'pca' ][0] . C
		. $p[ 'pca' ][1] . C
		. $p[ 'pca' ][2]
	;

	$res = $db->query( "INSERT OR REPLACE INTO main VALUES ($data)" );
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id ロードエラー: " . print_r( $er, 1 ) );
	else {
		_m( "$id: ロード成功" );
		$dtime[ $eid ] = $time; 
	}
}

//. 廃止データ 消去

_line( '廃止データ 消去' );

//- 存在するPDB-ID
if ( count( $allpdbids ) < 10000 ) {
	$allpdbids = _file( DN_DATA  . '/allpdbids.txt' );
	_m( "IDリスト読み込み完了: " . count( $allpdbids ) );
}
$ex = [];
foreach ( $allpdbids as $i )
	$ex[ $i ] = true;

foreach ( $emdbdata as $i )
	$ex[ $i ] = true;

//- 
$ids = $db
	->query( "SELECT id FROM main" )
	->fetchAll( PDO::FETCH_COLUMN, 0 )
;

foreach ( $ids as $id ) {
	if ( substr( $id, 0, 1 ) == 'e' ) continue;
	if ( $ex[ substr( $id, 0, 4 ) ] ) continue;
	$res = $db->query( "DELETE FROM main WHERE id = '$id'" );
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id: エラー: " . print_r( $er, 1 ) );
	else
		_m( "$id: 削除しました。" );
}

foreach ( _file( DN_DATA . "/identasb.txt" ) as $id ) {
	$res = $db->query( "DELETE FROM main WHERE id = '$id'" );
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id: エラー: " . print_r( $er, 1 ) );
//	else
//		_m( "$id: 削除しました。" );
}

//. 後処理
_line( '後処理' );

if ( ! $_redo ) {
	echo "\nDBバキューム開始 ... ";
	$bac = $db->exec( "VACUUM" );
	echo "完了\n";
}

_m( "インデックス作成開始" );
$res = $db->query( "CREATE INDEX idx ON main(pca1, pca2, pca3)" );
$er = $db->errorInfo();
if ( $er[0] != '00000' )
	_m( "エラー: " . print_r( $er, 1 ) );
else
	_m( "完了" );

_m( 'DB コミット' );
$db->commit();
_m( '完了' );

//. data time保存
_m( 'タイムスタンプファイル保存開始' );
_json_save( $timefn, $dtime );
_m( '完了' );

//. func
function _sh( $in ) {
	if ( ! is_array( $in ) )
		$in = explode( ',', $in );
	$cnt = count( $in );

	//- 何個に一個残すか
	$step = 3;
	if ( $cnt > 400  ) $step = 4;
	if ( $cnt > 1200 ) $step = 12;
	if ( MODE == 'ss' ) $step *= 2;

	$out = [];
	$sum = 0;
	foreach ( $in as $i => $val ) {
		$sum += $val;
		if ( $i % $step > 0 ) continue;
		$out[] = $sum / $step;
		$sum = 0;
	}
	return implode( ',', $out );
}
