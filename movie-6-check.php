あてはめムービーがあるかチェック

<?php

//. init
require_once( "commonlib.php" );
define( 'FITDB', _json_load( DN_PREP. '/fit_confirmed.json.gz' ) );

$blist = new cls_blist( 'ignore_fitmov', 'もう作らない' );
define( 'STATUS_JSON', _json_load( FN_DBSTATUS ) );
$task_json = [];

//. maind
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( ! file_exists( _fn( 'mapgz', $id ) ) ) continue;

	//- movinfo
	$in_mov = [];
	$json = _json_load( _fn( 'movinfo', $id ) );
	foreach ( (array)$json as $mi ) {
		$in_mov = array_merge( (array)$in_mov, (array)$mi[ 'fittedpdb' ] );
	}

	foreach ( (array)FITDB[ "emdb-$id" ] as $pdb_id ) {
		$pdb_id = strtr( $pdb_id, [ 'pdb-' => ''  ] );
		if ( ! _inlist( $pdb_id, 'epdb' ) ) continue;
		if ( $blist->inc( "$id-$pdb_id" ) ) continue;
		if ( in_array( $pdb_id, $in_mov ) ) {
			continue;
		}
		_problem( "$id: no fit movie for $id-$pdb_id" );
	}
	
	//- task まだ画像がない
	$stat = STATUS_JSON[ "emdb-$id" ];
	if ( ! in_array( $stat[ 'img' ], [ 1, 2, 3 ] ) ) {
//		_pause( "$id:". $stat[ 'img' ] );
		if ( $json[1] || $json[2] )
			$task_json[ "$id-session" ] = true;
		if ( $stat[ 'pg1' ] )
			$task_json[ "$id-polygon" ] = true;
	}
}
//_kvtable( $task_json );
_json_save( DN_PREP. '/mov_task_info.json', $task_json ); 
_end();


