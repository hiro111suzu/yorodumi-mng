<?php
/*
毎回作る
*/
require_once( "pap-common.php" );

//. prep db
/*
data
	author
	doi
	pii
	title
	date_type
	issue
	kw 表示
	src
	chemid
	ids
*/

$sqlite = new cls_sqlw([
	'fn' => 'pap', 
	'cols' => [
		'pmid UNIQUE' ,
		'journal COLLATE NOCASE' ,
		'date' ,
		'emflg INTEGER' ,
		'if REAL' ,
		'method COLLATE NOCASE' ,
		'search_kw COLLATE NOCASE' ,
		'search_auth COLLATE NOCASE' ,
		'score INTEGER' ,
		'data'
	],
	'indexcols' => [ 'pmid', 'journal', 'date', 'emflg', 'if', 'score' ] ,
	'new' => true
]);

//. main
foreach ( _idloop( 'pap_info' ) as $pn ) {
	_count( 5000 );
	$sqlite->set( _json_load( $pn ) );
}

//. DB終了
$sqlite->end();
_end();
