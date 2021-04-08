<?php
require_once( "commonlib.php" );

//. sqlite
$db_cath = new cls_sqlite( 'cath' );
$done = [];
$date2pdb = [];
foreach ( $db_cath->qcol([ 'select' => 'id', 'where' => 'id not like "%.%"' ]) as $id ) {
	_count( 'pdb' );
	$id = substr( $id, 0, 4 );
	if ( strlen( $id ) != 4 ) continue;
	if ( $done[ $id ] ) continue;
	$done[ $id ] = true;
	$date2pdb[ _ezsqlite([
		'dbname' => 'pdb',
		'select' => 'rdate',
		'where'  => [ 'id', $id ] 
	]) ][] = $id;
}

$latest = max( array_keys( $date2pdb) );
_m( '最新データ: '. $latest );
_m( implode( ' ', array_slice( $date2pdb[ $latest], 0, 100 ) ) );

_comp_save( DN_PREP. '/dbid/cath-latest.json.gz', [
	'date' => $latest ,
	'pdbids' => $date2pdb[ $latest]
]);

