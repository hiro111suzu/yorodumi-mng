<?php
require_once( "commonlib.php" );
define( 'FN_CATH_NAME', DN_FDATA. '/dbid/cath-b-newest-names.gz' );
define( 'FN_CATH_ALL' , DN_FDATA. '/dbid/cath-b-newest-all.gz' );

//. sqlite

$sqlite = new cls_sqlw([
	'fn' => 'cath', 
	'cols' => [
		'id UNIQUE' ,
		'data'
	],
	'new' => true ,
	'indexcols' => [ 'id' ] ,
]);

//. all

_line( 'CATH-all read' );
$all = [];

$json = [];
foreach ( gzfile( FN_CATH_ALL ) as $line ) {
	_count( 50000 );
	list( $id, $version, $cid, $data ) = explode( ' ', trim( $line ), 4 );
	$id = substr( $id, 0, 4 );
	if ( ! _inlist( $id, 'pdb' ) ) continue;

	$seq = [];
	foreach ( explode( ',', $data ) as $s )
		$seq[] = explode( ':', $s )[0];

	$all[ substr( $id, 0, strlen( $id_str ) - 2 ) ][] = [
		'ver' => $version ,
		'cid' => $cid ,
		'seq' => $seq
	];
	$json[ $id ][] = $cid;
}

_line( 'CATH-all load' );
_count();

foreach ( $all as $k => $v ) {
	_count( 50000 );
	$sqlite->set([ $k, json_encode( $v ) ]);
}

foreach ( $json as $k => $v ) {
	$json[ $k ] = array_values( _uniqfilt( $v ) );
}
_comp_save( FN_PDB2CATH, $json );


//. name
_line( 'CATH-name' );
_count();

$json = [];
foreach ( gzfile( FN_CATH_NAME ) as $line ) {
	_count( 5000 );
	list( $id, $name ) = explode( ' ', trim( $line ), 2 );
	if ( ! $name ) continue;
	$sqlite->set([ $id, $name ]);
	$json[ $id ] = $name;
}
_comp_save( FN_CATH_NAME_JSON, $json );
$sqlite->end();

