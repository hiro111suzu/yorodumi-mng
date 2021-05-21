<?php

require_once( "commonlib.php" );
$dn = realpath( DN_EMNAVI . '/doc' );
$dbfn  = "$dn/doc.sqlite";
$docfn = "$dn/doc.json.gz";

chdir( '../emnavi' );
exec( 'php ../emnavi/docdb.php nodisp' );
chdir( __DIR__ );

//. prep db

$columns = implode( ',', [
//	'ord INTEGER' ,
	'id UNIQUE COLLATE NOCASE' ,
	'kw' ,
	'type' ,
	'tag' 
]);

_del( $dbfn );
$pdo = new PDO( "sqlite:$dbfn", '', '' );
$pdo->beginTransaction();
$res = $pdo->query( "CREATE TABLE main( $columns )" );

//. main
//$order = 0;
foreach ( _json_load( $docfn ) as $id => $c ) {
//	++ $order;
	_m( $id );

	//- キーワード
	$kw = [];
	foreach ( [ 'e', 'j' ] as $l ) {
		foreach ( (array)$c[ $l ] as $s ) {
			foreach ( preg_split( '/(<br>|<p>|<li>|<td>|<tr>)/', $s ) as $s2 ) {
				$kw[] = strip_tags( $s2 );
			}
		}
	}

	//- クエリ文字列作成
	$vals = implode( ', ', [
		//- order
//		$order ,
		
		//- ID
		_q( $id ) ,

		//- キーワード
		_q( implode( '|', array_unique( array_filter( $kw ) ) ) ) ,

		//- タイプ
		_q( $c[ 'type' ] ) ,

		//- タグ
		_q( '|' . implode( '|', (array)$c[ 'tag' ] ) . '|' ) ,
	
	]);
	if ( $pdo->query( "REPLACE INTO main VALUES ( $vals )" ) === false ) {
		_m( "$id: 失敗", -1 );

		_m( "エラー: " . print_r( $er = $pdo->errorInfo(), 1 ) );
	}
}
//- DB終了
$pdo->commit();

//. function
function _q( $s ) {
	return "'" . strtr( $s, [ "'" => "''" ] ) . "'";
}
