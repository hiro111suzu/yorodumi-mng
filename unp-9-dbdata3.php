-----
sqlite
strid => dbids
-----
<?php
require_once( "unp-common.php" );

define( 'FN_STRID2DBIDS', DN_DATA. '/strid2dbids.sqlite' );

/*
if ( _newer( FN_DBID2STRID, [ FN_DBDATA_PDB, FN_DBDATA_EMDB, FN_DBDATA_SASBDB ] ) ) {
	_m( 'DB file is new' );
}
*/

define( 'DBID2STR_NUM', _json_load( FN_DBID2STRCNT ) );

//. main
$sqlite = new cls_sqlw([
	'fn' => FN_STRID2DBIDS , 
	'cols' => [
		'strid UNIQUE COLLATE NOCASE' ,
		'dbids COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'strid' ] ,
]);

//.. PDB
_count();
_line( 'PDB' );
foreach ( _json_load( FN_DBDATA_PDB ) as $pdb_id => $dbids ) {
	_count( 'pdb' );
	_sort_set( $pdb_id, $dbids );
}
//.. EMDB
_count();
_line( 'EMDB' );
foreach ( _json_load( FN_DBDATA_EMDB ) as $emdb_id => $dbids ) {
	_count( 'emdb' );
	_sort_set( "e$emdb_id", $dbids );
}

//.. SASBDB
_count();
_line( 'SASBDB' );
foreach ( _json_load( FN_DBDATA_SASBDB ) as $sasbdb_id => $dbids ) {
	_count( 'sas' );
	_sort_set( $sasbdb_id, $dbids );
}

//. end
$sqlite->end();


//. func
function _sort_set( $str_id, $dbids ) {
	global $sqlite;
	$sort = [];
	foreach ( $dbids as $i ) {
		$type = preg_replace( '/:.*$/', '', $i );
		$sort[ $i ] = in_array( $type , [ 'go', 'ec', 'pf', 'in', 'pr', 'sm', 'rt', 'ct' ] )
			? DBID2STR_NUM[ $i ]
			: 1000000
		;
	}
	asort( $sort );
//	_line( $str_id );
//	_pause( $sort );

	$sqlite->set([
		$str_id ,
		implode( '|', array_keys( $sort ) ),
	]);

}

/*
|GO       |OK, categ, name            |
|EC       |name (親をたどるべき)      |
|Pfam     |分けるべき                 |
|InterPro |OK, categ, txt             |
|UniProt  |nameのみ、生物種を足すべき |
|Prosite  |OK, ID, name               |
|reactome |OK, name、taxo             |
*/
