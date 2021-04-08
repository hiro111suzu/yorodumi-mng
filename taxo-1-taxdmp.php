<?php
/*
ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz
*/
require ( __DIR__ . '/commonlib.php' );
require ( __DIR__ . '/taxo-common.php' );

define( 'CNT_INTERVAL', 100000 );
//. download
$tmpfn = DN_FDATA . '/taxdump.tar.gz';
_mkdir( $dn = DN_FDATA . '/taxdump' ); 
exec( "rm -rf $dn/*" );
_m( 'taxdumpダウンロード開始' );
copy( 'ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz', $tmpfn );
_m( 'taxdumpダウンロード完了' );

exec( "tar xzvf $tmpfn -C $dn" );

//. 解析
$fn_names	= DN_FDATA . '/taxdump/names.dmp';
$fn_nodes	= DN_FDATA . '/taxdump/nodes.dmp';

//. nodes
_line( 'nodes' );
_count();
$o_sql = new cls_sqlw([
	'fn' => FN_ID2PARENT, 
	'cols' => [
		'id UNIQUE' ,
		'parent' ,
	],
	'indexcols' => [ 'id' ],
	'new' => true
]);

foreach ( _file( $fn_nodes ) as $line ) {
	if ( _count( CNT_INTERVAL, 0 ) ) break;
	list( $id , $parent ) = explode( "\t|\t", $line, 3 );
	$parent = _trim( $parent );
	if ( ! $parent ) continue;
	$o_sql->set([ _trim( $id ), $parent ]);
}
$o_sql->end();

//. id2name 
_count();
_line( 'id2name' );

$o_sql = new cls_sqlw([
	'fn' => FN_ID2NAME, 
	'cols' => [
		'id' ,
		'name COLLATE NOCASE' ,
		'type' ,
	],
	'indexcols' => [ 'id', 'name', 'type' ],
	'new' => true
]);

$uk_type = []; //- その他のタイプ
foreach ( _file( $fn_names ) as $line ) {
	if ( _count( CNT_INTERVAL, 0 ) ) break;

	list( $id, $name, $dummy, $cls ) = explode( "\t|\t", $line );
	$id		= _trim( $id   );
	$name	= _trim( $name );
	$type	= [
		'scientific name'		=> 'n' ,
		'common name' 			=> 'c' ,
		'genbank common name'	=> 'gc' ,
		'synonym' 				=> 's' ,
		'genbank synonym' 		=> 'gs' ,
		'equivalent name' 		=> 'eq' ,
		'misspelling' 			=> 'm' ,
		'includes'				=> 'i' ,
	][ _trim( $cls ) ] ?: 'x';
	if ( $type == 'x' )
		++ $uk_type[ _trim( $cls ) ];
	
	$o_sql->set([ $id, $name, $type ]);
}
$o_sql->end();
_kvtable( $uk_type );

//. func _trim
function _trim( $in ) {
	return trim( $in, "| \t\r" );
}

