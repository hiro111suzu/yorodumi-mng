<?php
require_once( "commonlib.php" );

//. sqlite
$db = new cls_sqlite( 'sacs' );
$done = [];
$date2pdb = [];
foreach ( $db->qcol([ 'select' => 'id' ]) as $id ) {
	_count( 'pdb' );
	$id = explode( '-', $id )[0];
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

_comp_save( DN_PREP. '/dbid/sacs-latest.json.gz', [
	'date' => $latest ,
	'pdbids' => $date2pdb[ $latest]
]);

