omo-pdb-2: プロファイル作成

<?php

//. init
require_once( "commonlib.php" );

$depdn = '/novdisk2/db/omopdb/dep/';
$asbdn = '/novdisk2/db/omopdb/asb/';
$vqdn  = '/novdisk2/db/omopdb/vq';
$errdn = '/novdisk2/db/omopdb/error';
$profdn  = '/novdisk2/db/omopdb/prof';
$dbfn = '/novdisk2/db/omopdb/profdb.sqlite';

//_initlog( "pdb-9: make vq pdb" );
//define( BLIST, 'blacklist_make_pdb_vq' );

//. データベース 準備

$columns = <<< EOF
id	UNIQUE
time
vq30
vq50
out
pca1	REAL
pca2	REAL
pca3	REAL
EOF;

$columns =  str_replace( "\r\n", ',' , $columns ) ;
$db = new PDO( "sqlite:$dbfn", '', '' );
$db->beginTransaction();

//.. テーブルがなければ作成
$res = $db->query( 
	"select name from 'sqlite_master' where type='table' and name='main'" ) ;

if ( $res->fetch() == '' ) {
	$res = $db->query( "CREATE TABLE main( $columns )" );
	_m( "新DBファイル作成" );
}

//. main
//die();
foreach ( scandir( $profdn ) as $fn ) {
	if ( _ext( $fn ) != 'txt' ) continue;
	$fn = "$profdn/$fn";
	$id = basename( $fn, '.txt' );
	$time = filemtime( $fn );
	if ( _get( $id, 'time' ) == $time ) {
//		_m( _get( $id, 'time' )  . ' == ' .  $time );
		continue;
	}

	//.. 読み取り
	$data = '';
	_data( $id );
	_data( $time );
	foreach ( _file( $fn ) as $l )
		_data( $l );

	//.. write
	$res = $db->query( "INSERT INTO main VALUES ($data)" );//->fetchAll();
	_m( "$id: loaded" );

	$cnt ++;
//	if ( $cnt > 5 ) break;
}
_m( $cnt );
$db->commit();

//. func
//.. _data
function _data( $in ) {
	global $data;
	$data = trim( "$data,\"$in\"", ',' );
}

//.. _get
function _get( $id, $col ) {
	global $db;
	$res = $db
		->query( "SELECT $col FROM main WHERE id = '$id'" )
		->fetchAll( PDO::FETCH_ASSOC )
	;
	return $res[0][ $col ];
}

