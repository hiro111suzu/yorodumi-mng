あてはめムービーがあるかチェック

<?php

//. init
require_once( "commonlib.php" );
define( 'STATUS_JSON', _json_load( FN_DBSTATUS ) );
define( 'FITDB', _json_load( DN_PREP. '/fit_confirmed.json.gz' ) );

$blist_fitmov = new cls_blist( 'ignore_fitmov', 'もう作らない' );

$ids = ( new cls_sqlite( 'main' ) )->qcol([
	'where' => [ 'release = '. _quote( _rel_date() ), "database = 'EMDB'" ] ,
	'select' => 'id'
]);

//. main
$data = [];
foreach ( $ids as $id ) {
	if ( ! file_exists( _fn( 'mapgz', $id ) ) ) continue;
	$movjson = _json_load([ 'movinfo', $id ]);

	//.. mov list
	$mov_list = [];
	foreach ( [1, 2] as $num )
		$mov[ $num ] = is_object( $movjson[ $num ] );

	// fit
	$in_mov = [];
	foreach ( (array)$movjson as $mi )
		$in_mov = array_merge( (array)$in_mov, (array)$mi[ 'fittedpdb' ] );

	foreach ( (array)FITDB[ "emdb-$id" ] as $pdb_id ) {
		$pdb_id = strtr( $pdb_id, [ 'pdb-' => ''  ] );
		if ( $blist->inc( "$id-$pdb_id" ) ) continue;
		$mov_list[ $pdb_id ] = in_array( $pdb_id, $in_mov );
	}

	$data[ $id ] = [
		'mov' =>
	]
}
//_kvtable( $task_json );
_json_save( DN_PREP. '/mov_task_info.json', $task_json ); 
_end();


