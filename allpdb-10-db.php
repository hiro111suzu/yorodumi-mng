<?php
include "commonlib.php";

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => 'pdb', 
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'title' ,
		'rdate' ,
		'reso REAL' ,
		'method COLLATE NOCASE' ,
		'search_kw COLLATE NOCASE' ,
		'search_auth COLLATE NOCASE' ,
		'json' ,
	],
	'indexcols' => [ 'id', 'rdate', 'reso' ] ,
	'new' => true
]);

//. main
_count();
foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 'pdb' );
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	$sqlite->set([
		$id ,
		$json->title ,
		$json->rdate ,
		$json->reso ,
		implode( ',', $json->method ) ,
		implode( '|', (array)_file( _fn( 'pdb_kw', $id  ) ) ) ,
		' ' . implode( ' | ', (array)_file( _fn( 'pdb_auth', $id  ) ) ) . ' ',
		json_encode( array_filter([
			'identasb' => $json->identasb ,
			'src' => array_values( (array)$json->src ) ,
		]), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE ) ,
	]);
}

//. DB終了
$sqlite->end();
_end();
