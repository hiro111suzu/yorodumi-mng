-----
sqlite
strid => dbids
-----
<?php
require_once( "unp-common.php" );

define( 'FN_STRID2DBIDS', DN_DATA. '/strid2dbids.sqlite' );
//define( 'FN_STRID2DBID' , DN_DATA. '/strid2dbid.sqlite' );

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
		'score REAL' ,
		'score_f REAL' ,
		'score_h REAL' ,
		'score_c REAL' ,
	],
	'new' => true ,
	'indexcols' => [ 'strid' ] ,
]);

// $sqlite2 = new cls_sqlw([
// 	'fn' => FN_STRID2DBID , 
// 	'cols' => [
// 		'strid COLLATE NOCASE' ,
// 		'dbid COLLATE NOCASE' ,
// 	],
// 	'new' => true ,
// 	'indexcols' => [ 'strid', 'dbid' ] ,
// ]);


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

//.. end
$sqlite->end();
//$sqlite2->end();

//. func
//.. _sort_set
function _sort_set( $str_id, $dbids ) {
	global $sqlite, $sqlite2;
	$sort = [];
	$score = [];
	foreach ( $dbids as $i ) {
		$num = DBID2STR_NUM[ strtolower( $i ) ];
		$type = explode( ':', $i, 2 )[0];
		$sort[ $i ] = in_array( $type , [ 'go', 'ec', 'pf', 'in', 'pr', 'sm', 'rt', 'ct' ] )
			? $num
			: 1000000
		;
		//- score
		$s = 1 / ( $num ?: 1000000 );
		$score['all'] += $s;
		$score[ _db_name2categ( $type ) ] += $s;
	}
	asort( $sort );
	$sqlite->set([
		$str_id ,
		implode( '|', array_keys( $sort ) ),
		$score['all'] ,
		$score['f'] ,
		$score['h'] ,
		$score['c'] ,
	]);

//	foreach ( array_keys( $sort ) as $i )
//		$sqlite2->set([ $str_id, $i ]);
}

//. memo

/*
|GO       |OK, categ, name            |
|EC       |name (親をたどるべき)      |
|Pfam     |分けるべき                 |
|InterPro |OK, categ, txt             |
|UniProt  |nameのみ、生物種を足すべき |
|Prosite  |OK, ID, name               |
|reactome |OK, name、taxo             |
*/
