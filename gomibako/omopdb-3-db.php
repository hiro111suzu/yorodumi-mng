omo-pdb-3: DBへロード

<?php

//. init
require_once( "commonlib.php" );

$vqdn	= '/novdisk2/db/omopdb/vq';
$errdn	= '/novdisk2/db/omopdb/error';
$profdn	= '/novdisk2/db/omopdb/prof';
$dbfn	= '/novdisk2/db/omopdb/profdb.sqlite';

//. データベース 準備
$db = new PDO( "sqlite:$dbfn", '', '' );
$db->beginTransaction();

//.. テーブルがなければ作成
$res = $db->query( 
	"select name from 'sqlite_master' where type='table' and name='main'" ) ;

if ( $res->fetch() == '' ) {
	$columns = implode( ',', [
		'id	UNIQUE' ,
		'time' ,
		'vq30' ,
		'vq50' ,
		'out' ,
		'pca1	REAL' ,
		'pca2	REAL' ,
		'pca3	REAL'
	] );

	$res = $db->query( "CREATE TABLE main( $columns )" );
	_m( "新DBファイル作成" );
}
define( 'Q', '"' );

//. main
//die();
foreach ( glob( "$profdn/*.txt" ) as $fn ) {
	$id = basename( $fn, '.txt' );
	$time = filemtime( $fn );

	//- タイムスタンプ比較
	$res = $db
		->query( "SELECT time FROM main WHERE id='$id'" )
		->fetchAll( PDO::FETCH_ASSOC )
	;
	if ( $res[0][ 'time' ] == $time ) continue;

	//.. 読み取り
	$data = [];
	foreach ( array_merge( [ $id, $time ], _file( $fn ) ) as $d )
		$data[] = "\"$d\"";
	$data = implode( ',', $data );

	//.. write
	$res = $db->query( "INSERT OR REPLACE INTO main VALUES ($data)" );//->fetchAll();
	$er = $db->errorInfo();
	if ( $er[0] != '00000' )
		_m( "$id ロードエラー: " . print_r( $er, 1 ) );
	else
		_m( "$id: ロード成功" );

	_count( 100 );
}

//. 廃止データ 消去

_m( '廃止データ 消去' );

//- 存在するPDB-ID
if ( count( $allpdbids ) < 10000 ) {
	$allpdbids = _file( DN_DATA  . '/allpdbids.txt' );
	_m( "IDリスト読み込み完了: " . count( $allpdbids ) );
}
$ex = [];
foreach ( $allpdbids as $i )
	$ex[ $i ] = true;

//- 
$ids = $db
	->query( "SELECT id FROM main" )
	->fetchAll( PDO::FETCH_COLUMN, 0 )
;

foreach ( $ids as $id ) {
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

//. バキューム
if ( ! $_redo ) {
	echo "\nDBバキューム開始 ... ";
	$bac = $db->exec( "VACUUM" );
	echo "完了\n";
}

//. インデックス作成
_m( "インデックス作成開始" );
$res = $db->query( "CREATE INDEX idx ON main(pca1, pca2, pca3)" );
$er = $db->errorInfo();
if ( $er[0] != '00000' )
	_m( "エラー: " . print_r( $er, 1 ) );
else
	_m( "完了" );


$db->commit();

