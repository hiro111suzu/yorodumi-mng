<?php
//. init
require_once( "omopre-common.php" );

//. db
$sqlite = new cls_sqlw([
	'fn' => 'omopre', 
	'cols' => [
		'id UNIQUE' ,
		'data' ,
	],
	'new' => true ,
	'indexcols' => [ 'id' ] ,
]);

//. main
foreach ( _idloop( 'omolist' ) as $fn ) {
	_count( 'pdb', 0 );
	$out = [];
	$rank = 0;
	foreach ( _json_load( $fn ) as $id => $score ) {
		if ( $score < OMOPRE_SCORE_LIMIT[ $rank ] ) break;
		++ $rank;
		$out[] = $id;
	}
	$sqlite->set([ _fn2id( $fn ), implode( ',', $out ) ]);
}

$sqlite->end();
