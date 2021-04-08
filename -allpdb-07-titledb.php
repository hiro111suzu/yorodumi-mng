<?php
require_once( "commonlib.php" );

$sqlite = new cls_sqlw([
	'fn' => 'pdbtitle', 
	'cols' => [
		'id UNIQUE' ,
		'title'
	],
	'indexcols' => [ 'id' ],
	'new' => true
]);

foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 5000 );
	$sqlite->set([
		_fn2id( $fn ),
		_json_load2( $fn )->title
	]);
}
$sqlite->end();
