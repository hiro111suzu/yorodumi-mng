<?php
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );

define( 'ANNOT', _tsv_load2( FN_TAXO_ANNOT ) );

$o_id2parent = new cls_sqlite( FN_ID2PARENT );
$o_id2name   = new cls_sqlite( FN_ID2NAME );
define( 'TSV_REP', _tsv_load2( FN_TSV_REP ) );

$sqlite = new cls_sqlw([
	'fn' => 'taxoid', 
	'cols' => [
		'id INTEGER' ,
		'name COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'id', 'name' ],
]);

//. main
$data = [];
foreach ( _json_load2( FN_LIST_ALL ) as $key => $c ) {
	$names = (array)$c->n;
	arsort( $names );
	$name = array_keys( $names )[0];
	$ids = (array)$c->i;
	unset( $ids['-'] );
	if ( $ids ) {
		arsort( $ids );
		$id = array_keys( $ids )[0];
	} else {
		$id = '';
	}
	$id = _name2id( $name ) ?:
		_name2id( TSV_REP['rep'][ strtolower( $name ) ] ) ?: //- マニュアルアノテーション
		$id
	;

	if ( !$id ) {
		_cnt( 'no id' );
		_m( "$name - no ID",  "green" );
	} else {
		_cnt( 'with id' );
	}
//	$data[ $key ] = $id;
	_cnt( 'total');
	if ( $id ) {
		$sqlite->set([
			$id,
			$name
		]);
	}
	$data[ $id ?: $key ][] = $name;
}
$sqlite->end();
_cnt();
_json_save( FN_ID2NAME_JSON, $data );

//. str-id - taxo-id
$o_read = new cls_sqlite( 'taxoid' );
$sqlite = new cls_sqlw([
	'fn' => 'taxostr', 
	'cols' => [
		'db COLLATE NOCASE' ,
		'id COLLATE NOCASE' ,
		'taxo COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'db', 'id', 'taxo' ],
]);

$data = [];
foreach ( _json_load( FN_PDB_ID2TAXNAME ) as $id => $names ) {
	$data[ "p|$id" ] = $names;
}
foreach ( array_merge( $data, _json_load( FN_OTHERS_ID2TAXNAME ) ) as $did => $names ) {
	list( $db, $id ) = explode( '|', $did );
	foreach ( $names as $n ) {
		$tx = $o_read->qcol([
			'select' => 'id',
			'where' => 'name='. _quote( $n )
		])[0] ?: $n ;
		$sqlite->set([
			$db, $id, $tx
		]);
	}
}
$sqlite->end();

