<?php
require_once( "commonlib.php" );
//$data = [];
//$data_lev = [];

$_filenames += [
	'pdb_kw'	=> DN_PREP . "/keyword/pdb/<id>.txt" ,
];

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => 'authori', 
	'cols' => [
		'id UNIQUE' ,
		'count INTEGER' ,
		'children' ,
		'parents' ,
		'kw COLLATE NOCASE'
	],
	'new' => true ,
	'indexcols' => [ 'id', 'count' ],
]);

//. main loop
$data = _json_load( DN_PREP . '/authori/data.json.gz' );
//$all_rev = _json_load( DN_PREP . '/authori/all_rev.json.gz' );

foreach ( _idloop( 'pdb_kw', '*' ) as $fn ) {
	_count( 5000 );
	$id = _fn2id( $fn );
	$d = $data[$id];
	//- クエリ
	$sqlite->set([
		$id ,
		$d[ 'a' ] , //- count
		implode( ',', (array)$d[ 'c' ] ) , //- children
		implode( ',', (array)$d[ 'p' ] ) , //- parents
		_kwfile2str( $fn )
	]);
	
}
$sqlite->end();
