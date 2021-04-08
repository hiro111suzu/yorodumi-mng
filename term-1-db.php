<?php
require_once( "commonlib.php" );

//. read
_line( 'read' );
$data = [];
foreach ( _file( DN_FDATA. '/scientific_term.ja_vs_en.utf8.tsv' ) as $line ) {
	_count( 5000 );
	list( $ja, $en, $dummy ) = explode( "\t", $line, 3 );
//	_pause( "$en=>$ja" );
	$data[ $en ][] = $ja;
}

//. db
$db = new cls_sqlw([
	'fn' => 'term', 
	'cols' => [
		'en UNIQUE' ,
		'ja'
	],
	'new' => true ,
	'indexcols' => [ 'en' ] ,
]);

//. write
foreach ( $data as $en => $ja ) {
	if ( strlen( $en ) < 4 ) continue;
	$db->set([ $en, implode( '|', _uniqfilt( $ja ) ) ]);
}

$db->end();

