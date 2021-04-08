omo-pdb-3: DBへロード

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

$mode = 'full';
if ( in_array( 's' , $argv ) ) $mode = 's';
if ( in_array( 'ss', $argv ) ) $mode = 'ss';

define( 'MODE', $mode );
_line( "mode = " . MODE );

$dbfn	= DN_DATA . "/profdb_b2_$mode.sqlite";
$timefn	= DN_OMODATA . "/datatime_b2_$mode.json.gz";
$dtime	= _json_load( $timefn );
$dbtime	= file_exists( $dbfn ) ? filemtime( $dbfn ) : 0;

define( 'Q', '"' );
define( 'C', ',' );
define( 'B', '|' );

$ex = [];
$num_load = 0;

//. データベース 準備

$db = new PDO( "sqlite:$dbfn", '', '' );
$db->beginTransaction();

//.. テーブルがなければ作成
$res = $db->query( 
	"select name from 'sqlite_master' where type='table' and name='main'" ) ;

if ( $res->fetch() == '' ) {
	$columns = implode( ',', MODE == 'ss'
		? [
			'id	UNIQUE' ,
			'data	BLOB' ,
			'pca1	REAL' ,
			'pca2	REAL' ,
			'pca3	REAL'
		]
		: [
			'id	UNIQUE' ,
			'data	BLOB'
		]
	);

	$res = $db->query( "CREATE TABLE main( $columns )" );
	_m( "新DBファイル作成" );
}

//. main
//$cnt = 0;
_line( 'PDB ロード' );
foreach ( glob( _fn( 'prof_pdb', '*' ) ) as $fn ) {
	$id = basename( $fn, '.txt' );
	_readload( $fn, $id );
//	++ $cnt;
//	if ( $cnt > 1000 ) break;
}

//. main emdb
_line( 'EMDB ロード' );
foreach ( glob( _fn( 'prof_emdb', '*' )  ) as $fn ) {
	$id = 'e' . basename( $fn, '.txt' );
	_readload( $fn, $id );
}

//. main emdb
_line( 'SAS-model ロード' );
foreach ( glob( _fn( 'prof_sas', '*' )  ) as $fn ) {
	$id = 's' . basename( $fn, '.txt' );
	_readload( $fn, $id );
}

//. 廃止データ 消去

_line( '廃止データ 消去' );

//- DB中のID一覧取得
$ids = $db
	->query( "SELECT id FROM main" )
	->fetchAll( PDO::FETCH_COLUMN, 0 )
;

//- 無いデータは削除
foreach ( $ids as $id ) {
	if ( $ex[ $id ] ) continue;
	$res = $db->query( "DELETE FROM main WHERE id = '$id'" );
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id: エラー: " . print_r( $er, 1 ) );
	else
		_m( "$id: 削除しました。" );
}

//- 登録構造と同じアセンブリー構造を削除
foreach ( _file( DN_PREP . "/ids_identasb.txt" ) as $id ) {
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
$res = $db->query( MODE == 'ss'
	? "CREATE INDEX idx ON main(pca1, pca2, pca3)"
	: "CREATE INDEX idx ON main(id)"
);
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
//.. _readload
function _readload( $fn, $id ) {
	global $db, $dtime, $ex, $num_load;
	$ex[ $id ] = true;
	$time = filemtime( $fn );

	//- タイムスタンプ比較
	if ( $dtime[ $id ] == $time ) return;

	//... 読み取り
	$bin ='';
	$f = _file( $fn );
	foreach (
		explode( C,
			_sh( $f[0] ) .C. _sh( $f[1] ) .C. _sh( $f[2] ) .C. $f[3] .C. $f[4] .C. $f[5]
		)
	as $val )
		$bin .= pack( 'f', $val );
	;
//	$tempfn = _tempfn( 'bin' );
//	file_put_contents( $tempfn, $bin );
//	$fp = fopen( $tempfn, 'rb' );

/*
	$data = Q . $id . Q . C
		. Q . base64_encode( $bin ) . Q
		. ( MODE == 'ss'
			? C . $f[3] . C . $f[4] . C . $f[5] : ''
		)
	;
*/
	//... write

	$stmt = $db->prepare(
//	die(
		"insert into main values "
		. ( MODE == 'ss' ? "(?, ?, ?, ?, ?)" : "(?, ?)" )
	);
	$stmt->bindParam( 1, $id );
	$stmt->bindParam( 2, $bin );
	if ( MODE == 'ss' ) {
		$stmt->bindParam( 3, $f[3] );
		$stmt->bindParam( 4, $f[4] );
		$stmt->bindParam( 5, $f[5] );
	}
	$stmt->execute();
//	$res = $db->query( "INSERT OR REPLACE INTO main VALUES ($data)" );
	$er = $db->errorInfo();
	if ( $er[0] != '00000' ) {
		_m( "$id ロードエラー: " . print_r( $er, 1 ) );
		die( $d );
	} else {
		if ( $num_load < 100 ) {
			_m( "$id: ロード成功" );
			++ $num_load;
		} else if ( $num_load == 100 ) {
			_m( "以降、メッセージ省略" );
			++ $num_load;
		}
		$dtime[ $id ] = $time; 
	}
	_count( 1000 );
}

//.. _sh: シュリンク
function _sh( $in ) {
	if ( MODE == 'full' )
		return $in;

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
