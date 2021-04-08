<?php
require_once( "commonlib.php" );

$dn = DN_PREP . '/pap';
$_filenames += [
	'emdb_pap' => "$dn/emdb/<id>.json" ,
	'emdb_kw' => "$dn/emdb/<id>.txt" ,
	'pdb_pap'  => "$dn/pdb/<id>.json" ,
	'pdb_kw'  => "$dn/pdb/<id>.txt" ,

];

//. チェック
$data = [];
$ecnt = 0;
_m( 'emdb' );
foreach ( _idloop( 'emdb_pap' ) as $pn ) {
	++ $ecnt;
	foreach ( _json_load( $pn ) as $k => $v ) {
		if ( $v == '' or $v == [] ) continue;
		++ $data[ $k ][ 'emdb' ];
	}
	_count( 1000, 0 );
}

_m( 'pdb' );
$pcnt = 0;
foreach ( _idloop( 'pdb_pap' ) as $pn ) {
	++ $pcnt;
	foreach ( _json_load( $pn ) as $k => $v ) {
		if ( $v == '' or $v == [] ) continue;
		++ $data[ $k ][ 'pdb' ];
	}
	_count( 1000, 0 );
}

foreach ( $data as $k => $v ) {
	_m( "[$k] EMDB " . _r( $v[ 'emdb' ], $ecnt )
		. ' / PDB:' . _r( $v[ 'pdb' ], $pcnt )
	);
}

function _r( $v, $cnt ) {
	return round( $v / $cnt * 100, 2 ) . '%';
}

/*
- pmid
- doi
- title
- journal
- issue
- author
- date
- src

- kw

*/

/*
$pmid2did = _json_load( "$dn/pmid2did.json.gz" );
_m( "データ数: " . count( $pmid2did ) );

//. prep db
$dbfn  = DN_DATA  . '/pap.sqlite';
$columns = implode( ',', [
	'pmid UNIQUE' ,
	'name COLLATE NOCASE' ,
	'syn COLLATE NOCASE' ,
	'form' ,
	'weight REAL' ,
	'date' ,
	'kw COLLATE NOCASE'
]);

$sqlite = new PDO( "sqlite:$dbfn", '', '' );
$sqlite->beginTransaction();

$res = $maindb->query( "select name from 'sqlite_master' where type='table' and name='main'" ) ;
if ( $res->fetch() == '' ) {
	$res = $sqlite->query( "CREATE TABLE main( $columns )" );
	_log( "pap-DB 作り直し" );

	//- インデックス作成
	foreach ( $indexcols as $c ) {
		$res = $maindb->query( "CREATE INDEX i$c ON main($c COLLATE NOCASE)" );
		$er = $maindb->errorInfo();
		if ( $er[0] === '00000' )
			_m( "インデックス作成: $c" );
		else
			_m( "$did - ERROR !: " . implode( ' / ', $er ) );
		_m( "index $c: " . implode( ' / ', $er ) );
	}

}


//. main

foreach ( $pmid2did as $pmid ) {
	
}
*/